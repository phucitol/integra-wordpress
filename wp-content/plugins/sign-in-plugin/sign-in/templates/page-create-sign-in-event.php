<?php
get_header();
?>
<main id="primary" class="site-main aasgnn logged-in">
    <?php 
        include( SIGN_IN_PLUGIN_PATH.'template-parts/side-panel.php' );
    ?>
    <div class="main-content">
        <div class="content-header-panel">
            <h1>Create Event</h1>
            <div class="tabs ms-0 ms-md-5 mb-4 mb-md-0">
                <button class="tab active" aria-tab="planned-event"><img class="planned" src="<?php echo aasgnn_image( 'planned_icon_white', 'svg' ); ?>" />  Planned Event</button>
                <button class="tab" aria-tab="ad-hoc-event"><img class="ad-hoc" src="<?php echo aasgnn_image( 'ad_hoc_icon', 'svg' ); ?>" /> Ad Hoc Event</button>
            </div>
        </div>
        <div class="mt-3 tab-content">
            <form autocomplete="off" class="edit-event">
                <label>Event Information</label>
                <input type="text" class="form-control mb-3 required" placeholder="Event Name" name="event_name">
                <div class="event-dep planned-event">
                    <label>cventID</label>
                    <input type="text" class="form-control mb-3 event-dep planned-event required" placeholder="" name="cvent_id">
                </div>
                <?php
                do_action( 'aasgnn_add_event_html', array() );
                ?>
                <div class="two-columns mb-3">
                    <div class="pe-3">
                        <label>Start Date</label>
                        <input type="datetime-local" class="form-control me-3 required" name="start_time">
                        <div id="start-time-errors" class="time-errors">
                            <p>Start date can not be later than the end date.</p>
                        </div>
                    </div>
                    <div>
                        <label>End Date</label>
                        <input type="datetime-local" class="form-control required" name="end_time">
                        <div id="end-time-errors" class="time-errors">
                        </div>
                    </div>
                </div>
                <label>Event Description</label>
                <input type="text" class="form-control mb-3 required" name="event_description">
                <input type="hidden" name="local_time" value="" />
                <input type="hidden" name="event_type" value="planned-event" />
                <input type="hidden" name="action" value="create_sign_in_event" />
                <?php wp_nonce_field('create_sign_in_event', 'create_sign_in_event_nonce'); ?>
                <div class="button-container text-center mt-5">
                    <input type="submit" id="submit-event" class="btn btn-primary btn-medium" value="Create Event" />
                </div>
            </form>
        </div>
    </div>
</main><!-- #primary -->

<?php
//get_sidebar();
get_footer();