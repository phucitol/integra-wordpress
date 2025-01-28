<?php

class CSVGenerator
{
    public function CreateAttendanceCSV( Event $event, array $attendees ) {
        if (!function_exists('wp_generate_attachment_metadata')) {
            require_once(ABSPATH . "wp-admin" . '/includes/image.php');
            require_once(ABSPATH . "wp-admin" . '/includes/file.php');
            require_once(ABSPATH . "wp-admin" . '/includes/media.php');
        }
        $csv = "";

        $att_blueprint = new Attendee();

        $event_blueprint = new Event();

        foreach ($att_blueprint->metadata as $key => $value) {
            $csv .= $key . ',';
        }

        foreach ($event_blueprint->metadata as $key => $value) {
            $csv .= $key . ',';
        }
        $csv = substr($csv, 0, -1);
        $csv .= PHP_EOL;

        foreach ($attendees as $att) {
            foreach ($att->metadata as $meta_key => $meta_value) {
                $csv .= '"' . $meta_value . '",';
            }

            foreach ($event->metadata as $meta_key => $meta_value) {
                $csv .= '"' . $meta_value . '",';
            }
            $csv = substr($csv, 0, -1);
            $csv .= PHP_EOL;
        }

        $filename = $event->metadata['guid'] . '.csv';

        $upload_dir = wp_upload_dir();

        //Store in the filesystem.
        if ( wp_mkdir_p( $upload_dir['path'] ) ) {
            $file = $upload_dir['path'] . '/' . $filename;
        }
        else {
            $file = $upload_dir['basedir'] . '/' . $filename;
        }

        // Save the CSV content to the file
        file_put_contents($file, $csv);

        // Check the type of file. We'll use this as the 'post_mime_type'.
        $filetype = wp_check_filetype( basename( $filename ), null );

        // Prepare an array of attachment data
        $attachment = array(
            'post_mime_type' => $filetype['type'],
            'post_title'     => sanitize_file_name( $filename ),
            'post_content'   => '',
            'post_status'    => 'inherit'
        );

        // Insert the attachment into the WordPress Media Library and attach it to the post
        $attach_id = wp_insert_attachment($attachment, $file, $event->id);

        // Generate the metadata for the attachment and update the database record
        $attach_data = wp_generate_attachment_metadata($attach_id, $file);
        wp_update_attachment_metadata($attach_id, $attach_data);

        return $attach_id;
    }
}