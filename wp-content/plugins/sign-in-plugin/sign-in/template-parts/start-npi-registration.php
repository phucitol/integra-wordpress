<h1 class="text-center"><?php echo $post_meta['event_name'][0]; ?></h1>
<div class="row mt-3 mb-5 pb-5">
    <p>
        Search using any combination of <b>first name</b>, <b>last name</b>, or <b>practice address</b> to access information on individuals matching the entered criteria.
    </p>
    <form class="mt-3 mb-5 npi-autocomplete" id="npi-lookup-form" autocomplete="off">
        <input type="hidden" name="action" value="npi_lookup" />
        <input type="hidden" name="limit" value="3" />
        <?php wp_nonce_field('npi_lookup', 'npi_lookup_nonce'); ?>
        <input type="text" name="s" id="npi-lookup-text" class="form-control" placeholder="Search" autocomplete="off">
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
            <div class="view-all">
                <a id="show-npi-lookup-full" href="#">View All Results</a>
            </div>
        </div>
    </form>
    <p class="text-center mt-1">
        <a class="no-npi-number" href="<?php echo site_url() . '/sign-in-event-complete-registration/?event='.get_the_title(); ?>">I don't have an NPI</a>
    </p>
</div>