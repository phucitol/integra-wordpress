            <div class="main-content">
                <div class="content-header-panel d-block">
                    <h1 class="d-inline"><?php echo $post_meta['event_name'][0]; ?></h1>
                </div>
                <div class="row mt-3">
                    <div class="col">
                        <span class="me-4 d-block d-md-inline mb-2">Sales Rep: <?php echo aasgnn_first_last(get_the_author_meta('ID')); ?></span>
                        <span>Start Date: <?php echo date('m/d/y', strtotime($post_meta['start_time'][0])) ?></span>
                    </div>
                </div>
                <div class="row mt-3">
                    <p>
                        This event has been closed and the reports have been sent. See below summary of the attendees. Please download the Attendance Report and Concur Report for your records.
                    </p>
                </div>
                <h4 class="mt-5">Summary</h4>
                <?php
                $args = array(
                    'post_parent' => get_the_ID(),
                    'post_type'   => 'any',  // Replace with your custom post type if needed
                    'posts_per_page' => -1,  // Get all child posts
                    'post_status' => 'publish'
                );

                $child_query = new WP_Query($args);

                if ($child_query->have_posts()) : ?>

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
                                $first_name = get_post_meta(get_the_ID(), 'first_name', true);
                                $last_name = get_post_meta(get_the_ID(), 'last_name', true);
                                $npi_number = get_post_meta(get_the_ID(), 'npi_number', true);
                                echo '<tr><td>' . $first_name . " " . $last_name . '</td><td>' . $npi_number . '</td></tr>';
                            endwhile; ?>
                        </tbody>
                    </table>
                    <div class="button-container mt-3 text-center">
                        <a class="btn btn-primary" style="width:25%" href="<?php echo $csv_url; ?>" class="mt-3">Download Attendance Report</a>
                    </div>
                <?php
                else : ?>
                    <p>No attendees have checked in.</p>
                <?php
                endif;
                wp_reset_postdata(); // Reset the global post data

                if (get_option('concur_active', false)) :
                ?>
                <div class="button-container mt-3 text-center">
                    <a class="btn btn-primary" style="width:25%" href="<?php echo $concur_xls_url; ?>" download>Download Concur Report</a>
                </div>
                <?php endif; ?>
            </div>
