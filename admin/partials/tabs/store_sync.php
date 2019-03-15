<?php
$mailchimp_total_products = $mailchimp_total_orders = 0;
$store_id = mailchimp_get_store_id();
$product_count = mailchimp_get_product_count();
$order_count = mailchimp_get_order_count();
$store_syncing = false;
$last_updated_time = get_option('mailchimp-woocommerce-resource-last-updated');
$sync_started_at = get_option('mailchimp-woocommerce-sync.started_at');
if (!empty($sync_started_at)) {
    $sync_started_at = mailchimp_date_local($sync_started_at);
} else {
    $sync_started_at = new \DateTime();
}

$sync_completed_at = get_option('mailchimp-woocommerce-sync.completed_at');
if (!empty($sync_completed_at)) {
    $sync_completed_at = mailchimp_date_local($sync_completed_at);
} else {
    $sync_completed_at = false;
}

$account_name = 'n/a';
$mailchimp_list_name = 'n/a';
if (!empty($last_updated_time)) {
    $last_updated_time = mailchimp_date_local($last_updated_time);
}

// if we have a transient set to start the sync on this page view, initiate it now that the values have been saved.
// then redirect back to the sync tab to initiate the JavaScript.
if ((bool) get_site_transient('mailchimp_woocommerce_start_sync', false)) {
    MailChimp_WooCommerce_Admin::startSync();
    wp_redirect('admin.php?page=mailchimp-woocommerce&tab=sync');
}

if (($mailchimp_api = mailchimp_get_api()) && ($store = $mailchimp_api->getStore($store_id))) {
    $store_syncing = $store->isSyncing();
    if (($account_details = $handler->getAccountDetails())) {
        $account_name = $account_details['account_name'];
    }
    try {
        $products = $mailchimp_api->products($store_id, 1, 1);
        $mailchimp_total_products = $products['total_items'];
        if ($mailchimp_total_products > $product_count) $mailchimp_total_products = $product_count;
    } catch (\Exception $e) { $mailchimp_total_products = 0; }
    try {
        $orders = $mailchimp_api->orders($store_id, 1, 1);
        $mailchimp_total_orders = $orders['total_items'];
        if ($mailchimp_total_orders > $order_count) $mailchimp_total_orders = $order_count;
    } catch (\Exception $e) { $mailchimp_total_orders = 0; }
    $mailchimp_list_name = $handler->getListName();
}
?>

<input type="hidden" name="mailchimp_active_settings_tab" value="store_sync"/>

<h2 style="padding-top: 1em;">Sync Information</h2>

<?php if ($sync_started_at && !$sync_completed_at): ?>
    <p><strong>Initial Sync:</strong> <i>In Progress</i></p>
<?php endif; ?>

<?php if ($last_updated_time): ?>
    <p><strong>Last Updated:</strong> <i id="mailchimp_last_updated"><?php echo $last_updated_time->format('D, M j, Y g:i A'); ?></i></p>
<?php endif; ?>

<p><strong>Account Connected:</strong> <span id="mailchimp_account_connected"><?php echo $account_name; ?></span></p>
<p><strong>List Connected:</strong> <span id="mailchimp_list_name"><?php echo $mailchimp_list_name; ?></span></p>
<p><strong>Products Synced:</strong> <span id="mailchimp_product_count"><?php echo $mailchimp_total_products; ?></span></p>
<p><strong>Orders Synced:</strong> <span id="mailchimp_order_count"><?php echo $mailchimp_total_orders; ?></span></p>

<?php if($mailchimp_api && (!$store_syncing || isset($_GET['resync']) && $_GET['resync'] === '1')): ?>
    <h2 style="padding-top: 1em;">Advanced</h2>
    <p id="resync_data_help_text">
        You can resync your list at any time without losing any of your e-commerce data.
    </p>
    <?php submit_button('Resync', 'primary','submit', TRUE); ?>
<?php endif; ?>
