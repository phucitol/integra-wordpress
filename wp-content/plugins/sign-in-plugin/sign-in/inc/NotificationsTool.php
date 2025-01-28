<?php

require SIGN_IN_PLUGIN_PATH . '/vendor/autoload.php';
require_once('CVENT.php');


use Dompdf\Dompdf;

// Notification Tool
//
// For example usage see comment at the bottom of the file


enum NotificationType
{
    case EMAIL_HTML;
    case EMAIL_SHARE_HTML;
    case EMAIL_PDF;
    case EMAIL_CSV;
    case EMAIL_CONCUR;
    case EMAIL_ATTENDANCE;
}

class NotificationsTool
{
    public function SendNotification(NotificationType $type, String $recipients, String $subject, Event $event, array $attendees)
    {
        switch ($type) {
            case NotificationType::EMAIL_HTML:
                $this->SendEmailHTML($recipients, $subject, $event, $attendees);
                break;
            case NotificationType::EMAIL_SHARE_HTML:
                $this->SendShareHTML($recipients, $subject, $event);
                break;
            case NotificationType::EMAIL_PDF:
                $this->SendEmailPDF($recipients, $subject, $event, $attendees);
                break;
            case NotificationType::EMAIL_CSV:
                $this->SendEmailCSV($recipients, $subject, $event, $attendees);
                break;
            case NotificationType::EMAIL_CONCUR:
                $this->SendConcurReport($recipients, $subject, $event, $attendees);
                break;
            case NotificationType::EMAIL_ATTENDANCE:
                $this->SendAttendanceCSV($recipients, $subject, $event, $attendees);
                break;
        }
    }

    private function SendConcurReport(String $recipients, String $subject, Event $event, array $attendees)
    {
        //$author_id = get_post_field('post_author', $event->id);
        //$author_name = aasgnn_first_last( $author_id );

        $args = array(
            'post_parent' => $event->id,
            'post_type' => 'attachment',
            'post_mime_type' => 'application/vnd.ms-excel',
            'posts_per_page' => -1
        );

        $children = get_posts($args);

        // Find the PDF with "concur" in its name
        $xls_path = '';
        foreach ($children as $child) {
            if (strpos($child->post_title, 'xls') !== false) {
                $xls_path = get_attached_file($child->ID);
                break;
            }
        }

        $attachments = array();

        if (!empty($xls_path)) {
            $attachments = array($xls_path);
        }

        $headers = 'Content-Type: text/html; charset=UTF-8';

        $html = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body><h1 style="text-align:center">Concur Report</h1>';
        $html .= '<p>Sales Rep: ' . $event->metadata['rep_name'] . '</p>';
        $html .= '<p>Event Name: ' . $event->metadata['event_name'] . '</p>';
        $html .= '<p>Description: ' . $event->metadata['event_description'] . '</p>';

        if (get_option('third_party_system', '') === 'cvent' && $event->metadata['event_type'] === 'planned-event') {
            $html .= '<p>cVent ID (Event ID): ' . $event->metadata['cvent_id'] . '</p>';
        }
        
        $html .= '<p>Start: ' . $event->metadata['start_time'] . '</p>';
        $html .= '<p>End: ' . $event->metadata['end_time'] . '</p>';

        if ( !empty( $event->metadata['concur_note'] ) ) {
            $html .= "<p>Note from rep:<br />" . $event->metadata['concur_note'] . "</p>";
        }

        $html .= "</body></html>";

        wp_mail($recipients, $subject, $html, $headers, $attachments);
    }

    private function SendAttendanceCSV(String $recipients, String $subject, Event $event, array $attendees)
    {
        // $author_id = get_post_field('post_author', $event->id);
        // $author_name = aasgnn_first_last( $author_id );

        $headers = "Content-Type: text/html; charset=UTF-8";
        $args = array(
            'post_parent' => $event->id,
            'post_type' => 'attachment',
            'post_mime_type' => 'text/csv',
            'posts_per_page' => -1
        );

        $attachments = get_posts($args);

        $csv_path = get_attached_file($attachments[0]->ID);

        if (!$csv_path) {
            return false;
        }

        $html = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body><h1 style="text-align:center">Attendance Report</h1>';
        $html .= '<p>Sales Rep: ' . $event->metadata['rep_name'] . '</p>';
        $html .= '<p>Event Name: ' . $event->metadata['event_name'] . '</p>';
        $html .= '<p>Description: ' . $event->metadata['event_description'] . '</p>';

        if (get_option('third_party_system', '') === 'cvent' && $event->metadata['event_type'] === 'planned-event') {
            $html .= '<p>cVent ID (Event ID): ' . $event->metadata['cvent_id'] . '</p>';
        }
        
        $html .= '<p>Start: ' . $event->metadata['start_time'] . '</p>';
        $html .= '<p>End: ' . $event->metadata['end_time'] . '</p>';
        $html .= "</body></html>";

        $attachments = array($csv_path);

        wp_mail($recipients, $subject, $html, $headers, $attachments);
    }

