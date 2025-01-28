<?php
get_header();

$event_query = new WP_Query(
    array(
        'post_type' => 'sign-in-event',
        'title' => $_GET['event'],
        'posts_per_page' => 1
    )
);

$search = '';
if ( !empty( $_POST['search'] ) ) {
    $search = $_POST['search'];
}

if ($event_query->have_posts()) :
    while ($event_query->have_posts()) :
        $event_query->the_post();
        $post_meta = get_post_meta($post->ID); ?>
<main id="primary" class="site-main aasgnn logged-out">
    <div class="registration">
        <div class="main-content">
            <div class="content-header-panel">
                <h1 class="text-center"><?php echo $post_meta['event_name'][0]; ?></h1>
            </div>
            <div class="row mt-3">
                <form class="mt-3 mb-2" id="npi-lookup-full-form" autocomplete="off">
                    <div>
                        <input type="hidden" name="action" value="npi_lookup" />
                        <input type="hidden" name="limit" value="499" />
                        <?php wp_nonce_field('npi_lookup', 'npi_lookup_nonce'); ?>
                        <input type="text" class="form-control" name="s" id="npi-lookup-full-text" placeholder="Search using any combination of first name, last name, or address to access information on individuals matching the entered criteria." value="<?= $search; ?>">
                    </div>
                    <div class="autocomplete-container">
                        <table class="attendee-suggestions">
                            <thead>
                                <tr>
                                    <td>Name</td>
                                    <td>NPI</td>
                                    <td>Primary Practice Address</td>
                                    <td>Primary Taxonomy</td>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </form>
                <form id="registrant-info" action="<?php echo site_url() . '/sign-in-event-complete-registration/?event='.get_the_title(); ?>" method="POST">
                    <?php
                    RegistrationForm::createForm( 'npi_lookup', true );
                    ?>
                </form>
                <div class="button-container text-center mt-3 mt-md-5">
                    <a href="#" class="btn btn-primary btn-medium" style="display:none;">Next</a>
                </div>
                <p class="text-center mt-1">
                    <a class="no-npi-number" href="#">I don't have an NPI</a>
                </p>
                <p class="text-center mt-4">
                    <a class="start-over" href="<?php echo site_url() . '/sign-in-event-start-registration/?event='.get_the_title(); ?>">Start Over</a>
                </p>
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