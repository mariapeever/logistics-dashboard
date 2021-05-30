<?php
/*
 * Plugin Name: Logistics Dashboard
 * Plugin URI:
 * Description: Logistics Dashboard provides support for WooCommerce shipping reports.
 * Version: 1.0
 * Author: Mariya Peeva
 * Author URI: https://mariyapeeva.com
 * Text Domain: wc_logistics_dashboards
*/

// Prevent direct access to files
if (!defined('ABSPATH'))
{
    exit;
}

/* 
 * Load styles
 */
function wc_add_logistics_dashboard_styles()
{
    global $pagenow;
    if ($pagenow != 'admin.php') {
        return;
    }
     
    // loading css
    wp_register_style( 'logistics-dashboard', plugins_url('css/logistics-dashboard.css',__FILE__ ), false, '1.0.0' );
    wp_enqueue_style( 'logistics-dashboard' );

}
 
add_action( 'admin_enqueue_scripts', 'wc_add_logistics_dashboard_styles' );

/* 
 * Logistics Dashboard
 */
class WC_Logistics_Dashboard 
{

	/**
	 * Default constructor
	 */
	function __construct()
	{
		
		add_action( 'admin_menu', array( &$this,'wc_logistics_dashboard_register_menu') );
		add_action( 'init', array($this,'wc_register_shipped_order_status'));
		add_filter( 'wc_order_statuses', array($this,'wc_add_shipped_to_order_statuses'));
		add_action( 'woocommerce_admin_order_data_after_order_details', array($this,'wc_shipping_date_meta') );
	} 

	/**
	 * Register menu
	 * @return void
	 */
	function wc_logistics_dashboard_register_menu() 
	{
		add_submenu_page( 'woocommerce', 'Logistics Dashboard', 'Logistics Dashboard', 'manage_options', 'logistics-dashboard', array( &$this,'wc_logistics_dashboard_create_dashboard') ); 
	}

	/**
	 * Create dashboard
	 * @return void
	 */
	function wc_logistics_dashboard_create_dashboard() 
	{
		include_once( 'logistics_dashboard.php' );
	}

	/**
	 * Register Shipped status
	 * @return void
	 */
	function wc_register_shipped_order_status()
	{
		register_post_status( 'wc-shipped', array(
			'label' 					=> _x('Shipped','wcld'),
			'public' 					=> true,
			'exclude_from_search' 		=> false,
			'show_in_admin_all_list' 	=> true,
			'show_in_admin_status_list' => true,
			'label_count' 				=> _n_noop( 'Shipped <span class="count">(%s)</span>', 'Shipped <span class="count">(%s)</span>' )
	    ));
	}

	/**
	 * Adds new Order status - Shipped in Order statuses
	 * @param $order_statuses the order statuses
	 * @return $new_order_statuses the order statuses with shipped status included
	 */  
	function wc_add_shipped_to_order_statuses($order_statuses)
	{	
	      $new_order_statuses = array();
	      // add new order status after Completed
	      foreach ( $order_statuses as $key => $status ) 
	      {
	         $new_order_statuses[ $key ] = $status;
	         if ( 'wc-on-hold' === $key ) 
	         {
	            $new_order_statuses['wc-shipped'] = __('Shipped','wcld');    
	         }
	      }
	   return $new_order_statuses;
	}
 	
 	/**
	 * Shipped date meta
	 * @param $order the order
	 * @return void
	 */  
	function wc_shipping_date_meta($order) {   
		$shipping_date 		= get_post_meta( $order->get_id(), '_shipping_date', true );
		$is_shipped 		= ($order->get_status() == 'shipped') ? true : false;
		if($is_shipped && !$shipping_date) {
			$wc_datetime = new WC_DateTime();
			$wc_datetime->set_utc_offset(0);
			$wc_datetime_str = $wc_datetime->__toString();
			add_post_meta( $order->get_id(), '_shipping_date', $wc_datetime_str);
		} elseif(!$is_shipped && $shipping_date) {
			delete_post_meta( $order->get_id(), '_shipping_date' ); 
		}
	}
	
}

// instantiate plugin's class
$GLOBALS['wc_logistics_dashboard'] = new wc_logistics_dashboard();

// load orders filter by shipping date
add_action( 'plugins_loaded', 'wc_load_filter_orders_by_shipping_date' );