    private function SendShareHTML(String $recipients, String $subject, Event $event)
    {
        $author_id = get_post_field('post_author', $event->id);
        $author_name = aasgnn_first_last( $author_id );

        $src = ""; //TODO:create a placeholder image to output
        // Get the ID of the post's featured image.
        $thumbnail_id = get_post_thumbnail_id($event->id);

        // Check if the post has a featured image.
        if ($thumbnail_id) {
            // Get the image source (src) of the featured image.
            $src = wp_get_attachment_image_url($thumbnail_id, 'full');
        }

        $attachments = array();
        $headers = "Content-Type: text/html; charset=UTF-8";
        $html = '<!DOCTYPE html>
        <html lang="en">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Document</title>
            <style>
            html,
            body {
                font-family: "Soleil", sans-serif;
            }
    
            .letterhead {
                max-width: 800px;
                width: 100%;
                margin: 0 auto;
            }
    
            .letterhead header {
                background-size: 100% auto;
                padding: 2rem;
                margin-bottom: 4rem;
            }
    
            .letterhead header img {
                height: 32px;
            }
    
            h1 {
                text-align: center;
            }
    
            .event-details {
                text-align: center;
            }
    
            .event-details span {
                margin: 0 1rem;
            }
    
            .qr-code {
                margin-top: 1rem;
                text-align: center;
            }
    
            .qr-code img {
                width: 138px;
                height: 138px;
                margin-bottom: 1rem;
            }
    
            .qr-code span {
                display: block;
            }
    
            .instructions {
                margin-top: 2rem;
                line-height: 1.75rem;
            }
    
            footer {
                max-width: 800px;
                width: 100%;
                bottom: 2rem;
                border-top: 5px solid #326EAF;
                padding-top: 2rem;
                text-align: center;
            }
            </style>
        </head>
        </body>
        <div class="letterhead">
            <header style="background-image:url(\'' . SIGN_IN_PLUGIN_URL . 'img/header_bg.png\')">
                <img src="' . SIGN_IN_PLUGIN_URL . 'img/header_logo.png">
            </header>
            <h1>' . $event->event_name . '</h1>
            <div class="event-details">
                <span>Sales Rep: ' . $author_name . '</span>
                <span>Start Date: ' . $event->start_time . '</span>
            </div>
            <div class="qr-code">
                <h3>QR Code Check In</h3>
                <img src="' . $src . '" />
                <span>Check in URL: <a href="' . get_site_url(null, 'sign-in-registration/?event=' . $event->guid) . '">' . get_site_url(null, 'sign-in-registration/?event=' . $event->guid) . '</a></span>
            </div>
            <div class="instructions">
            Please scan the QR code or visit the Check in URL on your device to access the event check in.
            </div>

            <footer>
                4555 Riverside Dr, Palm Beach Gardens, FL 33410 • 800-342-5454
            </footer>
        </div>
        </html>';

        wp_mail($recipients, $event->event_name, $html, $headers, $attachments);
    }

    private function SendEmailHTML(String $recipients, String $subject, Event $event, array $attendees)
    {
        $attachments = array();
        $headers = "Content-Type: text/html; charset=UTF-8";
        $html = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><style>';
        $html = $html . "@import url('https://fonts.cdnfonts.com/css/soleil'); html,body {font-family: 'Soleil', sans-serif;} table {width:100%; border-collapse: collapse;} th {background-color: #F1F1F2; border:solid 1px #aaa;} td {border:solid 1px #aaa;} ";

        $html = $html . "</style></head><body><h1>" . $event->name . "</h1>
        <table><thead><tr><th>AtnTypeKey</th><th>LastName</th><th>FirstName</th><th>Title</th><th>Company</th><th>ExternalId</th><th>Custom15</th><th>Custom16</th><th>Custom17</th><th>Custom18</th><th>Custom19</th><th>Custom20</th></tr></thead>
        <tbody>";

        foreach ($attendees as $att) {
            $html = $html . "<tr>";
            $html = $html . "<td>" . $att->type . "</td>";
            $html = $html . "<td>" . $att->lastName . "</td>";
            $html = $html . "<td>" . $att->firstName . "</td>";
            $html = $html . "<td>" . $att->title . "</td>";
            $html = $html . "<td>" . $att->company . "</td>";
            $html = $html . "<td>" . $att->email . "</td>";
            $html = $html . "<td>" . $att->primaryAddress1 . "</td>";
            $html = $html . "<td>" . $att->primaryAddress2 . "</td>";
            $html = $html . "<td>" . $att->primaryAddress3 . "</td>";
            $html = $html . "<td>" . $att->city . "</td>";
            $html = $html . "<td>" . $att->state . "</td>";
            $html = $html . "<td>" . $att->postalCode . "</td>";
            $html = $html . "</tr>";
        }

        $html = $html . "</tbody></table></body></html>";

        wp_mail($recipients, $event->name, $html, $headers, $attachments);
    }

