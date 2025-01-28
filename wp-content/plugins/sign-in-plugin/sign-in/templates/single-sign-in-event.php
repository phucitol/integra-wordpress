<?php
get_header();

if (have_posts()) :
    while (have_posts()) :
        the_post();
        $event_id = get_the_ID();
        $post_meta = get_post_meta($event_id);
        $post_status = get_post_status();

        $src = ""; //TODO:create a placeholder image to output
        // Get the ID of the post's featured image.
        $thumbnail_id = get_post_thumbnail_id($event_id);

        // Check if the post has a featured image.
        if ($thumbnail_id) {
            // Get the image source (src) of the featured image.
            $image_src_array = wp_get_attachment_image_src($thumbnail_id, 'full');

            // Check if the image source array is valid.
            if ($image_src_array) {
                // Return the URL of the image.
                $src = $image_src_array[0];
            }
        }

        $instructions_pdf_url = "";
        $concur_xls_url = "";
        // Get all attachments from the post
        $args = array(
            'post_parent' => $event_id,
            'post_type' => 'attachment',
            'posts_per_page' => -1 // Get all attachments
        );

        $attachments = get_posts($args);

        // Check if there are any attachments
        foreach ($attachments as $attachment) {
            if (strpos($attachment->post_title, 'instructions') !== false) {
                $instructions_pdf_url = wp_get_attachment_url($attachment->ID);
            } elseif (strpos($attachment->post_title, 'xls') !== false) {
                $concur_xls_url = wp_get_attachment_url($attachment->ID);
            }
        }

        $csv_url = "";
        $args = array(
            'post_parent' => $event_id,
            'post_type' => 'attachment',
            'post_mime_type' => 'text/csv',
            'posts_per_page' => -1
        );

        $attachments = get_posts($args);

        if (count($attachments) > 0) {
            $csv_url = wp_get_attachment_url($attachments[0]->ID);
        }

    endwhile;
    wp_reset_postdata();
endif;
?>
<main id="primary" class="site-main aasgnn logged-in">
    <?php 
        include( SIGN_IN_PLUGIN_PATH.'template-parts/side-panel.php' );
        include( SIGN_IN_PLUGIN_PATH.'template-parts/'.get_post_type().'-'.get_post_status().'.php' );
    ?>
    <div id="message-overlay" style="">
        <div id="message-box">
            <div class="text-right mt-3">
                <a href="#" class="close-share-sign-in-event">Close</a>
            </div>
            <div class="text-center">
                <h1>Share by Email or Copy Link</h1>
            </div>
            <form id="share-sign-in-event-form" autocomplete="off">
                <input type="hidden" name="id" value="<?php echo get_the_ID(); ?>" />
                <input type="hidden" name="event_name" value="<?php echo get_the_title(); ?>" />
                <input type="hidden" name="action" value="share_sign_in_event">
                <?php wp_nonce_field('share_sign_in_event', 'share_sign_in_event_nonce'); ?>
                <h4>Enter emails, Separated by a comma</h4>
                <textarea name="recipients" class="form-control"></textarea>
                <h4>Share Link</h4>
                <textarea readonly class="form-control"><?php echo get_site_url(null, 'sign-in-overview/?event=' . get_the_title()); ?></textarea>
                <div class="button-container text-center mt-4">
                    <input type="submit" class="btn btn-primary btn-medium" value="Send" />
                </div>
            </form>
        </div>
    </div>
</main><!-- #primary -->

<?php
//get_sidebar();
get_footer();
?>