/**
 * Adds custom filtering to the orders screen to allow filtering by shipping date.
 */
 class WC_Filter_Orders_By_Shipping_Date {

	const VERSION = '1.0';
	protected static $instance;

	/**
	 * WC_Filter_Orders_By_Shipping_Date constructor.
	 */
	public function __construct() {

		// load translations
		add_action( 'init', array( $this, 'load_translation' ) );

		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {

			// adds the shipped by date filtering dropdown to the orders page
			add_action( 'restrict_manage_posts', array( $this, 'filter_orders_by_shipping_date' ) );

			// makes orders filterable
			add_filter( 'posts_join',  array( $this, 'add_order_items_join' ) );
			add_filter( 'posts_where', array( $this, 'add_filterable_where' ) );
		}
	}

	/**
	 * Adds the shipped date filtering dropdown to the orders list
	 * @return void
	 */
	public function filter_orders_by_shipping_date() {
		global $typenow;

		if ( 'shop_order' === $typenow ) {
			$shipping_date_cats = array(
				"0-2" 				=> __('Last 3 days','wcld'),
				"3-6"       		=> __('4 to 7 days ago','wcld'),
				"7-2147483647"  	=> __('More than 7 days ago','wcld')
			);
			?>	
			<select name="_shipping_date" id="dropdown_shipping_date">
				<option value="">
					<?php esc_html_e( 'Filter by shipping date', 'wcld' ); ?>
				</option>
				<?php foreach ( $shipping_date_cats as $key => $value ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php echo esc_attr( ( isset( $_GET['_shipping_date'] ) && $_GET['_shipping_date'] == $key ) ? 'selected' : '' ); ?>>
						<?php echo 'Shipped: ' . esc_html( $value ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php
		}
	}


	/**
	 * Modify SQL JOIN for filtering the orders by shipping date
	 * @param $join JOIN part of the sql query
	 * @return $join modified JOIN part of sql query
	 */
	public function add_order_items_join( $join ) {
		global $typenow, $wpdb;

		if ( 'shop_order' === $typenow && isset( $_GET['_shipping_date'] ) && ! empty( $_GET['_shipping_date'] ) ) {
			$join .= "LEFT JOIN {$wpdb->prefix}postmeta wpm ON {$wpdb->posts}.ID = wpm.post_id";
		}

		return $join;
	}

	/**
	 * Modify SQL WHERE for filtering the orders by shipping date
	 * @param $where WHERE part of the sql query
	 * @return $where modified WHERE part of sql query
	 */
	public function add_filterable_where( $where ) {
		global $typenow, $wpdb;

		if ( 'shop_order' === $typenow && isset( $_GET['_shipping_date'] ) && ! empty( $_GET['_shipping_date'] ) ) {
			$shipping_date = explode('-',wc_clean( $_GET['_shipping_date']));
			$diff_from = $shipping_date[0];
			$diff_to = $shipping_date[1];
			$where .=  $wpdb->prepare(" AND meta_key = '_shipping_date' AND DATEDIFF(UTC_TIMESTAMP(),(SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE meta_key = '_shipping_date' AND post_id = {$wpdb->posts}.ID)) >= %s AND DATEDIFF(UTC_TIMESTAMP(), (SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE meta_key = '_shipping_date' AND post_id = {$wpdb->posts}.ID)) <= %s", $diff_from, $diff_to);
		}
		return $where;
	}

	/**
	 * Load Translations
	 */
	public function load_translation() {
		// localization
		load_plugin_textdomain( 'wcld', false, dirname( plugin_basename( __FILE__ ) ) . '/i18n/languages' );
	}


	/**
	 * Main WC_Filter_Orders_By_Shipping_Date Instance, ensures only one instance
	 * @return WC_Filter_Orders_By_Shipping_Date
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
		 	self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * Cloning instances is forbidden due to singleton pattern.
	 */
	public function __clone() {
		/* translators: Placeholders: %s - plugin name */
		_doing_it_wrong( __FUNCTION__, sprintf( esc_html__( 'You cannot clone instances of %s.', 'wcld' ), 'Filter WC Orders by Shipping Date' ), '1.0' );
	}


	/**
	 * Unserializing instances is forbidden due to singleton pattern.
	 */
	public function __wakeup() {
		/* translators: Placeholders: %s - plugin name */
		_doing_it_wrong( __FUNCTION__, sprintf( esc_html__( 'You cannot unserialize instances of %s.', 'wcld' ), 'Filter WC Orders by Shipping Date' ), '1.0' );
	}

}


/**
 * Returns the One True Instance of WC_Filter_Orders_By_Shipping_Date
 * @return \WC_Filter_Orders_By_Shipping_Date
 */
function wc_load_filter_orders_by_shipping_date() {
	return WC_Filter_Orders_By_Shipping_Date::instance();
}

/**
 * Get orders by date category
 * @return $orders the number of orders by date category
 */  
function wc_get_orders_by_date_cat() {
	$query = new WC_Order_Query( array(
	    'limit' => -1,
	    'return' => 'ids',
	));
	$ids = $query->get_orders();
	$three_days = 0;
	$week = 0;
	$past = 0;
	foreach($ids as $id) {
		$shipping_date_meta = get_post_meta( $id, '_shipping_date', true);

		if($shipping_date_meta) {
			$shipping_date = date_create($shipping_date_meta);
			$wc_datetime = new WC_DateTime();
			$wc_datetime->set_utc_offset(0);
			$timestamp = date_create($wc_datetime);
			$diff = date_diff($shipping_date, $timestamp);
			if($diff->format('%R%a') < 3) {
				$three_days++;
			} elseif($diff->format('%R%a') < 7) {
				$week++;
			} else {
				$past++;
			}
		}
	}
	$orders = array(
		'three-days' 	=> $three_days,
		'week'			=> $week,
		'past'			=> $past
	);
	return $orders;
}