    private function SendEmailPDF(String $recipients, String $subject, Event $event, array $attendees)
    {
        $headers = "Content-Type: text/html; charset=UTF-8";
        $html = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><style>';
        $html = $html . "@import url('https://fonts.cdnfonts.com/css/soleil'); html,body {font-family: 'Soleil', sans-serif; font-size:8px;} table {width:100%; border-collapse: collapse;} th {background-color: #F1F1F2; border:solid 1px #aaa;} td {border:solid 1px #aaa;} ";

        $html = $html . "</style></head><body><img src='../img/pdf_header_bg.png' /><h1>" . $event->name . "</h1>
        <table><thead><tr><th>event_type</th><th>cvent_id</th><th>event_subtype</th><th>start_time</th><th>end_time</th><th>event_name</th><th>event_description</th><th>npi_specialties</th><th>npi_state</th></tr></thead>
        <tbody>";

        $html = $html . "<tr>";
        $html = $html . "<td>" . $event->event_type . "</td>";
        $html = $html . "<td>" . $event->cvent_id . "</td>";
        $html = $html . "<td>" . $event->event_subtype . "</td>";
        $html = $html . "<td>" . $event->start_time . "</td>";
        $html = $html . "<td>" . $event->end_time . "</td>";
        $html = $html . "<td>" . $event->event_name . "</td>";
        $html = $html . "<td>" . $event->event_description . "</td>";
        $html = $html . "<td>" . $event->npi_specialties . "</td>";
        $html = $html . "<td>" . $event->npi_state . "</td>";
        $html = $html . "</tr></tbody></table>";

        $html = $html . "<h1>Attendees</h1><table><thead><tr><th>Salutation</th><th>First Name</th><th>Last Name</th><th>Address</th><th>Country</th><th>City</th><th>State</th><th>Zip</th><th>Email</th><th>Phone Number</th><th>Practice Name</th><th>Specialty</th><th>NPI Number</th><th>Agreement</th></tr></thead>
        <tbody>";

        foreach ($attendees as $att) {
            $html .= "<tr>";
            $html .= "<td>" . $att->salutation . "</td>";
            $html .= "<td>" . $att->first_name . "</td>";
            $html .= "<td>" . $att->last_name . "</td>";
            $html .= "<td>" . $att->address . "</td>";
            $html .= "<td>" . $att->country . "</td>";
            $html .= "<td>" . $att->city . "</td>";
            $html .= "<td>" . $att->state . "</td>";
            $html .= "<td>" . $att->zip . "</td>";
            $html .= "<td>" . $att->email . "</td>";
            $html .= "<td>" . $att->phone_number . "</td>";
            $html .= "<td>" . $att->practice_name . "</td>";
            $html .= "<td>" . $att->specialty . "</td>";
            $html .= "<td>" . $att->npi_number . "</td>";
            $html .= "<td>" . $att->agreement . '</td>';
            $html .= "</tr>";
        }

        $html = $html . "</tbody></table></body></html>";

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('Letter', 'landscape');
        $dompdf->render();
        $pdf = $dompdf->output();
        $filename = wp_upload_dir()['basedir'] . '/' . bin2hex(openssl_random_pseudo_bytes(16)) . '.pdf';
        $filename = str_replace('/', DIRECTORY_SEPARATOR, $filename);
        file_put_contents($filename, $pdf);

        $attachments = array($filename);

        wp_mail($recipients, $event->name, "<h1>" . $event->name . "</h1>", $headers, $attachments);
    }

