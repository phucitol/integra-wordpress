<?php
get_header(); 

$event_query = new WP_Query(
    array(
        'post_type' => 'sign-in-event',
        'title' => $_GET['event'],
        'posts_per_page' => 1
    )
);

$noNPI = false;
if ( !empty( $_POST['no-npi-number'] ) && $_POST['no-npi-number'] == true ) {
    $noNPI = true;
}

if ($event_query->have_posts()) :
    while ($event_query->have_posts()) :
        $event_query->the_post();
        $post_meta = get_post_meta($post->ID);
?>
<main id="primary" class="site-main aasgnn logged-out">
    <div class="registration complete-reg">
        <div class="main-content">
            <div class="content-header-panel">
                <h1><?php echo $post_meta['event_name'][0]; ?></h1>
            </div>
            <div class="hosted-by">
                <div>
                    <h3>Hosted by <?php echo aasgnn_first_last( get_the_author_meta( 'ID' ) ); ?></h3>
                    <h3><?php echo date('m-d-Y h:i A', strtotime($post_meta['start_time'][0])); ?></h3>
                </div>
                <a href="#" id="look-up-npi-full" class="btn btn-primary">Look Up NPI</a>
            </div>
            <p class="mt-3">
                Please use “Look Up NPI” to auto populate the Registration Form fields with your information. If you do not have an NPI# please fill out the form manually. Before submitting the form, be sure to check the signature box at the end of the form.
            </p>
            <div class="mt-3">
                <form id="complete-registration" class="reg-form" autocomplete="off" method="POST">
                    <input type="hidden" name="look-up-npi-action" value="<?php echo site_url() . '/sign-in-npi-lookup/?event='.get_the_title(); ?>" />
                    <input type="hidden" name="doing-npi-look-up" value="false" />
                    <input type="hidden" name="search" />
                    <?php
                    RegistrationForm::createForm( 'registration' );
                    ?>
                    <input type="hidden" name="cventDataChanged" value="No" />
                    <input type="hidden" name="id" value="<?php echo $post->ID; ?>" />
                    <input type="hidden" name="action" value="register_attendee" />
                    <?php wp_nonce_field('register_attendee', 'register_attendee_nonce'); ?>
                    <div class="button-container text-center mt-3">
                        <input type="submit" name="submit-registration" id="submit-registration" class="btn btn-primary btn-medium" value="Check In" aria-guid="<?= get_the_title(); ?>" />
                    </div>
                </form>
                <p class="text-center mt-4">
                    <a class="start-over" href="<?php echo site_url() . '/sign-in-event-start-registration/?event='.get_the_title(); ?>">Start Over</a>
                </p>
            </div>
            <div id="message-overlay">
                <div id="message-box">
                    Registration successful.
                </div>
            </div>
        </div><!-- .main-content -->
        <div id="rep-login-link">
            <a href="<?php echo site_url() . '/sign-in-event/'; ?>">Rep Login</a>
        </div>
    </div><!-- .main -->
</main><!-- #primary -->
<?php get_footer(); ?>
<?php
    endwhile;
endif;
wp_reset_postdata(); ?>