            <div class="main-content">
                <div class="content-header-panel d-block">
                    <h1 class="d-inline"><?php echo $post_meta['event_name'][0]; ?></h1>
                    <a href="<?php echo get_site_url(null, 'edit-sign-in-event/?event=' . get_the_title()); ?>" class="edit-event d-none d-md-inline ms-3">Edit Event</a>
                </div>
                <div class="button-container d-none d-md-flex pause-container">
                    <div class="my-4 me-3">Check in URL: <a href="<?php echo wp_logout_url(get_site_url(null, 'sign-in-event-start-registration/?event=' . get_the_title())); ?>"><?php echo get_site_url(null, 'sign-in-event-start-registration/?event=' . get_the_title()); ?></a></div>
                    <form class="begin-check-in">
                        <input type="hidden" name="id" value="<?php echo $event_id; ?>" />
                        <input type="hidden" name="action" value="begin_check_in">
                        <?php wp_nonce_field('begin_check_in', 'begin_check_in_nonce'); ?>
                        <input type="submit" class="btn btn-primary btn-medium" value="Begin Check In">
                    </form>
                </div>

                <div class="row mt-3">
                    <div class="col">
                        <span class="me-4 d-block d-md-inline mb-2">Sales Rep: <?php echo aasgnn_first_last(get_the_author_meta('ID')); ?></span>
                        <span>Start Date: <?php echo date('m/d/y', strtotime($post_meta['start_time'][0])) ?></span>
                    </div>
                </div>
                <div class="row mt-3">
                    <p>
                        Attendees may scan the QR code or visit the Check in URL on their device to access event check in. Event QR code and Check in URL may be distributed via email by selecting the share link below the Check in URL. Event QR code and Check in URL may be distributed physically by downloading and printing PDF Check in Instructions.
                    </p>
                </div>
                <div class="row mt-3 two-columns">
                    <div class="col-12 col-md-6 text-center mb-3">
                        <h4 class="mb-3">QR Code Check In</h4>
                        <img src="<?php echo $src; ?>" class="d-block mx-auto qr-code">
                        <div class="fw-bold mt-3"><a href="#" class="share-sign-in-event">Share QR Code</a></div>
                    </div>
                    <div class="col-12 col-md-6 text-center mb-3">
                        <h4 class="mb-3">Check In Instructions</h4>
                        <img src="<?php echo aasgnn_image( 'instructions_pdf', 'png' ); ?>" class="d-block mx-auto pdf-preview">
                        <div class="fw-bold mt-3">
                            <a href="<?php echo $instructions_pdf_url; ?>" class="me-3 d-block d-md-inline mb-3" download>Download PDF</a>
                        </div>
                    </div>
                </div>
                <?php
                $args = array(
                    'post_parent' => get_the_ID(),
                    'post_type'   => 'any',  // Replace with your custom post type if needed
                    'posts_per_page' => -1,  // Get all child posts
                    'post_status' => 'publish'
                );

                $child_query = new WP_Query($args);

                if ($child_query->have_posts()) : ?>

                    <h1>Summary</h1>
                    <table class="event-list mt-3">
                        <thead>
                            <tr>
                                <th>Attendee</th>
                                <th>NPI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            while ($child_query->have_posts()) :
                                $child_query->the_post();
                                $first_name = get_post_meta(get_the_ID(), 'FirstName', true);
                                $last_name = get_post_meta(get_the_ID(), 'LastName', true);
                                $npi_number = get_post_meta(get_the_ID(), 'npi_number', true);
                                echo '<tr><td>' . $first_name . " " . $last_name . '</td><td>' . $npi_number . '</td></tr>';
                            endwhile; ?>
                        </tbody>
                    </table>
                <?php
                endif;
                wp_reset_postdata(); // Reset the global post data
                ?>
            </div>