    private function SendEmailCSV(String $recipients, String $subject, Event $event, array $attendees)
    {
        $headers = "Content-Type: text/html; charset=UTF-8";
        $csv = 'salutation,first_name,last_name,address,country,city,state,zip,email,phone_number,practice_name,specialty,npi_number,agreement' . PHP_EOL;

        foreach ($attendees as $att) {

            $csv = $csv . '"' . $event->event_type . '",';
            $csv = $csv . '"' . $event->cvent_id . '",';
            $csv = $csv . '"' . $event->event_subtype . '",';
            $csv = $csv . '"' . $event->start_time . '",';
            $csv = $csv . '"' . $event->end_time . '",';
            $csv = $csv . '"' . $event->event_name . '",';
            $csv = $csv . '"' . $event->event_description . '",';
            $csv = $csv . '"' . $event->npi_specialties . '",';
            $csv = $csv . '"' . $event->npi_state . '",';
            $csv = $csv . '"' . $att->salutation . '",';
            $csv = $csv . '"' . $att->first_name . '",';
            $csv = $csv . '"' . $att->last_name . '",';
            $csv = $csv . '"' . $att->address . '",';
            $csv = $csv . '"' . $att->country . '",';
            $csv = $csv . '"' . $att->city . '",';
            $csv = $csv . '"' . $att->state . '",';
            $csv = $csv . '"' . $att->zip . '",';
            $csv = $csv . '"' . $att->email . '",';
            $csv = $csv . '"' . $att->phone_number . '",';
            $csv = $csv . '"' . $att->practice_name . '",';
            $csv = $csv . '"' . $att->specialty . '",';
            $csv = $csv . '"' . $att->npi_number . '",';
            $csv = $csv . '"' . $att->agreement . '"';
            $csv = $csv . PHP_EOL;
        }


        $filename = wp_upload_dir()['basedir'] . '/' . bin2hex(openssl_random_pseudo_bytes(16)) . '.csv';
        $filename = str_replace('/', DIRECTORY_SEPARATOR, $filename);
        file_put_contents($filename, $csv);

        $attachments = array($filename);

        wp_mail($recipients, $event->name, "<h1>" . $event->name . "</h1>", $headers, $attachments);
    }

    public function GetRegistrationURL(String $eventCode, String $first_name = '', $last_name = '',  $work_address = '', $work_city = '', $work_state_code = '', $work_postal_code = '')
    {

        $cvent = new CVENT();
        $token = $cvent->get_token();
        $eventID = $cvent->get_event_id($eventCode, $token);

        $url = 'https://www.cvent.com/Events/APIs/Reg.aspx';
        $data = array(
            'first_name' => $first_name,
            'last_name' => $last_name,
            'ecode' => $eventID,
            'work_address1' => $work_address,
            'work_city' => $work_city,
            'work_state_code' => $work_state_code,
            'work_postal_code' => $work_postal_code,
            'source_id' => null,
            'target' => 'Registration',
            'ref_id' => 'app'
        );

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, TRUE);

        $resp = curl_exec($curl);
        $info = curl_getinfo($curl, CURLINFO_REDIRECT_URL);
        return $info;
    }

    // Example usage to get Registration URL
    //
    // $notificationTool = new NotificationsTool();
    // $result = $notificationTool->GetRegistrationURL('0FDC2A4A-F403-4992-A488-C86B58075CBE');

}


// Example Usage:

// $notificationTool = new NotificationsTool();

// $attendees = array();

// $event = new Event();
// $event->name = "Test Event for SignIn Tool";
// $event->cventID = "3X24FKD";
// $event->startDate = new DateTime('2024-01-01 10:00am');
// $event->endDate = new DateTime('2024-01-01 2:00pm');
// $event->description = "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.";

// $attendee = new Attendee();
// $attendee->type = "EMPLOYEE";
// $attendee->lastName = "Doe 1";
// $attendee->firstName = "John";
// $attendee->title = "Mr";
// $attendee->company = "Atmosphere Apps";
// $attendee->email = "test1@atmoapps.com";
// $attendee->primaryAddress1 = "747 SW 2nd Ave";
// $attendee->city = "Gainesville";
// $attendee->state = "Florida";
// $attendee->postalCode = "32601";

// array_push($attendees, $attendee);
// array_push($attendees, $attendee);
// array_push($attendees, $attendee);
// array_push($attendees, $attendee);
// array_push($attendees, $attendee);

// $notificationTool->SendNotification(NotificationType::EMAIL_CSV, 'przemek@atmoapps.com', 'Przemek Chruscicki', $event, $attendees);