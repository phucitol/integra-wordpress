<?php

require SIGN_IN_PLUGIN_PATH  . 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class ConcurGenerator {

    public function CreateConcurXLSX(Event $event, array $attendees) {
        if (!function_exists('wp_generate_attachment_metadata')) {
            require_once(ABSPATH . "wp-admin" . '/includes/image.php');
            require_once(ABSPATH . "wp-admin" . '/includes/file.php');
            require_once(ABSPATH . "wp-admin" . '/includes/media.php');
        }

        $sheets = array(
            'Attendees' => array()
        );

        $blueprint = new Attendee();

        $column = 0;
        foreach ($blueprint->metadata as $key => $value) {
            $sheets['Attendees'][chr(65 + $column) . '1'] = $key;
            $column++;
        }

        $row = 2;
        foreach ($attendees as $att) {
            $column = 0;
            foreach ($att->metadata as $meta_key => $meta_value) {
                $sheets['Attendees'][chr(65 + $column) . $row] = $meta_value;
                $column++;
            }
            $row++;
        }

        $sheets = apply_filters( 'aasgnn_alter_concur_data', $sheets, $attendees );

        $spreadsheet = new Spreadsheet();

        $sheet_count = 0;
        foreach ($sheets as $sheet_name => $cells) {
            if ($sheet_count !== 0) {
                $spreadsheet->createSheet();
                // Zero based, so set the second tab as active sheet
                $spreadsheet->setActiveSheetIndex($sheet_count);
            }

            $activeWorksheet = $spreadsheet->getActiveSheet();
            $activeWorksheet->setTitle($sheet_name);

            foreach ($cells as $grid => $cell_value) {
                $activeWorksheet->setCellValue($grid, $cell_value);
            }

            $sheet_count++;
        }

        $filename = $event->metadata['guid'] . '.xlsx';

        $upload_dir = wp_upload_dir();

        //Store in the filesystem.
        if (wp_mkdir_p($upload_dir['path'])) {
            $file = $upload_dir['path'] . '/' . $filename;
        } else {
            $file = $upload_dir['basedir'] . '/' . $filename;
        }

        // Save the CSV content to the file
        $writer = new Xlsx($spreadsheet);
        $writer->save($file);

        // Check the type of file. We'll use this as the 'post_mime_type'.
        $filetype = wp_check_filetype(basename($filename), null);

        // Prepare an array of attachment data
        $attachment = array(
            'post_mime_type' => $filetype['type'],
            'post_title'     => sanitize_file_name($filename),
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
