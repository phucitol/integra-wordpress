<?php
get_header();

$status = !empty($_GET['status']) ? ucwords($_GET['status']) : 'Open';
?>

<main id="primary" class="site-main aasgnn logged-in">
    <?php 
        include( SIGN_IN_PLUGIN_PATH.'template-parts/side-panel.php' );
    ?>
    <div class="main-content">
        <a href="<?php echo site_url() . '/create-sign-in-event/'; ?>" class="btn btn-primary btn-large create-event-mobile">Create Event</a>
        <div>
            <h1 class="page-title"><?= $status; ?> Events</h1>
        </div><!-- .page-header -->
        <?php if ( have_posts() ) : ?>
            <table class="event-list">
                <thead>
                    <tr>
                        <th>&nbsp;</th>
                        <th>&nbsp;</th>
                        <th>Event</th>
                        <th>Sales Rep Name</th>
                        <th>Start Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while (have_posts()) : the_post();
                        $post_status = get_post_status( get_the_ID() );
                        $current_meta = get_post_meta( get_the_ID() );
                        $icon = ( $current_meta['event_type'][0] === 'planned-event' ) ? 'planned_icon' : 'ad_hoc_icon';
                        $live = ( $post_status === 'open' ) ? '<img src="'.aasgnn_image('live', 'svg').'" />' : '';
                        ?>
                        <tr class="event-row" aria-url="<?php echo get_site_url(null, 'sign-in-event/' . get_the_title() . '/'); ?>">
                            <td><?php echo $live; ?></td>
                            <td><img src="<?php echo aasgnn_image($icon, 'svg'); ?>" /></td>
                            <td><?php echo $current_meta['event_name'][0] ?></td>
                            <td><?php echo aasgnn_first_last( get_the_author_meta( 'ID' ) ); ?></td>
                            <td><?php echo date('m/d/y', strtotime($current_meta['start_time'][0])) ?></td>
                            <td><img src="<?php echo aasgnn_image('circle_arrow', 'svg'); ?>" class="circle-arrow-button" /></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php
        else :
        ?>
            <p>You have no <?= strtolower($status); ?> events.</p>
        <?php
        endif;
        ?>
    </div>
</main><!-- #primary -->

<?php
//get_sidebar();
get_footer();
?>