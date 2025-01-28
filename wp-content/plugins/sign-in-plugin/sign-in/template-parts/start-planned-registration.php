<h1 class="text-center"><?php echo $post_meta['event_name'][0]; ?></h1>
<div class="row mt-3 mb-5 pb-5">
    <p>
        Welcome. Please enter your last name, first name, or email address and select your details from the list of pre-registered attendees. If you are not pre-registered for this event please select the “I am not pre-registered” link to do so.
    </p>
    <form class="mt-3 mb-5 npi-autocomplete" id="planned-lookup" autocomplete="off">
        <input type="hidden" name="action" value="pre_reg_lookup" />
        <input type="hidden" name="pre_reg_system" value="<?php echo get_option('pre_reg_system', 'local'); ?>" />
        <input type="hidden" name="id" value="<?php echo $post->ID; ?>" />
        <?php wp_nonce_field('pre_reg_lookup', 'pre_reg_lookup_nonce'); ?>
        <input type="text" name="s" id="pre-reg-lookup-text" class="form-control" placeholder="Enter your last name, first name, or email" autocomplete="off">
        <div class="autocomplete-container">
            <table class="attendee-suggestions">
                <thead>
                    <tr>
                        <td>Name</td>
                        <td>Email</td>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </form>
    <p class="text-center mt-1">
        <a href="<?php echo site_url() . '/sign-in-event-complete-registration/?event='.get_the_title(); ?>">I am not pre-registered.</a>
    </p>
</div>