<?php get_header(); ?>
<main id="primary" class="site-main aasgnn logged-out">
    <div class="registration">
        <div class="main-content">
            <?php
            $prereg = get_option('pre_reg_system', 'local');
            $event_query = new WP_Query(
                array(
                    'post_type' => 'sign-in-event',
                    'title' => $_GET['event'],
                    'posts_per_page' => 1
                )
            );

            if ($event_query->have_posts()) :
                while ($event_query->have_posts()) :
                    $event_query->the_post();
                    $post_meta = get_post_meta($post->ID);

                    if (get_post_status() === 'upcoming') : ?>
            <div class="content-header-panel">            
                <h1><?php echo $post_meta['event_name'][0]; ?></h1>
            </div>
            <div class="row mt-3 order-2">
                <div class="col">
                    <span class="me-4 d-block d-md-inline mb-2">Sales Rep: <?php echo aasgnn_first_last( get_the_author_meta( 'ID' ) ); ?></span>
                    <span>Start Date: <?php echo date('m/d/y', strtotime($post_meta['start_time'][0])); ?></span>
                </div>
            </div>
            <div class="register-panel order-1 order-md-5 mb-5">
                <h3 class="text-center mt-5">This Event Has Not Started Yet</h3>
                <?php 
                if ($post_meta['event_type'][0] === 'planned-event') {
                    if ( $prereg === 'cvent' ) {
                        $nt = new NotificationsTool();
                        $reg_url = $nt->GetRegistrationURL($post_meta['cvent_id'][0]);

                        if (!empty($reg_url)) {
                        ?>
                        <h3>Pre-Register by clicking the button below:</h3>
                        <div class="button-container text-center mt-5">
                            <a href="<?php echo $reg_url; ?>" class="btn btn-primary btn-medium">Pre-Register Now</a>
                        </div>
                        <?php 
                        }
                    } elseif ( $prereg === 'local' ) {
                        ?>
                        <form id="pre-registration" class="reg-form" autocomplete="off" method="POST">
                            <?php
                            RegistrationForm::createForm( 'pre_reg' );
                            ?>
                            <input type="hidden" name="id" value="<?php echo $post->ID; ?>" />
                            <input type="hidden" name="action" value="pre_register_attendee" />
                            <?php wp_nonce_field('pre_register_attendee', 'pre_register_attendee_nonce'); ?>
                            <div class="button-container text-center mt-3">
                                <input type="submit" name="submit-pre-registration" id="submit-pre-registration" class="btn btn-primary btn-medium" value="Pre-register" aria-guid="<?= get_the_title(); ?>" />
                            </div>
                        </form>
                        <?php
                    }
                }
                ?>
            </div>
            <div id="message-overlay">
                <div id="message-box">
                    Pre-registration successful.
                </div>
            </div>
                <?php 
                elseif (get_post_status() === 'open') : 
                ?>
            <?php
            if ( $post_meta['event_type'][0] === 'planned-event' ) {
                include( SIGN_IN_PLUGIN_PATH . 'template-parts/start-planned-registration.php' );
            } else if ( $post_meta['event_type'][0] === 'ad-hoc-event' ) {
                include( SIGN_IN_PLUGIN_PATH . 'template-parts/start-npi-registration.php' );
            }
            ?>
            <form id="registrant-info" action="<?php echo site_url() . '/sign-in-event-complete-registration/?event='.get_the_title(); ?>" method="POST">
                <?php
                RegistrationForm::createForm( 'pre_reg', true );
                ?>
            </form>
                <?php 
                elseif (get_post_status() === 'closed') : 
                ?>
            <h3 class="d-block d-md-none">This Event Has Closed</h3>
            <h1><?php echo $post_meta['event_name'][0]; ?></h1>
            <div class="row mt-0 mt-md-5">
                <div class="col">
                    <span class="me-4 d-block d-md-inline mb-2">Sales Rep: <?php echo aasgnn_first_last( get_the_author_meta( 'ID' ) ); ?></span>
                    <span class="me-4">Start Date: <?php echo date('m/d/y', strtotime($post_meta['start_time'][0])); ?></span>
                    <span class="in-progress">Closed</span>
                </div>
            </div>
            <h3 class="mt-5 d-none d-md-block">This Event Has Closed</h3>
                <?php 
                endif;
            endwhile;
            endif;
            wp_reset_postdata(); ?>
        </div><!-- .main-content -->
        <div id="rep-login-link">
            <a href="<?php echo site_url() . '/sign-in-event/'; ?>">Rep Login</a>
        </div>
    </div><!-- .main -->
</main><!-- #primary -->
<?php get_footer(); ?>