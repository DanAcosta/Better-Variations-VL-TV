<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @since      1.0.0
 *
 * @package    WC_Vapelab_Sheets_Connector
 * @subpackage WC_Vapelab_Sheets_Connector/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    WC_Vapelab_Sheets_Connector
 * @subpackage WC_Vapelab_Sheets_Connector/includes
 * @author     Your Name <email@example.com>
 */
class WC_Vapelab_Sheets_Connector {

    private $loader;

    private $id;

    private $version;

	private $settings;

	private $plugin_path;

	private $plugin_admin;

	private $google_client;

	private $google_service;

	private $google_snippets;

    public function __construct() {

		if ( defined( 'WC_VAPELAB_SHEETS_CONNECTOR_VERSION' ) ) {
			$this->version = WC_VAPELAB_SHEETS_CONNECTOR_VERSION;
		}

		if ( defined( 'WC_VAPELAB_SHEETS_CONNECTOR_PATH' ) ) {
			$this->plugin_path = WC_VAPELAB_SHEETS_CONNECTOR_PATH;
		}
		

		$this->id = 'wc-vapelab-sheets-connector';

		$this->load_settings();
		$this->load_dependencies();
		
		if( is_admin() ){
			
			$this->define_admin_hooks();
		}else{
			
			$this->define_public_hooks();
		}

		$plugin_public =  new WC_Vapelab_Sheets_Connector_Public($this->id,$this->plugin_path,$this->version,$this->settings, $this->google_snippets);
		$this->loader->add_action( 'wc_vapelab_sheets_connector_background', $plugin_public,'woocommerce_thankyou_vl_callback') ;

		$plugin_admin = new WC_Vapelab_Sheets_Connector_Admin($this->id,$this->plugin_path,$this->version,$this->settings, $this->google_snippets);
		$this->loader->add_action( 'wc_vapelab_sheets_connector_orders_move_v2', $plugin_admin,'wc_vapelab_sheets_connector_orders_move_callback');
		$this->loader->add_action( 'wc_vapelab_sheets_connector_shipping_move_v2', $plugin_admin,'wc_vapelab_sheets_connector_shipping_move_callback') ;


	}

	private function load_settings(){

		$this->init_settings();

		$this->settings = array_merge($this->settings, (array)get_option($this->id.'_settings', array()));
		
	}

	private function init_settings()
	{

		$this->settings = array(
			'enable_products_sheet' => 'no',
			'enable_orders_sheet' => 'no',
			'enable_shippings_sheet' => 'no',
			'enable_details_sheet' => 'no',
		);
		
	}
   
	private function load_dependencies() {

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wc-vapelab-sheets-connector-admin.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-wc-vapelab-sheets-connector-public.php';
		
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'vendor/woocommerce/action-scheduler/action-scheduler.php';
		
		
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-vapelab-sheets-connector-loader.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wc-vapelab-sheets-connector-admin-orders.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wc-vapelab-sheets-connector-admin-shippings.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wc-vapelab-sheets-connector-admin-products.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wc-vapelab-sheets-connector-admin-details.php';

		//require_once plugin_dir_path( dirname( __FILE__ ) ) . 'logger/class-wc-vapelab-sheets-connector-logger.php';
		//require_once plugin_dir_path( dirname( __FILE__ ) ) . 'logger/class-wc-vapelab-sheets-connector-logger-instance.php';
			
		$this->loader = new WC_Vapelab_Sheets_Connector_Loader();

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'vendor/autoload.php';

        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-vapelab-sheets-connector-google-client.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-vapelab-sheets-connector-spreadsheet-snippets.php';

		$this->google_client =  new WC_Vapelab_Sheets_Connector_Google_Client();

		$this->google_client->client->addScope(Google\Service\Drive::DRIVE);

		$this->service = new Google_Service_Sheets($this->google_client->client);

		$this->google_snippets = new WC_Vapelab_Sheets_Connector_Spreadsheet_Snippets($this->service);

		
		
	}


	private function define_admin_hooks() {

		$plugin_admin = new WC_Vapelab_Sheets_Connector_Admin($this->id,$this->plugin_path,$this->version,$this->settings, $this->google_snippets);
		$plugin_admin->run();
	
	}

	private function define_public_hooks() {

		
		
		$plugin_public =  new WC_Vapelab_Sheets_Connector_Public($this->id,$this->plugin_path,$this->version,$this->settings, $this->google_snippets);
		

		
		$plugin_public->run();
		/*
		$plugin_public = new WC_Vapelab_Sheets_Connector_Public( $this->get_id(), $this->get_version() );
		$this->loader->add_action( 'rest_api_init', $plugin_public, 'vl_rest_api_init' );
		*/
	}

	


	public function run() {
		
		$this->loader->run();
	}



}