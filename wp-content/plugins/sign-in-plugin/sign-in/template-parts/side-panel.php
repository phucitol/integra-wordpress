<?php
$open = empty($_GET['status']) ? ' active' : '';
$closed = (!empty($_GET['status']) && $_GET['status'] == 'closed') ? ' active' : '';
?>
<div class="side-panel">
    <a href="<?php echo get_site_url(null, 'sign-in-event'); ?>" class="btn btn-menu<?= $open; ?>">Open Events</a>
    <a href="<?php echo get_site_url(null, 'sign-in-event/?status=closed'); ?>" class="btn btn-menu<?= $closed; ?>">Closed Events</a>
    <a href="<?php echo get_site_url(null, 'manage-account'); ?>" class="btn btn-menu">Manage Account</a>
</div>