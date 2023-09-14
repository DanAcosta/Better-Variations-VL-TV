<?php

class WC_Vapelab_Sheets_Connector_Admin
{

    private $id;

    private $version;

	private $settings;

	private $orders;

	private $products;

	private $shippings;
		
	private $service;

	private $spreadsheet_snippets;

	private $plugin_path;

	private $google_snippets;

    public function __construct($id, $path, $version, $settings, $google_snippets)
    {

		$this->id = $id;

		$this->version = $version;

		$this->settings = $settings;

		$this->plugin_path = $path;

		$this->google_snippets = $google_snippets;

		$this->shippings = new WC_Vapelab_Sheets_Connector_Admin_Shippings($google_snippets);

		$this->orders = new WC_Vapelab_Sheets_Connector_Admin_Orders($google_snippets);

		$this->products = new WC_Vapelab_Sheets_Connector_Admin_Products($google_snippets);

		$this->details = new WC_Vapelab_Sheets_Connector_Admin_Details($google_snippets);
		
		add_action('wc_vapelab_sheets_connector_sync', array($this, 'woocommerce_order_status_changed_vl_callback'),90);

    }

	public function run(){
		
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'woocommerce_new_order',  array($this,'woocommerce_new_order_vl_callback') );
		add_action( 'woocommerce_update_order', array( $this, 'wc_vapelab_sheets_connector_update_order_callback' ),90 );
		add_action( 'woocommerce_order_status_changed',  array($this,'woocommerce_order_status_changed_vl_callback'),90,3 );
		add_action( 'woocommerce_save_product_variation',  array($this,'woocommerce_save_product_variation_vl_callback' ));
		add_action( 'woocommerce_process_product_meta',  array($this,'woocommerce_process_product_meta_vl_callback' ),90);
		add_action( 'woocommerce_bulk_edit_variations', array($this,'woocommerce_bulk_edit_variations_vl_callback' ),10, 4 );
		add_action( 'woocommerce_updated_product_stock',  array($this,'wc_updated_product_stock_callback') );

	}

	public function woocommerce_order_status_changed_vl_callback($order_id, $status_from, $status_to){

		if($this->settings['enable_details_sheet']){

			if($this->settings['enable_shippings_sheet'] == "yes"){
				$this->shippings->updateShippingSheet($order_id);
			}
	
			if($this->settings['enable_orders_sheet'] == "yes"){
				
				$this->orders->updateOrderSheet($order_id);
			}

			$reload = false;
			if($status_to == 'completed'){
				$this->details->reload();
			}else{
				$this->details->updateDetailsSheet($order_id);
			}
			
		}
		
	}

	public function woocommerce_new_order_vl_callback($order_id){
		
		if(is_admin()){

			if($this->settings['enable_shippings_sheet'] == "yes"){

				$this->shippings->insertShippingSheet($order_id);
				$this->shippings->updateShippingSheet($order_id,true);
	
			}
	
			if($this->settings['enable_orders_sheet'] == "yes"){
				
				$this->orders->insertOrderSheet($order_id);
				$this->orders->updateOrderSheet($order_id,true);
			}

			if($this->settings['enable_details_sheet']){
				$this->details->insertOrderSheet($order_id);
			}
			

		}
		
		
	}

	public function woocommerce_bulk_edit_variations_vl_callback($bulk_action, $data, $product_id, $variations ){
		
		
		if($this->settings['enable_products_sheet'] == "yes"){
			$this->products->bulkEditVariations($bulk_action, $data, $product_id, $variations);
		}
		
		
	}
	

	public function woocommerce_save_product_variation_vl_callback($product_id){
		
		if($this->settings['enable_products_sheet'] == "yes"){
			$this->products->saveProductVariation($product_id);
		}
		
	}

    public function add_admin_menu() {
		
		if( empty(menu_page_url('vapelab-menu-page', false)) ){
			
			add_menu_page(
				'VapeLab',
				'VapeLab',
				'manage_options',
				'vapelab-menu-page',
				'',
				plugins_url('admin/images/icon.png',__DIR__),
				25
			);
			
			add_submenu_page( 'vapelab-menu-page', 'WooCommerce Sheets Connector ', 'WooCommerce Sheets Connector ', 'manage_options', 'wc-vapelab-sheets-connector', array($this,'wc_vapelab_sheets_connector_settings_page'));
			
			remove_submenu_page( 'vapelab-menu-page','vapelab-menu-page' );
			
		}else{
			add_submenu_page( 'vapelab-menu-page', 'WooCommerce Sheets Connector ', 'WooCommerce Sheets Connector ', 'manage_options', 'wc-vapelab-sheets-connector', array($this,'wc_vapelab_sheets_connector_settings_page'));
		}
		
		
		/*
		add_submenu_page($this->mainMenuId, 'About', 'About', 'manage_options', $this->mainMenuId);

		add_menu_page(
			'My Plugin Settings', // Page title
			'My Plugin', // Menu title
			'manage_options', // Capability required to access the menu
			'my-plugin-settingss', // Menu slug
			'my_plugin_settings_page', // Callback function to render the settings page
			'dashicons-admin-generic', // Icon for the menu item (optional),
			25
		);

		add_menu_page(
			'My Plugin Settings', // Page title
			'My Plugin', // Menu title
			'manage_options', // Capability required to access the menu
			'my-plugin-settings', // Menu slug
			'my_plugin_settings_page', // Callback function to render the settings page
			'dashicons-admin-generic', // Icon for the menu item (optional),
			25
		);

		echo '<pre>';var_dump( menu_page_url('vapelab-menu-page', false) != "" );echo '</pre>';exit();
		*/
		//add_submenu_page( 'vapelab', 'WooCommerce Sheets Connector ', 'WooCommerce Sheets Connector ', 'manage_options', 'wc-vapelab-sheets-connector', array($this,'wc_vapelab_sheets_connector_settings_page'));

	}

	public  function wc_vapelab_sheets_connector_shipping_move_callback() {	

		if($this->settings['enable_shippings_sheet']){

			$this->shippings->fixMetadata();
			$this->shippings->moveShippingsToHistory();
			$this->shippings->fixMetadata();

		}
		

	}

	public  function wc_vapelab_sheets_connector_orders_move_callback() {	
		
		try {

			if($this->settings['enable_orders_sheet']){

				$this->orders->fixMetadata();
				$this->orders->moveOrdersToHistory();
				$this->orders->fixMetadata();

			}
			
		}
		catch (Google\Service\Exception $e) {
			// Si falla manda un mail    
			wp_mail(get_bloginfo('admin_email'), "Error en la validación de Token", "Ha ocurrido un error durante la validación de Token ");
		}
		
	}

	public function wc_vapelab_sheets_connector_update_order_callback($order_id){


		if($this->settings['enable_shippings_sheet']){
			$this->shippings->updateShippingSheet($order_id);
		}
		
		if($this->settings['enable_orders_sheet']){
			$this->orders->updateOrderSheet($order_id);
		}

		if($this->settings['enable_details_sheet']){
			$this->details->updateDetailsSheet($order_id);
		}

	}

    public function wc_vapelab_sheets_connector_settings_page(){

		
        global $pagenow, $woocommerce, $post;


		$tab =  ( isset ( $_GET['tab'] ) ) ? $_GET['tab'] : 'connection';

		if(isset($_POST['submit_connection_tab'])){

			$settings = get_option( $this->id.'_settings', array() );
			
			if(isset($_FILES['wcvlshcon_credentials_json_url'])){
				
				$tmp_name = $_FILES["wcvlshcon_credentials_json_url"]["tmp_name"];
				move_uploaded_file($tmp_name, "$this->plugin_path/$this->id-credentials.json");

			}
			
			$settings['client_id'] = $_POST['wcvlshcon_client_id'];
			$settings['client_secret'] = $_POST['wcvlshcon_client_secret'];
			
			update_option( $this->id.'_settings', $settings );
			
			$url_parameters = isset($_GET['tab'])? 'updated=true&tab='.$_GET['tab'] : 'updated=true';
            wp_redirect(admin_url('admin.php?page=wc-vapelab-sheets-connector&'.$url_parameters));
			//exit;

		}

        if(isset($_POST['submit_poroducts_tab'])){
			
			$settings = $this->settings;
			$settings['enable_products_sheet'] = $_POST['enable_products_sheet'];
			update_option( $this->id.'_settings', $settings );

            $products_settings = get_option( "wc_products_sheets_vapelab_settings" );
			
			if( isset($_POST['wcvlshcon_products']['sheet_id']) ){
				$sheet_arr = explode(":",$_POST['wcvlshcon_products']['sheet_id']);
				$sheet_id = $sheet_arr[0];
				$sheet_title = $sheet_arr[1];
			}

			$products_settings['spreadsheet_id'] = isset($_POST['wcvlshcon_products']['spreadsheet_id']) ? $_POST['wcvlshcon_products']['spreadsheet_id'] : "";
			$products_settings['sheet_id'] = isset($sheet_id) ? $sheet_id : "";
			$products_settings['sheet_title'] = isset($sheet_title) ? $sheet_title : "";
			$products_settings['products_spreadsheet_group_by_categories'] = isset($_POST['products_spreadsheet_group_by_categories']) ? 'yes' : 'no';
			
			update_option( "wc_products_sheets_vapelab_settings", $products_settings );
            
			$url_parameters = '&tab='.$_GET['tab'].'&updated=success';
            wp_redirect(admin_url('admin.php?page=wc-vapelab-sheets-connector&'.$url_parameters));
			exit;

        }

		if(isset($_POST['submit_orders_tab'])){
			
			$settings = $this->settings;
			$settings['enable_orders_sheet'] = $_POST['enable_orders_sheet'];
			update_option( $this->id.'_settings', $settings );

            $order_settings = get_option( "wc_orders_sheets_vapelab_settings" );

			if( isset($_POST['wcvlshcon_orders']['main_sheet_id']) ){
				$main_sheet_arr = explode(":",$_POST['wcvlshcon_orders']['main_sheet_id']);
				$main_sheet_id = $main_sheet_arr[0];
				$main_sheet_title = $main_sheet_arr[1];
			}

			if( isset($_POST['wcvlshcon_orders']['sheet_id_move']) ){
				$move_sheet_arr = explode(":",$_POST['wcvlshcon_orders']['sheet_id_move']);
				$move_sheet_id = $move_sheet_arr[0];
				$move_sheet_title = $move_sheet_arr[1];
			}
			
			$order_settings['spreadsheet_id'] = isset($_POST['wcvlshcon_orders']['spreadsheet_id']) ? $_POST['wcvlshcon_orders']['spreadsheet_id'] : "";
			$order_settings['main_sheet_id'] = isset($main_sheet_id) ? $main_sheet_id : "";
			$order_settings['main_sheet_title'] = isset($main_sheet_title) ? $main_sheet_title : "";
			$order_settings['enable_move_auto'] = isset($_POST['wcvlshcon_orders']['enable_move_auto']) ? 'yes' : 'no';
			$order_settings['sheet_id_move'] = isset($move_sheet_id) ? $move_sheet_id : "";
			$order_settings['move_sheet_title'] = isset($move_sheet_title) ? $move_sheet_title : "";
			$order_settings['move_target_col'] = isset($_POST['wcvlshcon_orders']['move_target_col']) ? $_POST['wcvlshcon_orders']['move_target_col'] : "";
			$order_settings['move_target_content'] = isset($_POST['wcvlshcon_orders']['move_target_content']) ? $_POST['wcvlshcon_orders']['move_target_content'] : "";
		
            update_option( "wc_orders_sheets_vapelab_settings", $order_settings );

			$url_parameters = '&tab='.$_GET['tab'].'&updated=success';
            wp_redirect(admin_url('admin.php?page=wc-vapelab-sheets-connector&'.$url_parameters));
			exit;
            
        }

		if(isset($_POST['submit_shippings_tab'])){
			
			
			$settings = $this->settings;
			$settings['enable_shippings_sheet'] = $_POST['enable_shippings_sheet'];
			update_option( $this->id.'_settings', $settings );

            $shipping_settings = get_option( "wc_shipping_sheets_vapelab_settings" );

			if( isset($_POST['wcvlshcon_shippings']['main_sheet_id']) ){
				$main_sheet_arr = explode(":",$_POST['wcvlshcon_shippings']['main_sheet_id']);
				$main_sheet_id = $main_sheet_arr[0];
				$main_sheet_title = $main_sheet_arr[1];
			}

			if( isset($_POST['wcvlshcon_shippings']['sheet_id_move']) ){
				$move_sheet_arr = explode(":",$_POST['wcvlshcon_shippings']['sheet_id_move']);
				$move_sheet_id = $move_sheet_arr[0];
				$move_sheet_title = $move_sheet_arr[1];
			}

			$shipping_settings['spreadsheet_id'] = isset($_POST['wcvlshcon_shippings']['spreadsheet_id']) ? $_POST['wcvlshcon_shippings']['spreadsheet_id'] : "";
			$shipping_settings['main_sheet_id'] = isset($main_sheet_id) ? $main_sheet_id : "";
			$shipping_settings['main_sheet_title'] = isset($main_sheet_title) ? $main_sheet_title : "";
			$shipping_settings['company_id'] = isset($_POST['wcvlshcon_shippings']['company_id']) ? $_POST['wcvlshcon_shippings']['company_id'] : "";
			$shipping_settings['company_field'] = isset($_POST['wcvlshcon_shippings']['company_field']) ? $_POST['wcvlshcon_shippings']['company_field'] : "";
			$shipping_settings['enable_move_auto'] = isset($_POST['wcvlshcon_shippings']['enable_move_auto']) ? 'yes' : 'no';
			$shipping_settings['sheet_id_move'] = isset($move_sheet_id) ? $move_sheet_id : "";
			$shipping_settings['move_sheet_title'] = isset($move_sheet_title) ? $move_sheet_title : "";
			$shipping_settings['move_target_col'] = isset($_POST['wcvlshcon_shippings']['move_target_col']) ? $_POST['wcvlshcon_shippings']['move_target_col'] : "";
			$shipping_settings['move_target_content'] = isset($_POST['wcvlshcon_shippings']['move_target_content']) ? $_POST['wcvlshcon_shippings']['move_target_content'] : "";
		
            update_option( "wc_shipping_sheets_vapelab_settings", $shipping_settings );

			$url_parameters = '&tab='.$_GET['tab'].'&updated=success';
            wp_redirect(admin_url('admin.php?page=wc-vapelab-sheets-connector&'.$url_parameters));
			exit;
            
        }

		if(isset($_POST['submit_details_tab'])){
			
			
			$settings = $this->settings;
			$settings['enable_details_sheet'] = $_POST['enable_details_sheet'];
			update_option( $this->id.'_settings', $settings );

            $details_settings = get_option( "wc_details_sheets_vapelab_settings" );

			if( isset($_POST['wcvlshcon_details']['main_sheet_id']) ){
				$main_sheet_arr = explode(":",$_POST['wcvlshcon_details']['main_sheet_id']);
				$main_sheet_id = $main_sheet_arr[0];
				$main_sheet_title = $main_sheet_arr[1];
			}

			$details_settings['spreadsheet_id'] = isset($_POST['wcvlshcon_details']['spreadsheet_id']) ? $_POST['wcvlshcon_details']['spreadsheet_id'] : "";
			$details_settings['main_sheet_id'] = isset($main_sheet_id) ? $main_sheet_id : "";
			$details_settings['main_sheet_title'] = isset($main_sheet_title) ? $main_sheet_title : "";
			$details_settings['variation_column'] = isset($_POST['wcvlshcon_details']['variation_column']) ? $_POST['wcvlshcon_details']['variation_column'] : "";
			$details_settings['delimiter'] = isset($_POST['wcvlshcon_details']['delimiter']) ? $_POST['wcvlshcon_details']['delimiter'] : "";
			
		
            update_option( "wc_details_sheets_vapelab_settings", $details_settings );

			$url_parameters = '&tab='.$_GET['tab'].'&updated=success';
            wp_redirect(admin_url('admin.php?page=wc-vapelab-sheets-connector&'.$url_parameters));
			exit;
            
        }
		
		if(isset($_POST['action']) && $_POST['action'] == "wc_products_sheets_vapelab_create_spreadsheet"){

			$products_spreadsheet = new WC_Vapelab_Sheets_Connector_Admin_Products ($this->google_snippets);
			
			$products_spreadsheet->createProductsSheet();
			
			$url_parameters = '&tab='.$_GET['tab'].'&updated=success';
            wp_redirect(admin_url('admin.php?page=wc-vapelab-sheets-connector&'.$url_parameters));
			exit;
            
        }

		if(isset($_POST['action']) && $_POST['action'] == "wc_products_sheets_vapelab_update_spreadsheet"){

			$this->products->updateSpreadsheet();
			
			
			$url_parameters = '&tab='.$_GET['tab'].'&updated=success';
            wp_redirect(admin_url('admin.php?page=wc-vapelab-sheets-connector&'.$url_parameters));
			exit;
            
        }

		if(isset($_POST['action']) && $_POST['action'] == "wc_products_sheets_vapelab_update_woo"){

			$settings = array_merge(get_option( "wc_products_sheets_vapelab_settings" ), get_option($this->id.'_settings') );
			
			if($settings['products_spreadsheet_group_by_categories'] == 'yes'){
				
			}else{
				$this->products->update_woo($settings);
			}
			
			$url_parameters = isset($_GET['tab'])? 'updated=true&tab='.$_GET['tab'] : 'updated=true';
			wp_redirect(admin_url('admin.php?page=wc-vapelab-sheets-connector&'.$url_parameters));
            exit;
            
        }

		if(isset($_POST['action']) && $_POST['action'] == "wc_orders_sheets_vapelab_create_spreadsheet"){

			$settings = get_option( "wc_orders_sheets_vapelab_settings" );

			$settings['spreadsheet_id'] = $this->orders->createOrdersSheet();
			
			update_option( "wc_orders_sheets_vapelab_settings", $settings );
            
			$url_parameters = isset($_GET['tab'])? 'updated=true&tab='.$_GET['tab'] : 'updated=true';
            wp_redirect(admin_url('admin.php?page=wc-vapelab-sheets-connector&'.$url_parameters));
			exit;
            
        }

		if(isset($_POST['action']) && $_POST['action'] == "wc_shippings_sheets_vapelab_create_spreadsheet"){

			$settings = get_option( "wc_shipping_sheets_vapelab_settings" );

			$settings['spreadsheet_id'] = $this->shippings->createShippingsSheet();
			
			update_option( "wc_shipping_sheets_vapelab_settings", $settings );
            
			$url_parameters = isset($_GET['tab'])? 'updated=true&tab='.$_GET['tab'] : 'updated=true';
            wp_redirect(admin_url('admin.php?page=wc-vapelab-sheets-connector&'.$url_parameters));
			exit;
            
        }

		if ( isset($_POST["wcosvl_action_submit"]) && $_POST["wcosvl_action_submit"] == 'wc_shippings_sheets_vapelab_oder_sheet' ) {
		

			$this->shippings->orderSheetByDate();

			$url_parameters = isset($_GET['tab'])? 'updated=true&tab='.$_GET['tab'] : 'updated=true';
            wp_redirect(admin_url('admin.php?page=wc-vapelab-sheets-connector&'.$url_parameters));
			exit;
            
        }

		if ( isset($_POST["wcosvl_action_submit"]) && $_POST["wcosvl_action_submit"] == 'wc_orders_sheets_vapelab_move_orders_sheets' ) {
			
			$this->orders->fixMetadata();
			$this->orders->moveOrdersToHistory();
			$this->orders->fixMetadata();
			$url_parameters = isset($_GET['tab'])? 'updated=true&tab='.$_GET['tab'] : 'updated=true';
			wp_redirect(admin_url('admin.php?page=wc-vapelab-sheets-connector&'.$url_parameters));
			exit;

		}

		if ( isset($_POST["wcosvl_action_submit"]) && $_POST["wcosvl_action_submit"] == 'wc_shippings_sheets_vapelab_move_shippings_sheets' ) {
			
			$this->wc_vapelab_sheets_connector_shipping_move_callback();
			$url_parameters = isset($_GET['tab'])? 'updated=true&tab='.$_GET['tab'] : 'updated=true';
			wp_redirect(admin_url('admin.php?page=wc-vapelab-sheets-connector&'.$url_parameters));
			exit;

		}

		if ( isset( $_POST["wcosvl_action_submit"] ) && $_POST["wcosvl_action_submit"] == 'wc_shippings_sheets_vapelab_fix_metadata_sheets' ) {
			
			$this->shippings->fixMetadata();
			$url_parameters = isset($_GET['tab'])? 'updated=true&tab='.$_GET['tab'] : 'updated=true';
			wp_redirect(admin_url('admin.php?page=wc-vapelab-sheets-connector&'.$url_parameters));
			exit;

		}

		if ( isset($_POST["wcosvl_action_submit"]) &&  $_POST["wcosvl_action_submit"] == 'wc_orders_sheets_vapelab_fix_metadata_sheets' ) {
			
			$this->orders->fixMetadata();
			$url_parameters = isset($_GET['tab'])? 'updated=true&tab='.$_GET['tab'] : 'updated=true';
			wp_redirect(admin_url('admin.php?page=wc-vapelab-sheets-connector&'.$url_parameters));
			exit;

		}

		if(isset($_POST['action_submit']) && $_POST['action_submit'] == "wc_details_sync_by_id" ){

			$order_id = $_POST['action_submit_data']['order_id'];

			$order = wc_get_order($order_id);

			if($order){

				$this->details->insertIntoSheet($order_id,false);
			}

			
		}

		if(isset($_POST['action_submit']) && $_POST['action_submit'] == "wc_details_sync_by_date" ){

			if(!empty($_POST['action_submit_data']['from']) && !empty($_POST['action_submit_data']['to']) ){

				// Define the start and end dates for the range
				$start_date = $_POST['action_submit_data']['from'];
				$end_date = $_POST['action_submit_data']['to'];

				// Convert the dates to UTC format
				$start_date_utc = date('Y-m-d', strtotime($start_date)) . 'T00:00:00';
				$end_date_utc = date('Y-m-d', strtotime($end_date)) . 'T23:59:59';

				// Prepare the order query arguments
				$args = array(
					'limit'      => -1, // Retrieve all orders within the range
					//'status'     => array('completed', 'processing'), // Specify the order statuses to include
					'date_created' => $start_date_utc.'...'.$end_date_utc,
					'order' => 'ASC',
				);
				
				// Instantiate the order query
				$query = new WC_Order_Query($args);

				// Get the orders within the date range
				$orders = $query->get_orders();
			
				// Process the retrieved orders
				foreach ($orders as $order) {
					
					
					$this->details->insertIntoSheet($order->get_id());

				}
				

			}

		}


		if(isset($_POST['action_submit']) && $_POST['action_submit'] == "wc_details_fix_metadata" ){
			
			$this->details->fixMetadata();

		}

		if ( isset ( $_GET['tab'] ) ) {

			$this->wc_vapelab_sheets__settings_admin_tabs($_GET['tab']); 

		}else{

			$this->wc_vapelab_sheets__settings_admin_tabs('connection');

		}

		if($tab == 'connection'){

			require_once 'partials/wc-vapelab-sheets-connector-connection-display.php';

		}
		elseif($tab == 'products'){
			
			$enabled = $this->settings['enable_products_sheet'] == 'yes' ? true : false;

			if ($enabled) {

				$settings = get_option( "wc_products_sheets_vapelab_settings",array());

				$spreadsheet_id = $settings['spreadsheet_id'];

				if(!is_null($spreadsheet_id) && !empty($spreadsheet_id)){
					try {
						$response = $this->google_snippets->getSpreadsheet($spreadsheet_id);

						foreach ($response->sheets as $sheet) {
							$sheet_id = $sheet->properties->sheetId;
							$tmp = array(
								'id' => $sheet->properties->sheetId,
								'title' => $sheet->properties->title,
								'label' => "( ".$sheet->properties->sheetId." ) ".$sheet->properties->title,
							);
							$sheets_arr[$sheet_id] = $tmp;
						}
					} catch(Google\Service\Exception $e) {
						
						$error = json_decode($e->getMessage(), true);

						echo '<div class="notice notice-error">
								<p>Error: '.print_r($error,true).'</p>
							</div>'; 

					}
				}
				
			}
			
            require_once 'partials/wc-vapelab-sheets-connector-products-display.php';
        }
        elseif($tab == 'orders'){
			
			$enabled = $this->settings['enable_orders_sheet'] == 'yes' ? true : false;
	
			if ($enabled) {

				$settings = get_option( "wc_orders_sheets_vapelab_settings",array());

				$spreadsheet_id = $settings['spreadsheet_id'];
				$sheets_arr = array();

				if(!is_null($spreadsheet_id) && !empty($spreadsheet_id)){
					try {
						$response = $this->google_snippets->getSpreadsheet($spreadsheet_id);

						foreach ($response->sheets as $sheet) {
							$sheet_id = $sheet->properties->sheetId;
							$tmp = array(
								'id' => $sheet->properties->sheetId,
								'title' => $sheet->properties->title,
								'label' => "( ".$sheet->properties->sheetId." ) ".$sheet->properties->title,
							);
							$sheets_arr[$sheet_id] = $tmp;
						}
					} catch(Google\Service\Exception $e) {

						
						$error = json_decode($e->getMessage(), true);

						echo '<div class="notice notice-error">
								<p>Error: '.print_r($error,true).'</p>
							</div>'; 


					}
				}
			}
					
		
            require_once 'partials/wc-vapelab-sheets-connector-orders-display.php';

        }
		elseif($tab == 'shippings'){
			
			$enabled = $this->settings['enable_shippings_sheet'] == 'yes' ? true : false;
			
			if($enabled){

				$settings = get_option( "wc_shipping_sheets_vapelab_settings",array());
				
				$spreadsheet_id = $settings['spreadsheet_id'];
				
				$sheets_arr = array();
				if(!is_null($spreadsheet_id) && !empty($spreadsheet_id)){

					try{
						
						$response = $this->google_snippets->getSpreadsheet($spreadsheet_id);
						
						foreach($response->sheets as $sheet){

							$sheet_id = $sheet->properties->sheetId;
							$tmp = array(
								'id' => $sheet->properties->sheetId,
								'title' => $sheet->properties->title,
								'label' => "( ".$sheet->properties->sheetId." ) ".$sheet->properties->title,
							);
							$sheets_arr[$sheet_id] = $tmp;
							
						}
						
					}catch(Google\Service\Exception $e){	
						
						$error = json_decode($e->getMessage(), true);

						echo '<div class="notice notice-error">
								<p>Error: '.print_r($error,true).'</p>
							</div>'; 

					
					}


				}

			}
			
			


            require_once 'partials/wc-vapelab-sheets-connector-shippings-display.php';
        }
		elseif($tab == 'details'){

			
			$enabled = $this->settings['enable_details_sheet'] == 'yes' ? true : false;

			if($enabled){

				$settings_default = array(
					'spreadsheet_id' => "",
					'main_sheet_id' => "",
					'delimiter' => "",
					'variation_column' => "no"
				);
				
				$settings = get_option( "wc_details_sheets_vapelab_settings",array());
				
				$settings = array_merge($settings_default,$settings);
				
				$spreadsheet_id = $settings['spreadsheet_id'];
				
				$sheets_arr = array();
				if(!is_null($spreadsheet_id) && !empty($spreadsheet_id)){

					try{
						
						$response = $this->google_snippets->getSpreadsheet($spreadsheet_id);
						
						foreach($response->sheets as $sheet){

							$sheet_id = $sheet->properties->sheetId;
							$tmp = array(
								'id' => $sheet->properties->sheetId,
								'title' => $sheet->properties->title,
								'label' => "( ".$sheet->properties->sheetId." ) ".$sheet->properties->title,
							);
							$sheets_arr[$sheet_id] = $tmp;
							
						}
						
					}catch(Google\Service\Exception $e){	
						
						$error = json_decode($e->getMessage(), true);

						echo '<div class="notice notice-error">
								<p>Error: '.print_r($error,true).'</p>
							</div>'; 

					
					}


				}

			}
			require_once 'partials/wc-vapelab-sheets-connector-details-display.php';
			
			//$this->details->insertIntoSheet(1615719,false);
			//echo '<pre>';var_dump("complete!");echo '</pre>';exit();
			
		}

    }

    public function wc_vapelab_sheets__settings_admin_tabs( $current = 'connection' ){

		$tabs = array( 'connection' => 'Connection', 'products' => 'Products', 'orders' => 'Orders', 'shippings' => 'Shippings', 'details' => 'Details' );
		echo '<div id="icon-themes" class="icon32"><br></div>';
		echo '<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">';
		foreach( $tabs as $tab => $name ){
			$class = ( $tab == $current ) ? " nav-tab-active" : "";
			
			echo "<a class='nav-tab".$class."' href='?page=wc-vapelab-sheets-connector&tab=$tab'>$name</a>";

		}
		echo '</h2>';

	}

	public function get_sheets_by_spreadsheets($spreadsheet_id){

		
		
		try{
			
			$response = $this->google_snippets->getSpreadsheet($spreadsheet_id);

		
			$sheets_arr = array();
			foreach($response->sheets as $sheet){

				$sheet_id = $sheet->properties->sheetId;
				$tmp = array(
					'id' => $sheet->properties->sheetId,
					'title' => $sheet->properties->title,
				);
				$sheets_arr[$sheet_id] = $tmp;
				

			}
			return $sheets_arr;

		}catch(Google\Service\Exception $e){	

			$error = json_decode($e->getMessage(), true)['error'];
			

			if(isset($error['errors'] )){

				foreach($error['errors'] as $err){
					add_settings_error(
						'wcosvl_speadsheet_products',
						$error['code'],
						$err['message'],
					);
				}

			}
			
		
		}

	}

	public function wc_updated_product_stock_callback( $product_id_with_stock ){
		
		if ($this->settings['enable_products_sheet'] == "yes") {

			$product =  wc_get_product($product_id_with_stock);

			$product_class = get_class($product);

			if ($product_class == 'WC_Product_Variation') {
				$this->products->updateProductVariation($product);
			} elseif ($product_class == 'WC_Product_Simple') {
				$this->products->updateProductSimple($product);
			}

		}
		

	}

	public function woocommerce_process_product_meta_vl_callback( $product_id ){

	
		if ($this->settings['enable_products_sheet'] == "yes") {

			$product  = wc_get_product( $product_id );
		
			$product_class = get_class($product);
			
			

			if( $product_class == 'WC_Product_Variable' ){

				$this->products->updateProductVariable($product);
				
			}elseif( $product_class == 'WC_Product_Simple'  ){

				$this->products->updateProductSimple($product);
			}

			
		}
		

	}


}