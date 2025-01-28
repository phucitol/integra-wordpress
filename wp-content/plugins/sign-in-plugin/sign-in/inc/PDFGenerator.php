<?php

require SIGN_IN_PLUGIN_PATH . 'vendor/autoload.php';


use Dompdf\Dompdf;

class PDFGenerator
{
    public function GenerateInstructions($post_id, $guid, $overwrite = false) { 
        $author_id = get_post_field('post_author', $post_id);
        $author_name = aasgnn_first_last( $author_id );

        // Get post meta
        $post_meta = get_post_meta($post_id);

        $event = new Event();

        foreach ($post_meta as $key => $value) {
            $event->$key = $value[0];
        }

        $src = ""; //TODO:create a placeholder image to output
        // Get the ID of the post's featured image.
        $thumbnail_id = get_post_thumbnail_id($post_id);

        // Check if the post has a featured image.
        if ($thumbnail_id) {
            // Get the image source (src) of the featured image.
            $image_src_array = wp_get_attachment_image_src($thumbnail_id, 'full');

            // Check if the image source array is valid.
            if (!empty($image_src_array[0])) {
                // Return the URL of the image.
                $src = $image_src_array[0];
            }
        }

        $footer_html = apply_filters( 'aasgnn_instructions_footer_html', '' );
        $header_html = apply_filters( 'aasgnn_instructions_header_html', '' );
        $style_css = '
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
                background-image:url(\'' . aasgnn_image( 'pdf_header_bg', 'png') . '\');
                background-size: 100% auto;
                padding: 2rem;
                margin-bottom: 4rem;
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
                position: fixed;
                max-width: 800px;
                width: 100%;
                bottom: 2rem;
                left: 50%;
                transform: translateX(-50%);
                border-top: 5px solid #004860;
                padding-top: 2rem;
                text-align: center;
            }';
        $style_css = apply_filters( 'aasgnn_instructions_style_css', $style_css );

        $html = '<!DOCTYPE html>
        <html lang="en">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Document</title>
            <style>
                ' . $style_css . '
            </style>
        </head>
        </body>
        <div class="letterhead">
            <header>
                ' . $header_html . '
            </header>
            <h1>' . $event->event_name . '</h1>
            <div class="event-details">
                <span>Sales Rep: ' . $author_name . '</span>
                <span>Start Date: ' . $event->start_time . '</span>
            </div>
            <div class="qr-code">
                <h3>QR Code Check In</h3>
                <img src="' . $src . '">
                <span>Check in URL: <a href="' . get_site_url(null, 'sign-in-registration/?event=' . $guid) . '">' . get_site_url(null, 'sign-in-registration/?event=' . $guid) . '</a></span>
            </div>
            <div class="instructions">
            Please scan the QR code or visit the Check in URL on your device to access the event check in.
            </div>

            <footer>
                ' . $footer_html . '
            </footer>
        </div>
        </html>';

        if ( $overwrite ) {
            $args = array(
                'post_parent' => $post_id,
                'post_type' => 'attachment',
                'posts_per_page' => -1 // Get all attachments
            );

            $attachments = get_posts( $args );

            // Check if there are any attachments
            foreach ( $attachments as $attachment ) {
                if ( strpos( $attachment->post_title, 'instructions' ) !== false ) {
                    $attach_id = $attachment->ID;
                }
            }

            if ( $attach_id ) {
                // Get the file path of the current attachment.
                $current_attachment_path = get_attached_file( $attach_id );

                // Delete the old file.
                if ( file_exists( $current_attachment_path ) ) {
                    unlink( $current_attachment_path );
                }
            }
        }

        $filename = $guid . '-instructions.pdf';

        $upload_dir = wp_upload_dir();

        //Get the file
        $dompdf = new Dompdf(array('enable_remote' => true));
        $dompdf->loadHtml($html);
        $dompdf->setPaper('Letter', 'portrait');
        $dompdf->render();
        $pdf = $dompdf->output();

        //Store in the filesystem.
        if (wp_mkdir_p($upload_dir['path'])) {
            $file = $upload_dir['path'] . '/' . $filename;
        } else {
            $file = $upload_dir['basedir'] . '/' . $filename;
        }

        file_put_contents($file, $pdf);

        // Check the type of file. We'll use this as the 'post_mime_type'.
        $filetype = wp_check_filetype(basename($filename), null);

        // Prepare an array of post data for the attachment.
        $attachment = array(
            'post_mime_type' => $filetype['type'],
            'post_title'     => sanitize_file_name($filename),
            'post_content'   => '',
            'post_status'    => 'inherit'
        );

        // Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
        if (!function_exists('wp_generate_attachment_metadata')) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
        }

        if ( $overwrite && $attach_id ) {
            update_attached_file( $attach_id, $file ); // Update file path.
            update_post_meta( $attach_id, '_wp_attached_file', $file ); // Update file path in post meta.
            wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $file ) ); // Update attachment metadata.
        } else {
            // Insert the attachment.
            $attach_id = wp_insert_attachment( $attachment, $file, $post_id );

            // Generate the metadata for the attachment, and update the database record.
            $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
            wp_update_attachment_metadata( $attach_id, $attach_data );
        }

        return $attach_id;
    }
}
