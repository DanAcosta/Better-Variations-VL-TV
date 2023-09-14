<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Sheets_Vapelab
 * @subpackage Sheets_Vapelab/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Sheets_Vapelab
 * @subpackage Sheets_Vapelab/public
 * @author     Your Name <email@example.com>
 */
class WC_Vapelab_Sheets_Connector_Public {

	private $id;

    private $version;

	private $settings;

	private $orders;

	private $products;

	private $shippings;

	private $google_snippets;

	private $plugin_path;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $sheets_vapelab       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($id, $path, $version, $settings, $google_snippets){

		$this->id = $id;

		$this->version = $version;

		$this->settings = $settings;

		$this->plugin_path = $path;

		$this->google_snippets = $google_snippets;

		$this->products = new WC_Vapelab_Sheets_Connector_Admin_Products($google_snippets);

		$this->shippings = new WC_Vapelab_Sheets_Connector_Admin_Shippings($google_snippets);

		$this->orders = new WC_Vapelab_Sheets_Connector_Admin_Orders($google_snippets);

		$this->details = new WC_Vapelab_Sheets_Connector_Admin_Details($google_snippets);

	}

	public function run() {
		
		add_action( 'woocommerce_new_order',  array($this,'woocommerce_new_order_vl_callback') );
		//add_action( 'woocommerce_order_edit_status',  array($this,'woocommerce_order_edit_status_callback') );
		add_action( 'rest_api_init', array($this,'rest_api_init_callback'));
		add_action( 'woocommerce_updated_product_stock',  array($this,'wc_updated_product_stock_callback') );

		
		
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
	

	public function woocommerce_thankyou_vl_callback($order_id){

		
		if($this->settings['enable_shippings_sheet'] == "yes"){
			$this->shippings->updateShippingSheet($order_id,true);
		}
		
		if($this->settings['enable_orders_sheet'] == "yes"){
			$this->orders->updateOrderSheet($order_id,true);
		}

		if($this->settings['enable_details_sheet'] == "yes"){
			$this->details->insertIntoSheet($order_id);
		}

	}

	public function woocommerce_order_edit_status_callback($order_id){

		if ( metadata_exists( 'post', $order_id, 'sheet_updated_range' ) ) {
			$this->shippings->updateShippingSheet($order_id);
		}else{
			$this->shippings->insertShippingSheet($order_id);
			as_schedule_single_action(strtotime(date('Y-m-d H:i:s', strtotime("+3 sec"))),'wc_vapelab_sheets_connector_background', [ $order_id ]);
		}
	
	}

	public function woocommerce_new_order_vl_callback($order_id){

		if($this->settings['enable_shippings_sheet'] == "yes"){

			$this->shippings->insertShippingSheet($order_id);

		}

		if($this->settings['enable_orders_sheet'] == "yes"){
			
			$this->orders->insertOrderSheet($order_id);

		}

		as_schedule_single_action(strtotime(date('Y-m-d H:i:s', strtotime("+5 sec"))),'wc_vapelab_sheets_connector_background', [ $order_id ]);

	

	}


	public function rest_api_init_callback(){


		register_rest_route(  'vapelab','/order',
			array(
				'methods' => 'POST', 
				'callback' =>  array($this, 'update_order_date'),
			)
		);
		
		add_action( 'woocommerce_updated_product_stock',  array($this,'wc_updated_product_stock_api_callback') );
		add_action( 'woocommerce_update_order', array( $this, 'wc_vapelab_sheets_connector_update_order_api_callback' ) );


	}

	public function wc_updated_product_stock_api_callback( $product_id_with_stock ){
		
		
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

	public function wc_vapelab_sheets_connector_update_order_api_callback($order_id){


		if ($this->settings['enable_shippings_sheet'] == "yes") {
			$this->shippings->updateShippingSheet($order_id);
		}

		if ($this->settings['enable_orders_sheet'] == "yes") {
			$this->orders->updateOrderSheet($order_id);
		}

		if ($this->settings['enable_details_sheet'] == "yes") {
			$this->details->updateDetailsSheet($order_id);
		}



	}

	public function update_order_date(WP_REST_Request $data){

		
		$json = $data->get_json_params();
		
		$order = new WC_Order( $json['order_id'] );

		$date = date_create_from_format('d-M-y H:i',$json['date']." ".$json['time']);
		
		if(!is_null($date) && $date != false){
			$order->set_date_created( gmdate('d.m.Y H:i', strtotime($date->format('Y-m-d H:i'))) );
			$order->save();
		}
		

		return array('status'=> 'success');
	
	}


}
