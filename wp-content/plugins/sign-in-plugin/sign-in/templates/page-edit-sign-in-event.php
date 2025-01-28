<?php
$event_query = new WP_Query(
    array(
        'post_type' => 'sign-in-event',
        'title' => $_GET['event'],
        'posts_per_page' => 1,
        'post_status' => 'any'
    )
);

if ($event_query->have_posts()) :
    while ($event_query->have_posts()) :
        $event_query->the_post();
        $post_meta = get_post_meta($post->ID);
?>
<?php 
get_header(); 
?>
<main id="primary" class="site-main aasgnn logged-in">
    <?php 
        include( SIGN_IN_PLUGIN_PATH.'template-parts/side-panel.php' );
    ?>
    <div class="main-content">
        <div class="content-header-panel">
            <h1>Edit Event</h1>
            <div class="tabs ms-0 ms-md-5 mb-4 mb-md-0">
                <?php 
                if ( $post_meta['event_type'][0] === 'planned-event' ) {
                    $planned_class = ' active';
                    $planned_img = aasgnn_image( 'planned_icon_white', 'svg' );
                    $adhoc_class = '';
                    $adhoc_img = aasgnn_image( 'ad_hoc_icon', 'svg' );
                } else if ( $post_meta['event_type'][0] === 'ad-hoc-event' ) {
                    $planned_class = '';
                    $planned_img = aasgnn_image( 'planned_icon', 'svg' );
                    $adhoc_class = ' active';
                    $adhoc_img = aasgnn_image( 'ad_hoc_icon_white', 'svg' );
                }

                ?>
                <button class="tab<?php echo $planned_class; ?>" aria-tab="planned-event"><img class="planned" src="<?= $planned_img; ?>" />  Planned Event</button>
                <button class="tab<?php echo $adhoc_class; ?>" aria-tab="ad-hoc-event"><img class="ad-hoc" src="<?= $adhoc_img; ?>" />  Ad Hoc Event</button>
            </div>
        </div>
        <div class="mt-3 tab-content">
            <form autocomplete="off" class="edit-event">
                <label>Event Information</label>
                <input type="text" class="form-control mb-3 required" placeholder="Event Name" name="event_name" value="<?php echo $post_meta['event_name'][0]; ?>">
                <div class="event-dep planned-event">
                    <label>cventID</label>
                    <input type="text" class="form-control mb-3 event-dep planned-event " name="cvent_id" value="<?php echo (!empty($post_meta['cvent_id'][0])) ? $post_meta['cvent_id'][0] : ''; ?>">
                </div>
                <?php
                do_action( 'aasgnn_add_event_html', $post_meta );
                ?>
                <div class="two-columns mb-3">
                    <div class="pe-3">
                        <label>Start Date</label>
                        <input type="datetime-local" class="form-control required" name="start_time" value="<?php echo $post_meta['start_time'][0]; ?>">
                        <div id="start-time-errors" class="time-errors">
                            <p>Start date can not be later than the end date.</p>
                        </div>
                    </div>
                    <div>
                        <label>End Date</label>
                        <input type="datetime-local" class="form-control required" name="end_time" value="<?php echo $post_meta['end_time'][0]; ?>">
                        <div id="end-time-errors" class="time-errors">
                        </div>
                    </div>
                </div>

                <label>Event Description</label>
                <input type="text" class="form-control mb-3 required" name="event_description" value="<?php echo $post_meta['event_description'][0]; ?>">
                <input type="hidden" name="local_time" value="" />
                <input type="hidden" name="event_type" value="<?php echo $post_meta['event_type'][0]; ?>" />
                <input type="hidden" name="action" value="edit_sign_in_event" />
                <?php wp_nonce_field('edit_sign_in_event', 'edit_sign_in_event_nonce'); ?>
                <input type="hidden" name="id" value="<?php echo $post->ID; ?>" />
                <div class="button-container text-center mt-3">
                    <input type="submit" id="submit-edit-event" class="btn btn-primary btn-medium" value="Update" />
                </div>
                <div class="button-container text-center mt-5">
                    <input type="button" id="cancel-edit-event" class="btn btn-primary btn-red btn-medium" value="Cancel" />
                </div>
            </form>
        </div>
    </div>
</main><!-- #primary -->
<?php get_footer();
    endwhile;
endif;
wp_reset_postdata(); ?>