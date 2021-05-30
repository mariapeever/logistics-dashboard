<?php
/**
 * WC dashboard page
 */

/** WordPress Administration Bootstrap */
require_once( ABSPATH . 'wp-load.php' );
require_once( ABSPATH . 'wp-admin/admin.php' );
require_once( ABSPATH . 'wp-admin/admin-header.php' );

$orders = wc_get_orders_by_date_cat();

?>

<div class="wrap">
	<h1><?php _e('Logistics Dashboard','wcld'); ?></h1>
</div>

<div id="dashboard-widgets-wrap">
	<div id="dashboard-widgets" class="metabox-holder">
		<div id="postbox-container-1" class="postbox-container">
			<div class="meta-box">
				<div id="dashboard_right_now" class="postbox">
					<h2 class="hndle"><span><?php _e('Shipped orders', 'wcld'); ?></span></h2>
					<ul class="shipping-stats">
						<li class="three-days">
							<h3><a href="<?php echo admin_url('edit.php?s&post_status=all&post_type=shop_order&action=-1&m=0&_shipping_date=0-2','admin'); ?>"><?php echo __('Last 3 days', 'wcld'); ?></a></h3>
							<p><a href="<?php echo admin_url('edit.php?s&post_status=all&post_type=shop_order&action=-1&m=0&_shipping_date=0-2','admin'); ?>"><?php echo $orders['three-days']; ?></a></p>
						</li>
						<li class="week">
							<h3><a href="<?php echo admin_url('edit.php?s&post_status=all&post_type=shop_order&action=-1&m=0&_shipping_date=3-6','admin'); ?>"><?php echo __('4 to 7 days ago', 'wcld'); ?></a></h3>
							<p><a href="<?php echo admin_url('edit.php?s&post_status=all&post_type=shop_order&action=-1&m=0&_shipping_date=3-6','admin'); ?>"><?php echo $orders['week']; ?></a></p>
						</li>
						<li class="past">
							<h3><a href="<?php echo admin_url('edit.php?s&post_status=all&post_type=shop_order&action=-1&m=0&_shipping_date=7-2147483647','admin'); ?>"><?php echo __('More than 7 days ago', 'wcld'); ?></a></h3>
							<p><a href="<?php echo admin_url('edit.php?s&post_status=all&post_type=shop_order&action=-1&m=0&_shipping_date=7-2147483647','admin'); ?>"><?php echo $orders['past']; ?></a></p>
						</li>

					</ul>
				</div>
			</div>	
		</div>
	</div>
</div>

<?php include( ABSPATH . 'wp-admin/admin-footer.php' );