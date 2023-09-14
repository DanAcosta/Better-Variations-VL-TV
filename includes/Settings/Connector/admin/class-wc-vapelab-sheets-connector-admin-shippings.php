<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 *
 * @package    WC_Vapelab_Sheets_Connector
 * @subpackage WC_Vapelab_Sheets_Connector/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    WC_Vapelab_Sheets_Connector
 * @subpackage WC_Vapelab_Sheets_Connector/admin
 */
class WC_Vapelab_Sheets_Connector_Admin_Shippings
{
  
    private $id;

	private $settings;

    private $logger;

	private $google_snippets;

	private $service;

    public function __construct($google_snippets)
    {

		$this->google_snippets = $google_snippets;

		$this->service = $this->google_snippets->getService();

		$this->init_settings();
		
    }

	public function init_settings(){

		$this->settings  = get_option( "wc_shipping_sheets_vapelab_settings" );
	}

	public function sync(){
		
		/*
		$url = 'https://vapelab.mx/wp-json/wc/v3/orders?after=2023-02-28+00:00:00&per_page=100&page=2';

		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => array(
				'Authorization: Basic Y2tfMDNiOGUzMTI3ZGM4MTJhZDBlZWU2Y2RjMzc3MjM2Njc2NGQyMGFlZDpjc18xNTEzNjc4NDY4MjBhYTc5ZmU0ZGE5ZjNhY2ZkZGJjNzk3YzMyYzVl'
			)
		));

		$response = curl_exec($curl);
		
		file_put_contents(WC_VAPELAB_SHEETS_CONNECTOR_PATH."/orders.json", $response);

		curl_close($curl);
		exit();
		*/
		
		$settings = $this->settings;
		
		$spreadsheet_id = $settings['spreadsheet_id'];
		$main_sheet_id = $settings['main_sheet_id'];

		

		$orders = json_decode(file_get_contents(WC_VAPELAB_SHEETS_CONNECTOR_PATH."/orders.json"),true);

		$data_filters = [];
		foreach ($orders as $order) {

		
		$date_created = new \DateTime($order['date_created']);

		$country_code_phone = "";
		foreach($order['meta_data'] as $meta_data){
			if($meta_data['key'] == "shipping_country_phone_code"){
				$country_code_phone = $meta_data['value'];
			}
		}

		$phone = $order['shipping']['phone'];
		if(empty($phone) || is_null($phone)){
			$phone = $order['billing']['phone'];
		}

		$efectivo_real = "";
		foreach($order['meta_data'] as $meta_data){
			if($meta_data['key'] == "efectivo_real"){
				$efectivo_real = $meta_data['value'];
			}
		}

		$cobro = "";
		foreach($order['meta_data'] as $meta_data){
			if($meta_data['key'] == "cobro"){
				$cobro = $meta_data['value'];
			}
		}

		$shipping_method_id = "";
		$shipping_method_title = "";
		if(isset($order['shipping_lines']) && count($order['shipping_lines']) > 0){
			$shipping_method_id = $order['shipping_lines'][0]['method_id'];
			$shipping_method_title = $order['shipping_lines'][0]['method_title'];
		}

		$mensajero = "";
		foreach($order['meta_data'] as $meta_data){
			if($meta_data['key'] == "mensajero"){
				$mensajero = $meta_data['value'];
			}
		}
		if(empty($mensajero)){
			if (strpos($shipping_method_id, 'enviaya') !== false || $shipping_method_id == 'wc-enviaya-shipping' ) {
				$mensajero = "Guia";
			}
		}

		$hr_entrega = "";
		foreach($order['meta_data'] as $meta_data){
			if($meta_data['key'] == "hr_entrega"){
				$hr_entrega = $meta_data['value'];
			}
		}

		$hr_update = "";
		foreach($order['meta_data'] as $meta_data){
			if($meta_data['key'] == "hr_update"){
				$hr_update = $meta_data['value'];
			}
		}

		$link_clip = "";

		$hr_update = "";
		foreach($order['propinas'] as $meta_data){
			if($meta_data['key'] == "propinas"){
				$hr_update = $meta_data['value'];
			}
		}

		$link_etiqueta = "";
		if(isset($order['shipping_lines']) && count($order['shipping_lines']) > 0){

			$$item_shipping = $order['shipping_lines'][0];

			if(!empty($item_shipping['booking'])){

				$shipment = $item_shipping['booking'];
				$carrier_name = strtolower($shipment['carrier']);

				if($carrier_name == 'dhl'){
					$link_etiqueta = "https://www.dhl.com/us-en/home/tracking/tracking-express.html?submit=1&tracking-id=".$shipment['carrier_shipment_number'];
				}elseif($carrier_name == 'estafeta'){
					$link_etiqueta = "https://cs.estafeta.com/es/Tracking/searchByGet?wayBillType=1&wayBill=".$shipment['carrier_shipment_number'];
				}elseif($carrier_name == 'redpack'){
					$link_etiqueta = "https://www.redpack.com.mx/es/rastreo/?guias=".$shipment['carrier_shipment_number'];
				}elseif($carrier_name == 'fedex'){
					$link_etiqueta = "https://www.fedex.com/fedextrack/?trknbr=".$shipment['carrier_shipment_number'];
				}

			}

		}


		$order_fees = 0;

		// Get fees
		foreach ( $order['fee_lines'] as $fee ) {
			// Get total
			$order_fees += $fee['total_tax'];

			// OR $order_fees += $fee->get_total();
		}


		$order_subtotal = 0;

		// Get fees
		foreach ( $order['line_items'] as $item ) {
			// Get total
			$order_subtotal += $item['total'];

			// OR $order_fees += $fee->get_total();
		}


		$data = array(
			"VL",
			$date_created->format('d-M-y'),
			$date_created->format('H:i'),
			$order['id'],
			$order['shipping']['first_name']." ".$order['shipping']['last_name'],
			"",
			$order['shipping']['address_1'],
			$order['shipping']['address_2'],
			$order['shipping']['city'],
			$order['shipping']['state'],
			"'".$order['shipping']['postcode'],
			$country_code_phone,
			$phone,
			"", //WA
			$shipping_method_id,
			$shipping_method_title,
			$order['payment_method'],
			$order['payment_method_title'],
			$order['total'],
			$order_subtotal,
			$order_fees,
			$order['total'],
			$efectivo_real,
			$mensajero,
			$order['status'],
			//$hr_entrega,
			//$hr_update,
			//$terminado,
			//$link_etiqueta,
			//$link_clip,
			//$propinas,
			//"",
			//"",
			//$cobro,
		);

		
		$request = array(
			"data" => [
				[
					"dataFilter"=> [
						"developerMetadataLookup"=> [
							"metadataId"=> "1".$order['id'],
						]
					],
					"majorDimension"=> "ROWS",
					"values"=> array(
						$data
					)
				]
			],
			"includeValuesInResponse"=> false,
			"valueInputOption"=> "USER_ENTERED"
			
		);

		$res =$this->service->spreadsheets_values->batchUpdateByDataFilter(
			$spreadsheet_id,
			new Google_Service_Sheets_BatchUpdateValuesByDataFilterRequest($request)
		);

			}
			exit();

			$this->fixMetadata();

			$query_args = array(
		'fields' => 'ids',
		'post_type' => 'shop_order',
		'post_status' => array_keys( wc_get_order_statuses() ),
		'posts_per_page' => -1,
		//'numberposts' => -1,
		'date_query' => array(
			array(
				'before' => date('Y-m-d')." 23", // replace desired date
				'after'  => date('Y-m-d'), // replace desired date
				'inclusive' => true,
			),
		),
			);
			//4152313459558511
			$query = new WP_Query($query_args);



		$request = array(
			"dataFilter"=> $data_filters,
			"majorDimension"=> "ROWS",
		);
		$responseMeta =$this->service->spreadsheets_developerMetadata->search($spreadsheet_id, new Google_Service_Sheets_SearchDeveloperMetadataRequest([
			"dataFilters" => $data_filters
		]));
		
		$response =$this->service->spreadsheets_values->batchUpdateByDataFilter(
			$spreadsheet_id,
			new Google_Service_Sheets_BatchUpdateValuesByDataFilterRequest([
				"data"=> array($request),
				"includeValuesInResponse"=> true,
				"valueInputOption"=> "USER_ENTERED"
			]),
		);

		
	}

	public function orderSheetByDate(){
		
		$settings = $this->settings;
	
		$spreadsheet_id = $settings['spreadsheet_id'];

		$main_sheet_id =   $settings['main_sheet_id'];

		$spreadsheet_snippets = $this->google_snippets;

		$responseGetSheet =$this->service->spreadsheets->get($spreadsheet_id);

		foreach ($responseGetSheet->getSheets() as $sheet) {
			$sheet_properties = $sheet->getProperties();

			if ($sheet_properties->getSheetId() == $main_sheet_id) {
				$main_sheet = $sheet;
				break;
			}

		}

		if (isset($main_sheet)) {

			$responseGetValues = $this->google_snippets->batchGetValues($spreadsheet_id, $main_sheet->getProperties()->getTitle());
			$sheetValues = current($responseGetValues->getValueRanges())->getValues();
			
			//$endIndexRow =
			$endRowIndex = count($sheetValues);
			$endColumnIndex = count($sheetValues[0]);

			$startIndex = 2;
			$offset_slice = 1;
			if (empty($sheetValues[1]) || empty($sheetValues[1][0])) {
				$startIndex = 3;
				$offset_slice = 2;
			}

			$sheetValues = array_slice($sheetValues, $offset_slice);

			$dates = array();
			foreach($sheetValues as $k => $v){
				
				$fecha = date_create_from_format('d-M-y H:i',$v[1]." ".$v[2]);
				$dates[$k] = strtotime($fecha->format('Y-m-d H:i'));
			
				
			}

			array_multisort($dates, SORT_ASC, $sheetValues);

			
			
			$service->spreadsheets_values->batchClearByDataFilter($spreadsheet_id,new Google_Service_Sheets_BatchClearValuesByDataFilterRequest([
				"dataFilters" => [
					[
						"gridRange" => [
							"sheetId" => $main_sheet_id,
							"startRowIndex" => $startIndex-1,
							"startColumnIndex" => 0

						]
					]
				]
			]));
			

			$update_arr = array();
			foreach ($sheetValues as $roWValue) {

				if (empty($roWValue[3])) {
					break;
				}

				$roWValue = array_merge($roWValue, array_fill(count($roWValue),$endColumnIndex-count($roWValue),"") );
				$update_arr[] = $roWValue;
				
			}

			$update_arr = array_map(function($n) {
					
				$f_idx = $this->columnIndexFromString('F') -1;
				$n_idx = $this->columnIndexFromString('N') -1;
				$ay_idx = $this->columnIndexFromString('AY') -1;
				$az_idx = $this->columnIndexFromString('AZ') -1;

				$n[$f_idx] = "";	
				$n[$m_idx] = "";
				$n[$ay_idx] = "";		
				$n[$az_idx] = "";					
				
				return $n;

			},$update_arr);


		
			$response = $this->google_snippets->updateValues($spreadsheet_id, $main_sheet->getProperties()->getTitle()."!A".$startIndex.":".$this->stringFromColumnIndex($endColumnIndex).$endRowIndex, "USER_ENTERED", $update_arr);

			$this->fixMetadata();
			
		}

	}

	public function createShippingsSheet(){
		
		try{
			
			$google_client = new WC_Vapelab_Sheets_Connector_Google_Client();
			
			$google_client->client->addScope(Google\Service\Drive::DRIVE);
			
			$service = new Google_Service_Sheets($google_client->client);
	
			$spreadsheet = new Google_Service_Sheets_Spreadsheet([
				'properties' => [
					'title' => 'VapeLab-Woo Shipping Sync',
				]
			]);

			$createResponse =$this->service->spreadsheets->create($spreadsheet, [
				'fields' => 'spreadsheetId'
			]);
			
			$spreadsheet_id = $createResponse->spreadsheetId;
			
			$response =$this->service->spreadsheets->get($spreadsheet_id);
			
			$requests = array(
				
				new Google_Service_Sheets_Request([
					'addSheet' => [
						'properties' => [
							'title' => 'Envios'
						]
					]
				]),

				new Google_Service_Sheets_Request([
					'addSheet' => [
						'properties' => [
							'title' => 'Historial'
						]
					]
				]),
				
				new Google_Service_Sheets_Request([
					'deleteSheet' => [
						'sheetId' => 0
					]
				]),
			);
			$batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
				'requests' => $requests
			]);
	
			$batchUpdateResponse =$this->service->spreadsheets->batchUpdate($spreadsheet_id, $batchUpdateRequest);
			
		
			return $spreadsheet_id;
			
		}catch(Google\Service\Exception $e){
			
			foreach($e->getErrors() as $error){
				
			
			}
		}
		

		
	}

	public function insertShippingSheet($order_id ){

		//try{
			
			$order = wc_get_order($order_id);
		
			$order_data = $order->get_data();

			$customer = $order->get_user();
			
			if($customer){

				$settings = $this->settings;
				$company_suffix = $settings['company_field'];
				$company_id = $settings['company_id'];
				$spreadsheet_id = $settings['spreadsheet_id'];

				$customer_data = $customer->to_array();

				$order_fees = 0;
				$decomiso_fees = 0;

				// Get fees
				foreach ($order->get_fees() as $fee_id => $fee) {

					$fee_name = $fee['name'];
					$line_total = $fee['line_total'];

					if($fee_name == "Seguro contra decomiso"){
						$decomiso_fees+=$line_total;
					}else{
						$order_fees+=$line_total;
					}
	
				}

				$shipping_method_id = "";
				if (isset($order->get_data()['shipping_lines'])) {
					if (count($order->get_data()['shipping_lines']) > 0) {
						$shipping_method_id = current($order->get_data()['shipping_lines'])->get_data()['method_id'];
					}
				}

				$mensajero = "";
				if (strpos($shipping_method_id, 'enviaya') !== false || $shipping_method_id == 'wc-enviaya-shipping') {
					$mensajero = "Guia";

					if (!$order->meta_exists('mensajero')) {
						$order->add_meta_data('mensajero', $mensajero);
						$order->save();
					}
				}
			
				$country_code_phone = "";
				$shipping_method_id = "";
				$cobro = "";

				$phone = $order->get_shipping_phone();
				if(empty($phone) || is_null($phone)){
					$phone = $order->get_billing_phone();
				}

				$shipping_notes = "";
				if ($order->meta_exists('_shipping_notes')) {
					$shipping_notes =  $order->get_meta('_shipping_notes');
				}
				
				
				$data = array(
					$company_suffix,
					$order_data['date_created']->date('d-M-y'),
					$order_data['date_created']->date('H:i'),
					$order_data['id'],
					$order_data['shipping']['first_name']." ".$order_data['shipping']['last_name'],
					"",
					$order->get_shipping_address_1(),
					$shipping_notes,
					$order->get_shipping_address_2(),
					$order->get_shipping_city(),
					$order->get_shipping_state(),
					"'".$order->get_shipping_postcode(),
					$country_code_phone,
					$phone,
					"", //WA
					$shipping_method_id,
					$order->get_shipping_method(),
					$order_data['payment_method'],
					$order_data['payment_method_title'],
					$order->get_subtotal(),
					$order->get_total_shipping(),
					$decomiso_fees,
					$order_fees,
					$order->get_total(),
					"",
					$mensajero,
					$order->get_status(),
					"",
					"",
					"",
					"", // etiqueta
					"",
					"",
					"",
					"",
					"",
					"",
					"",
					$cobro,
				);
				
				while(count($data) < $this->columnIndexFromString('AX')){
					$data[] = "";
				}

				$body = new Google_Service_Sheets_ValueRange([
					"majorDimension" => "ROWS",
					'values' => array($data),
				]);

				$params = [
					'valueInputOption' => 'USER_ENTERED',
					'insertDataOption' => 'INSERT_ROWS'
				];

				$spreadsheet = $this->google_snippets->getSpreadsheet($spreadsheet_id); 
				
				$main_sheet;
				
				foreach($spreadsheet->getSheets() as $sheet){

					$sheet_properties = $sheet->getProperties();

					if($sheet_properties->getSheetId() == $settings['main_sheet_id']){
						$main_sheet = $sheet;
						break;
					}
					
				}

				$sheet_title = $main_sheet->getProperties()->title;

				$a1range = $sheet_title . "!A3";
				
				$result =$this->service->spreadsheets_values->append($spreadsheet_id, $a1range, $body, $params);
				
				if(isset($result->updates) && !is_null($result->updates->updatedRange)){

					$order->add_meta_data('sheet_updated_range',$result->updates->updatedRange);
					$order->save();
					

					/*
					preg_match( '/!A\d+:[A-Z]{2}(\d+)/', $result->updates->updatedRange, $matches);
					$start_index = (int) $matches[1] - 1;
					$end_index = (int) $matches[1];

					
					$createDeveloperMetadataRequests[] = new Google_Service_Sheets_Request([
						'createDeveloperMetadata' => [
							'developerMetadata' => [
								'metadataId' => $company_id.$order_id,
								'metadataKey' => 'id',
								'metadataValue' => (string) $order_id,
								'location' => [
									"dimensionRange" => [
										"sheetId" => $main_sheet->getProperties()->getSheetId(),
										"dimension"=> "ROWS",
										"startIndex"=> $start_index,
										"endIndex"=> $end_index
									],
								],
								'visibility' => "DOCUMENT"
							]
						],

					]);

					$body = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
						'requests' => $createDeveloperMetadataRequests

					]);
					
					$this->service->spreadsheets->batchUpdate($spreadsheet_id, $body);
					*/
				
					
				}

			

				
				
			}

		//}catch(Google\Service\Exception $e){
			
	
		//}
		
		
	
	}
	
	public function updateShippingSheet($order_id, $create_metadata = false){


		$order = wc_get_order($order_id);

		$order_data = $order->get_data();
		
		$customer = $order->get_user();
		
		if(!is_null($customer)){
			
			$settings = $this->settings;
			
			if($create_metadata){

				if ($order->meta_exists('sheet_updated_range')) {

					$sheet_updated_range = $order->get_meta('sheet_updated_range');

					preg_match( '/!A\d+:[A-Z]{2}(\d+)/', $sheet_updated_range, $matches);
				
					$start_index = (int) $matches[1] - 1;
					$end_index = (int) $matches[1];

					$createDeveloperMetadataRequests[] = new Google_Service_Sheets_Request([
						'createDeveloperMetadata' => [
							'developerMetadata' => [
								'metadataId' => $settings['company_id'].$order_id,
								'metadataKey' => 'id',
								'metadataValue' => (string) $order_id,
								'location' => [
									"dimensionRange" => [
										"sheetId" => $settings['main_sheet_id'],
										"dimension"=> "ROWS",
										"startIndex"=> $start_index,
										"endIndex"=> $end_index
									],
								],
								'visibility' => "DOCUMENT"
							]
						],

					]);

					$body = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
						'requests' => $createDeveloperMetadataRequests

					]);

					$res = $this->service->spreadsheets->batchUpdate($settings['spreadsheet_id'], $body);
					

				}

			}

			$searchResponse =$this->service->spreadsheets_developerMetadata->search($settings['spreadsheet_id'], new Google_Service_Sheets_SearchDeveloperMetadataRequest([
				"dataFilters" => [
					[
						"developerMetadataLookup" => [
							'metadataId' => $settings['company_id'].$order_id
						]
					]
				]
			]));
			
			if (!is_null($searchResponse->matchedDeveloperMetadata)) {

				$endIndex = $searchResponse->matchedDeveloperMetadata[0]->developerMetadata->location->dimensionRange->endIndex;
				
				$rowData = $this->google_snippets->getValues($settings['spreadsheet_id'],$endIndex.":".$endIndex)->values[0];
				
				$order->update_meta_data( 'hr_entrega', $rowData[27] );
				$order->update_meta_data( 'hr_update', $rowData[28] );
				$order->update_meta_data( 'terminado', $rowData[29] );
				
				$customer_data = $customer->to_array();

				$order_fees = 0;
				$decomiso_fees = 0;

				// Get fees
				foreach ($order->get_fees() as $fee_id => $fee) {

					$fee_name = $fee['name'];
					$line_total = $fee['line_total'];

					if($fee_name == "Seguro contra decomiso"){
						$decomiso_fees+=$line_total;
					}else{
						$order_fees+=$line_total;
					}
	
				}


				
				$phone = $order->get_shipping_phone();
				if (empty($phone) || is_null($phone)) {
					$phone = $order->get_billing_phone();
				}

				$efectivo_real = "";
				if ($order->meta_exists('efectivo_real')) {
					$efectivo_real = $order->get_meta('efectivo_real');
				}


				$cobro="";
				if ($order->meta_exists('cobro')) {
					$cobro = $order->get_meta('cobro');
				}

				$shipping_method_id = "";
				if (isset($order->get_data()['shipping_lines'])) {
					if (count($order->get_data()['shipping_lines']) > 0) {
						$shipping_method_id = current($order->get_data()['shipping_lines'])->get_data()['method_id'];
					}
				}

				$mensajero = "";
				if ($order->meta_exists('mensajero')) {
					$mensajero = $order->get_meta('mensajero');
				} else {
					if (strpos($shipping_method_id, 'enviaya') !== false || $shipping_method_id == 'wc-enviaya-shipping') {
						$mensajero = "Guia";

						if (!$order->meta_exists('mensajero')) {
							$order->add_meta_data('mensajero', $mensajero);
							$order->save();
						}
					}
				}

				$hr_entrega = "";
				if ($order->meta_exists('hr_entrega')) {
					$hr_entrega = $order->get_meta('hr_entrega');
				}

				$hr_update = "";
				if ($order->meta_exists('hr_update')) {
					$hr_update = $order->get_meta('hr_update');
				}

				$terminado = "";
				if ($order->meta_exists('terminado')) {
					$terminado = $order->get_meta('terminado');
				}

				$link_clip = "";
				if ($order->meta_exists('link_clip')) {
					$link_clip = $order->get_meta('link_clip');
				} else {
					if ($order_data['payment_method'] == "clip_payment_gateway" || $order_data['payment_method'] == "wc_clip") {
						$private_order_notes = wc_get_order_notes([
							'order_id' => $order_data['id'],
							'type' => 'internal', // only get private notes
						]);

						foreach ($private_order_notes as $note) {
							if (preg_match("/https:\/\/completa-tu-pago2.payclip.com\/\S*/", $note->content, $ccoincidencias)) {
								$link_clip = current($ccoincidencias);
							}
						}
					}
				}

				$propinas = "";
				if ($order->meta_exists('propinas')) {
					$propinas = $order->get_meta('propinas');
				}

				$link_etiqueta = "";

				if (!is_null($order->get_items('shipping'))) {
					if (count($order->get_items('shipping')) > 0) {
						$item_shipping = current($order->get_items('shipping'));

						if (!empty($item_shipping->get_meta('booking'))) {
							$shipment = $item_shipping->get_meta('booking');
							$carrier_name = strtolower($shipment['carrier']);

							if ($carrier_name == 'dhl') {
								$link_etiqueta = "https://www.dhl.com/us-en/home/tracking/tracking-express.html?submit=1&tracking-id=".$shipment['carrier_shipment_number'];
							} elseif ($carrier_name == 'estafeta') {
								$link_etiqueta = "https://cs.estafeta.com/es/Tracking/searchByGet?wayBillType=1&wayBill=".$shipment['carrier_shipment_number'];
							} elseif ($carrier_name == 'redpack') {
								$link_etiqueta = "https://www.redpack.com.mx/es/rastreo/?guias=".$shipment['carrier_shipment_number'];
							} elseif ($carrier_name == 'fedex') {
								$link_etiqueta = "https://www.fedex.com/fedextrack/?trknbr=".$shipment['carrier_shipment_number'];
							}
						}
					}
				}


				$total_usd = "";
				if ($order->meta_exists('vl_usd_ex_rate')) {
					
					$usd_ex_rate = $order->get_meta('vl_usd_ex_rate');
					
					$total_usd = $order->get_total() / $usd_ex_rate;

					$total_usd = number_format($total_usd,2,".","");

				}

				$cobro = "";
				if (file_exists(WP_PLUGIN_DIR.'/wc-vapelab-sheets-connector-rules/shipping_rules.php')) {

					include WP_PLUGIN_DIR.'/wc-vapelab-sheets-connector-rules/shipping_rules.php'; 
					
					if(!empty($cobro)){
	
						if (!$order->meta_exists('cobro')) {
							$order->add_meta_data('cobro',$cobro);
							$order->save();
						}
	
					}
	
				}

				$customer_id = $order->get_customer_id();
				$customer = new \WC_Customer( $customer_id );

				$allCountries = [ [ "Afghanistan ", "af", "93" ], [ "Albania ", "al", "355" ], [ "Algeria ", "dz", "213" ], [ "Andorra", "ad", "376" ], [ "Angola", "ao", "244" ], [ "Anguilla", "ai", "1", 6, [ "264" ] ], [ "Antigua and Barbuda", "ag", "1", 7, [ "268" ] ], [ "Argentina", "ar", "549" ], [ "Armenia ", "am", "374" ], [ "Aruba", "aw", "297" ], [ "Ascension Island", "ac", "247" ], [ "Australia", "au", "61", 0 ], [ "Austria ", "at", "43" ], [ "Azerbaijan ", "az", "994" ], [ "Bahamas", "bs", "1", 8, [ "242" ] ], [ "Bahrain ", "bh", "973" ], [ "Bangladesh ", "bd", "880" ], [ "Barbados", "bb", "1", 9, [ "246" ] ], [ "Belarus ", "by", "375" ], [ "Belgium ", "be", "32" ], [ "Belize", "bz", "501" ], [ "Benin ", "bj", "229" ], [ "Bermuda", "bm", "1", 10, [ "441" ] ], [ "Bhutan ", "bt", "975" ], [ "Bolivia", "bo", "591" ], [ "Bosnia and Herzegovina ", "ba", "387" ], [ "Botswana", "bw", "267" ], [ "Brazil ", "br", "55" ], [ "British Indian Ocean Territory", "io", "246" ], [ "British Virgin Islands", "vg", "1", 11, [ "284" ] ], [ "Brunei", "bn", "673" ], [ "Bulgaria ", "bg", "359" ], [ "Burkina Faso", "bf", "226" ], [ "Burundi ", "bi", "257" ], [ "Cambodia ", "kh", "855" ], [ "Cameroon ", "cm", "237" ], [ "Canada", "ca", "1", 1, [ "204", "226", "236", "249", "250", "289", "306", "343", "365", "387", "403", "416", "418", "431", "437", "438", "450", "506", "514", "519", "548", "579", "581", "587", "604", "613", "639", "647", "672", "705", "709", "742", "778", "780", "782", "807", "819", "825", "867", "873", "902", "905" ] ], [ "Cape Verde ", "cv", "238" ], [ "Caribbean Netherlands", "bq", "599", 1, [ "3", "4", "7" ] ], [ "Cayman Islands", "ky", "1", 12, [ "345" ] ], [ "Central African Republic ", "cf", "236" ], [ "Chad ", "td", "235" ], [ "Chile", "cl", "56" ], [ "China ", "cn", "86" ], [ "Christmas Island", "cx", "61", 2, [ "89164" ] ], [ "Cocos  Islands", "cc", "61", 1, [ "89162" ] ], [ "Colombia", "co", "57" ], [ "Comoros ", "km", "269" ], [ "Congo  ", "cd", "243" ], [ "Congo  ", "cg", "242" ], [ "Cook Islands", "ck", "682" ], [ "Costa Rica", "cr", "506" ], [ "Côte d’Ivoire", "ci", "225" ], [ "Croatia ", "hr", "385" ], [ "Cuba", "cu", "53" ], [ "Curaçao", "cw", "599", 0 ], [ "Cyprus ", "cy", "357" ], [ "Czech Republic ", "cz", "420" ], [ "Denmark ", "dk", "45" ], [ "Djibouti", "dj", "253" ], [ "Dominica", "dm", "1", 13, [ "767" ] ], [ "Dominican Republic ", "do", "1", 2, [ "809", "829", "849" ] ], [ "Ecuador", "ec", "593" ], [ "Egypt ", "eg", "20" ], [ "El Salvador", "sv", "503" ], [ "Equatorial Guinea ", "gq", "240" ], [ "Eritrea", "er", "291" ], [ "Estonia ", "ee", "372" ], [ "Eswatini", "sz", "268" ], [ "Ethiopia", "et", "251" ], [ "Falkland Islands ", "fk", "500" ], [ "Faroe Islands ", "fo", "298" ], [ "Fiji", "fj", "679" ], [ "Finland ", "fi", "358", 0 ], [ "France", "fr", "33" ], [ "French Guiana ", "gf", "594" ], [ "French Polynesia ", "pf", "689" ], [ "Gabon", "ga", "241" ], [ "Gambia", "gm", "220" ], [ "Georgia ", "ge", "995" ], [ "Germany ", "de", "49" ], [ "Ghana ", "gh", "233" ], [ "Gibraltar", "gi", "350" ], [ "Greece ", "gr", "30" ], [ "Greenland ", "gl", "299" ], [ "Grenada", "gd", "1", 14, [ "473" ] ], [ "Guadeloupe", "gp", "590", 0 ], [ "Guam", "gu", "1", 15, [ "671" ] ], [ "Guatemala", "gt", "502" ], [ "Guernsey", "gg", "44", 1, [ "1481", "7781", "7839", "7911" ] ], [ "Guinea ", "gn", "224" ], [ "Guinea-Bissau ", "gw", "245" ], [ "Guyana", "gy", "592" ], [ "Haiti", "ht", "509" ], [ "Honduras", "hn", "504" ], [ "Hong Kong ", "hk", "852" ], [ "Hungary ", "hu", "36" ], [ "Iceland ", "is", "354" ], [ "India ", "in", "91" ], [ "Indonesia", "id", "62" ], [ "Iran ", "ir", "98" ], [ "Iraq ", "iq", "964" ], [ "Ireland", "ie", "353" ], [ "Isle of Man", "im", "44", 2, [ "1624", "74576", "7524", "7924", "7624" ] ], [ "Israel ", "il", "972" ], [ "Italy ", "it", "39", 0 ], [ "Jamaica", "jm", "1", 4, [ "876", "658" ] ], [ "Japan ", "jp", "81" ], [ "Jersey", "je", "44", 3, [ "1534", "7509", "7700", "7797", "7829", "7937" ] ], [ "Jordan ", "jo", "962" ], [ "Kazakhstan ", "kz", "7", 1, [ "33", "7" ] ], [ "Kenya", "ke", "254" ], [ "Kiribati", "ki", "686" ], [ "Kosovo", "xk", "383" ], [ "Kuwait ", "kw", "965" ], [ "Kyrgyzstan ", "kg", "996" ], [ "Laos ", "la", "856" ], [ "Latvia ", "lv", "371" ], [ "Lebanon ", "lb", "961" ], [ "Lesotho", "ls", "266" ], [ "Liberia", "lr", "231" ], [ "Libya ", "ly", "218" ], [ "Liechtenstein", "li", "423" ], [ "Lithuania ", "lt", "370" ], [ "Luxembourg", "lu", "352" ], [ "Macau ", "mo", "853" ], [ "North Macedonia ", "mk", "389" ], [ "Madagascar ", "mg", "261" ], [ "Malawi", "mw", "265" ], [ "Malaysia", "my", "60" ], [ "Maldives", "mv", "960" ], [ "Mali", "ml", "223" ], [ "Malta", "mt", "356" ], [ "Marshall Islands", "mh", "692" ], [ "Martinique", "mq", "596" ], [ "Mauritania ", "mr", "222" ], [ "Mauritius ", "mu", "230" ], [ "Mayotte", "yt", "262", 1, [ "269", "639" ] ], [ "Mexico ", "mx", "521" ], [ "Micronesia", "fm", "691" ], [ "Moldova ", "md", "373" ], [ "Monaco", "mc", "377" ], [ "Mongolia ", "mn", "976" ], [ "Montenegro ", "me", "382" ], [ "Montserrat", "ms", "1", 16, [ "664" ] ], [ "Morocco ", "ma", "212", 0 ], [ "Mozambique ", "mz", "258" ], [ "Myanmar  ", "mm", "95" ], [ "Namibia ", "na", "264" ], [ "Nauru", "nr", "674" ], [ "Nepal ", "np", "977" ], [ "Netherlands ", "nl", "31" ], [ "New Caledonia ", "nc", "687" ], [ "New Zealand", "nz", "64" ], [ "Nicaragua", "ni", "505" ], [ "Niger ", "ne", "227" ], [ "Nigeria", "ng", "234" ], [ "Niue", "nu", "683" ], [ "Norfolk Island", "nf", "672" ], [ "North Korea ", "kp", "850" ], [ "Northern Mariana Islands", "mp", "1", 17, [ "670" ] ], [ "Norway ", "no", "47", 0 ], [ "Oman ", "om", "968" ], [ "Pakistan ", "pk", "92" ], [ "Palau", "pw", "680" ], [ "Palestine ", "ps", "970" ], [ "Panama ", "pa", "507" ], [ "Papua New Guinea", "pg", "675" ], [ "Paraguay", "py", "595" ], [ "Peru ", "pe", "51" ], [ "Philippines", "ph", "63" ], [ "Poland ", "pl", "48" ], [ "Portugal", "pt", "351" ], [ "Puerto Rico", "pr", "1", 3, [ "787", "939" ] ], [ "Qatar ", "qa", "974" ], [ "Réunion ", "re", "262", 0 ], [ "Romania ", "ro", "40" ], [ "Russia ", "ru", "7", 0 ], [ "Rwanda", "rw", "250" ], [ "Saint Barthélemy", "bl", "590", 1 ], [ "Saint Helena", "sh", "290" ], [ "Saint Kitts and Nevis", "kn", "1", 18, [ "869" ] ], [ "Saint Lucia", "lc", "1", 19, [ "758" ] ], [ "Saint Martin )", "mf", "590", 2 ], [ "Saint Pierre and Miquelon ", "pm", "508" ], [ "Saint Vincent and the Grenadines", "vc", "1", 20, [ "784" ] ], [ "Samoa", "ws", "685" ], [ "San Marino", "sm", "378" ], [ "São Tomé and Príncipe ", "st", "239" ], [ "Saudi Arabia ", "sa", "966" ], [ "Senegal ", "sn", "221" ], [ "Serbia ", "rs", "381" ], [ "Seychelles", "sc", "248" ], [ "Sierra Leone", "sl", "232" ], [ "Singapore", "sg", "65" ], [ "Sint Maarten", "sx", "1", 21, [ "721" ] ], [ "Slovakia ", "sk", "421" ], [ "Slovenia ", "si", "386" ], [ "Solomon Islands", "sb", "677" ], [ "Somalia ", "so", "252" ], [ "South Africa", "za", "27" ], [ "South Korea ", "kr", "82" ], [ "South Sudan ", "ss", "211" ], [ "Spain ", "es", "34" ], [ "Sri Lanka ", "lk", "94" ], [ "Sudan ", "sd", "249" ], [ "Suriname", "sr", "597" ], [ "Svalbard and Jan Mayen", "sj", "47", 1, [ "79" ] ], [ "Sweden ", "se", "46" ], [ "Switzerland ", "ch", "41" ], [ "Syria ", "sy", "963" ], [ "Taiwan ", "tw", "886" ], [ "Tajikistan", "tj", "992" ], [ "Tanzania", "tz", "255" ], [ "Thailand ", "th", "66" ], [ "Timor-Leste", "tl", "670" ], [ "Togo", "tg", "228" ], [ "Tokelau", "tk", "690" ], [ "Tonga", "to", "676" ], [ "Trinidad and Tobago", "tt", "1", 22, [ "868" ] ], [ "Tunisia ", "tn", "216" ], [ "Turkey ", "tr", "90" ], [ "Turkmenistan", "tm", "993" ], [ "Turks and Caicos Islands", "tc", "1", 23, [ "649" ] ], [ "Tuvalu", "tv", "688" ], [ "U.S. Virgin Islands", "vi", "1", 24, [ "340" ] ], [ "Uganda", "ug", "256" ], [ "Ukraine ", "ua", "380" ], [ "United Arab Emirates ", "ae", "971" ], [ "United Kingdom", "gb", "44", 0 ], [ "United States", "us", "1", 0 ], [ "Uruguay", "uy", "598" ], [ "Uzbekistan ", "uz", "998" ], [ "Vanuatu", "vu", "678" ], [ "Vatican City ", "va", "39", 1, [ "06698" ] ], [ "Venezuela", "ve", "58" ], [ "Vietnam ", "vn", "84" ], [ "Wallis and Futuna ", "wf", "681" ], [ "Western Sahara ", "eh", "212", 1, [ "5288", "5289" ] ], [ "Yemen ", "ye", "967" ], [ "Zambia", "zm", "260" ], [ "Zimbabwe", "zw", "263" ], [ "Åland Islands", "ax", "358", 1, [ "18" ] ] ];


				$country_code = "mx";
				$country_code_phone = "521";
				if ($order->meta_exists('shipping_country_phone_code')) {

					$country_code_phone = $order->get_meta('shipping_country_phone_code');

					foreach($allCountries as $country_arr){
						if($country_arr[2] == $country_code_phone){
							$country_code = $country_arr[1];

							$order->update_meta_data( 'billing_country_code', sanitize_text_field( $_POST['billing_phone_code'] ) );
							$order->update_meta_data( 'shipping_country_code', sanitize_text_field(  $_POST['billing_phone_code'] ) );

							

							$customer->update_meta_data( 'default_country_code', $country_code );
							$customer->save();

							break;
						}
					}

				}

				$shipping_notes = "";
				if ($order->meta_exists('_shipping_notes')) {
					$shipping_notes =  $order->get_meta('_shipping_notes');
				}

				$customer->set_billing_address_1($order->get_shipping_address_1());
				$customer->set_billing_address_2($order->get_shipping_address_2());
				$customer->set_billing_city($order->get_shipping_city());
				$customer->set_billing_state($order->get_shipping_state());
				$customer->set_billing_postcode($order->get_shipping_postcode());
				$customer->update_meta_data( 'billing_notes', $shipping_notes );

				$customer->set_shipping_address_1($order->get_shipping_address_1());
				$customer->set_shipping_address_2($order->get_shipping_address_2());
				$customer->set_shipping_city($order->get_shipping_city());
				$customer->set_shipping_state($order->get_shipping_state());
				$customer->set_shipping_postcode($order->get_shipping_postcode());
				$customer->update_meta_data( 'shipping_notes', $shipping_notes );

				$customer->set_billing_phone($phone);
				$customer->set_shipping_phone($phone);

				$customer->save();

				$data = array(
					$settings['company_field'],
					$order_data['date_created']->date('d-M-y'),
					$order_data['date_created']->date('H:i'),
					$order_data['id'],
					$order_data['shipping']['first_name']." ".$order_data['shipping']['last_name'],
					"",
					$order->get_shipping_address_1(),
					$shipping_notes,
					$order->get_shipping_address_2(),
					$order->get_shipping_city(),
					$order->get_shipping_state(),
					"'".$order->get_shipping_postcode(),
					$country_code_phone,
					$phone,
					"", //WA
					$shipping_method_id,
					$order->get_shipping_method(),
					$order_data['payment_method'],
					$order_data['payment_method_title'],
					$order->get_subtotal(),
					$order->get_total_shipping(),
					$decomiso_fees,
					$order_fees,
					$order->get_total(),
					$efectivo_real,
					$mensajero,
					$order->get_status(),
					$hr_entrega,
					$hr_update,
					$terminado,
					$link_etiqueta,
					$link_clip,
					$total_usd,
					"",
					"",
					"",
					"",
					"",
					$cobro,
				);

				$request = array(
					"dataFilter"=> [
						"developerMetadataLookup"=> [
							"metadataId"=> $settings['company_id'].$order_id,
						]
					],
					"majorDimension"=> "ROWS",
					"values"=> [
						$data
					]
				);

				$res =$this->service->spreadsheets_values->batchUpdateByDataFilter(
					$settings['spreadsheet_id'],
					new Google_Service_Sheets_BatchUpdateValuesByDataFilterRequest([
						"data"=> array($request),
						"includeValuesInResponse"=> true,
						"valueInputOption"=> "USER_ENTERED"
					]),
				);
			}


		}

		return $res;

	}

	public static function stringFromColumnIndex($columnIndex)
    {
        static $indexCache = [];
        static $lookupCache = ' ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        if (!isset($indexCache[$columnIndex])) {
            $indexValue = $columnIndex;
            $base26 = '';
            do {
                $characterValue = ($indexValue % 26) ?: 26;
                $indexValue = ($indexValue - $characterValue) / 26;
                $base26 = $lookupCache[$characterValue] . $base26;
            } while ($indexValue > 0);
            $indexCache[$columnIndex] = $base26;
        }

        return $indexCache[$columnIndex];
    }

	public static function columnIndexFromString($columnAddress)
    {
        //    Using a lookup cache adds a slight memory overhead, but boosts speed
        //    caching using a static within the method is faster than a class static,
        //        though it's additional memory overhead
        static $indexCache = [];

        if (isset($indexCache[$columnAddress])) {
            return $indexCache[$columnAddress];
        }
        //    It's surprising how costly the strtoupper() and ord() calls actually are, so we use a lookup array
        //        rather than use ord() and make it case insensitive to get rid of the strtoupper() as well.
        //        Because it's a static, there's no significant memory overhead either.
        static $columnLookup = [
            'A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5, 'F' => 6, 'G' => 7, 'H' => 8, 'I' => 9, 'J' => 10,
            'K' => 11, 'L' => 12, 'M' => 13, 'N' => 14, 'O' => 15, 'P' => 16, 'Q' => 17, 'R' => 18, 'S' => 19,
            'T' => 20, 'U' => 21, 'V' => 22, 'W' => 23, 'X' => 24, 'Y' => 25, 'Z' => 26,
            'a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5, 'f' => 6, 'g' => 7, 'h' => 8, 'i' => 9, 'j' => 10,
            'k' => 11, 'l' => 12, 'm' => 13, 'n' => 14, 'o' => 15, 'p' => 16, 'q' => 17, 'r' => 18, 's' => 19,
            't' => 20, 'u' => 21, 'v' => 22, 'w' => 23, 'x' => 24, 'y' => 25, 'z' => 26,
        ];

        //    We also use the language construct isset() rather than the more costly strlen() function to match the
        //       length of $columnAddress for improved performance
        if (isset($columnAddress[0])) {
            if (!isset($columnAddress[1])) {
                $indexCache[$columnAddress] = $columnLookup[$columnAddress];

                return $indexCache[$columnAddress];
            } elseif (!isset($columnAddress[2])) {
                $indexCache[$columnAddress] = $columnLookup[$columnAddress[0]] * 26
                    + $columnLookup[$columnAddress[1]];

                return $indexCache[$columnAddress];
            } elseif (!isset($columnAddress[3])) {
                $indexCache[$columnAddress] = $columnLookup[$columnAddress[0]] * 676
                    + $columnLookup[$columnAddress[1]] * 26
                    + $columnLookup[$columnAddress[2]];

                return $indexCache[$columnAddress];
            }
        }

        throw new Exception(
            'Column string index can not be ' . ((isset($columnAddress[0])) ? 'longer than 3 characters' : 'empty')
        );
    }

	public function deleteMetadata(){

		$settings = get_option( "wc_shipping_sheets_vapelab_settings" );

			$spreadsheet_id = $settings['spreadsheet_id'];
			
			$main_sheet_id =   $settings['main_sheet_id'];
			
			$google_client = new WC_Vapelab_Sheets_Connector_Google_Client();

			$google_client->client->addScope(Google\Service\Drive::DRIVE);

			$service = new Google_Service_Sheets($google_client->client);

			$spreadsheet_snippets = $this->google_snippets;

			
			
			$responseGetSheet =$this->service->spreadsheets->get($spreadsheet_id);


			foreach ($responseGetSheet->getSheets() as $sheet) {

				$sheet_properties = $sheet->getProperties();

				if ($sheet_properties->getSheetId() == $main_sheet_id) {
					
					$main_sheet = $sheet;
					break;
					
				}


			}
			
			if (isset($main_sheet)) {
				$responseMeta =$this->service->spreadsheets_developerMetadata->search($spreadsheet_id, new Google_Service_Sheets_SearchDeveloperMetadataRequest([
					"dataFilters" => [
						[
							"developerMetadataLookup"=> [
								"locationType" =>  "ROW",
								"metadataLocation" => [
									"sheetId" => $main_sheet_id,
								],
							]
						]
					]
				]));

				$deleteDeveloperMetadataRequests = array();

				foreach ($responseMeta->matchedDeveloperMetadata as $matchedDeveloperMetadata) {
					$developerMetadata = $matchedDeveloperMetadata->developerMetadata;
					$deleteDeveloperMetadataRequests[] = [
						"deleteDeveloperMetadata" => [
							"dataFilter" => [
								"developerMetadataLookup"=> [
									"metadataId" => $developerMetadata->metadataId,
								]
							]
						]
					];
				}

				if (!empty($deleteDeveloperMetadataRequests)) {
					$responseMetadataDel =$this->service->spreadsheets->batchUpdate($spreadsheet_id, new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
						'requests' => $deleteDeveloperMetadataRequests
					]));
				}
			}

	}

	public function fixMetadata(){

			$settings = $this->settings;

			$spreadsheet_id = $settings['spreadsheet_id'];
			
			$main_sheet_id =   $settings['main_sheet_id'];
			
			
			$responseGetSheet =$this->service->spreadsheets->get($spreadsheet_id);


			foreach ($responseGetSheet->getSheets() as $sheet) {

				$sheet_properties = $sheet->getProperties();

				if ($sheet_properties->getSheetId() == $main_sheet_id) {
					
					$main_sheet = $sheet;
					break;
					
				}


			}
			
			if(isset($main_sheet) ){

				
				$responseMeta =$this->service->spreadsheets_developerMetadata->search($spreadsheet_id, new Google_Service_Sheets_SearchDeveloperMetadataRequest([
					"dataFilters" => [
						[
							"developerMetadataLookup"=> [
								"locationType" =>  "ROW",
								"metadataLocation" => [
									"sheetId" => $main_sheet_id,
								],
							]
						]
					]
				]));
				
				$deleteDeveloperMetadataRequests = array();
				
				foreach($responseMeta->matchedDeveloperMetadata as $matchedDeveloperMetadata){
					$developerMetadata = $matchedDeveloperMetadata->developerMetadata;
					$deleteDeveloperMetadataRequests[] = [
						"deleteDeveloperMetadata" => [
							"dataFilter" => [
								"developerMetadataLookup"=> [
									"metadataId" => $developerMetadata->metadataId,
								]
							]
						]
					];
				}
	
				if(!empty($deleteDeveloperMetadataRequests)){
	
					$responseMetadataDel =$this->service->spreadsheets->batchUpdate($spreadsheet_id, new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
						'requests' => $deleteDeveloperMetadataRequests
					]));
	
				}
				
				$createDeveloperMetadataRequests = array();
				
				$responseGetValues = $this->google_snippets->batchGetValues($spreadsheet_id,$main_sheet->getProperties()->getTitle());
	
				$sheetValues = current($responseGetValues->getValueRanges())->getValues();
				
				$startIndex = 1;
				$offset_slice = 1;
				if(empty($sheetValues[1]) || empty($sheetValues[1][0])){
					$startIndex = 2;
					$offset_slice = 2;
				}
	
				$sheetValues = array_slice($sheetValues,$offset_slice);
				
				$empresas_map = array(
					'VL' => 1,
					'TV' => 2,
					'DEV' => 3
				);

				foreach($sheetValues as $roWValue){
					
					if(empty($roWValue[3])){
						break;
					}

					$empresa_code = $roWValue[0];
					$empresa_id = $empresas_map[$empresa_code];
	
					$createDeveloperMetadataRequests[] = new Google_Service_Sheets_Request([
						'createDeveloperMetadata' => [
							'developerMetadata' => [
								'metadataId' => $empresa_id.$roWValue[3],
								'metadataKey' => 'id',
								'metadataValue' => (string) $roWValue[3],
								'location' => [
									"dimensionRange" => [
										"sheetId" => $main_sheet_id,
										"dimension"=> "ROWS",
										"startIndex"=> $startIndex,
										"endIndex"=> $startIndex+1
									]
							
								],
								'visibility' => "DOCUMENT"
							]
						],
	
					]);
				
					$startIndex++;
				}
				
				$responseMeta =$this->service->spreadsheets->batchUpdate($spreadsheet_id, new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
					'requests' => $createDeveloperMetadataRequests
				]));
			
			}
			
			
	}

	public function  moveShippingsToHistory(){


			$settings = $this->settings;

			$spreadsheet_id = $settings['spreadsheet_id'];

			$main_sheet_id =   $settings['main_sheet_id'];

			$move_sheet_id =   $settings['sheet_id_move'];

			$column_target_content = $settings['move_target_content'];

			$column_target_idx = $this->columnIndexFromString($settings['move_target_col']) - 1;

			$google_client = new WC_Vapelab_Sheets_Connector_Google_Client();

			$google_client->client->addScope(Google\Service\Drive::DRIVE);

			$service = new Google_Service_Sheets($google_client->client);

			$spreadsheet_snippets = $this->google_snippets;
			
			//GET VALUES


			$responseGetSheet =$this->service->spreadsheets->get($spreadsheet_id);
			

			foreach ($responseGetSheet->getSheets() as $sheet) {
				$sheet_properties = $sheet->getProperties();

				if ($sheet_properties->getSheetId() == $main_sheet_id) {
					$main_sheet = $sheet;
				}

				if ($sheet_properties->getSheetId() == $move_sheet_id) {
					$move_sheet = $sheet;
				}
			}

			if (isset($main_sheet) && isset($move_sheet)) {
				$responseGetValues = $this->google_snippets->batchGetValues($spreadsheet_id, $main_sheet->getProperties()->getTitle());
				$sheetValues = current($responseGetValues->getValueRanges())->getValues();
				
				//$endIndexRow =
				$endRowIndex = count($sheetValues);
				$endColumnIndex = count($sheetValues[0]);

				$startIndex = 2;
				$offset_slice = 1;
				if (empty($sheetValues[1]) || empty($sheetValues[1][0])) {
					$startIndex = 3;
					$offset_slice = 2;
				}

				$sheetValues = array_slice($sheetValues, $offset_slice);
				
				$update_arr = array();
				$move_arr = array();

				
				
				foreach ($sheetValues as $roWValue) {

					if (empty($roWValue[3])) {
						break;
					}

					$roWValue = array_merge($roWValue, array_fill(count($roWValue),$endColumnIndex-count($roWValue),"") );
					
					
					if (isset($roWValue[$column_target_idx]) && $roWValue[$column_target_idx] == $column_target_content) {
						$move_arr[] = $roWValue;
					} else {
						$update_arr[] = $roWValue;
					}

				}
				
				if(!empty($move_arr)){

				
					$responseClear =$this->service->spreadsheets_values->batchClearByDataFilter($spreadsheet_id,new Google_Service_Sheets_BatchClearValuesByDataFilterRequest([
						"dataFilters" => [
							[
								"gridRange" => [
									"sheetId" => $main_sheet_id,
									"startRowIndex" => $startIndex-1,
									"startColumnIndex" => 0
		
								]
							]
						]
					]));
					
					$body = new Google_Service_Sheets_ValueRange([
						"majorDimension" => "ROWS",
						'values' => $move_arr,
					]);
		
					$params = [
						'valueInputOption' => 'USER_ENTERED',
						'insertDataOption' => 'INSERT_ROWS'
					];

					$formulas_idx = array();

					$update_arr = array_map(function($n) {
						
						$f_idx = $this->columnIndexFromString('F') -1;
						$o_idx = $this->columnIndexFromString('O') -1;
						$ba_idx = $this->columnIndexFromString('BA') -1;

						$n[$f_idx] = "";	
						$n[$o_idx] = "";	
						$n[$ba_idx] = "";		
										
						return $n;

					},$update_arr);

					
					
					$updateResponse = $this->google_snippets->updateValues($spreadsheet_id, $main_sheet->getProperties()->getTitle()."!A".$startIndex.":".$this->stringFromColumnIndex($endColumnIndex).$endRowIndex, "USER_ENTERED", $update_arr);
		
					$moveResponse =$this->service->spreadsheets_values->append($spreadsheet_id, $move_sheet->getProperties()->getTitle()."!A".$startIndex, $body, $params);
					
					
				}
				
				
			}



	}

	public function initShippingSheet(){

		$settings = get_option( "wc_shipping_sheets_vapelab_settings" );
		$company_suffix = $settings['company_field'];
		$company_id = $settings['company_id'];

		$spreadsheet_id = $settings['spreadsheet_id'];

		$google_client = new WC_Vapelab_Sheets_Connector_Google_Client();
		
		$google_client->client->addScope(Google\Service\Drive::DRIVE);
		
		$service = new Google_Service_Sheets($google_client->client);
		
		$response =$this->service->spreadsheets->get($spreadsheet_id);
		
		$main_sheet;

		
		foreach($response->getSheets() as $sheet){

			$sheet_properties = $sheet->getProperties();

			if($sheet_properties->getSheetId() == $settings['main_sheet_id']){
				$main_sheet = $sheet;
				break;
			}

			
		}
		
		$batch = array();

		$from = date('Y-m-d',(strtotime ( '-5 day' , strtotime ( date('Y-m-d')) ) ));
		$from = '2022-10-01';
		$to =date('Y-m-d');
		$date_created = $from.'...'.$to;
		
		$args = array(
			'limit' => -1,
			'date_created' => $date_created,
			'orderby' => 'date_created',
    		'order' => 'ASC',
		);

		$shipping = wc_get_orders( $args );
		
		$createDeveloperMetadataRequests = array();
		$index = 1;

		foreach($shipping as $order){

			$order_data = $order->get_data();
			$order_meta = $order_data['meta_data'];
			$customer = $order->get_user();
			
			if($customer){

				$customer_data = $customer->to_array();
			
				$order_fees = 0;
		
				// Get fees
				foreach ( $order->get_fees() as $fee_id => $fee ) {
					// Get total
					$order_fees += $fee['line_total'];

					// OR $order_fees += $fee->get_total();
				}

				$phone = $order->get_billing_phone();
				if($order->meta_exists('shipping_country_phone_code')){
					$country_code_phone = $order->get_meta('shipping_country_phone_code');
					$phone=$country_code_phone.$phone;
				}
				
				$batch[] = array(
					$company_suffix,
					$order_data['date_created']->date('d-M-y'),
					$order_data['date_created']->date('H:i'),
					$order_data['id'],
					$order_data['shipping']['first_name']." ".$order_data['shipping']['last_name'],
					"",
					$order->get_shipping_address_1(),
					$order->get_shipping_address_2(),
					$order->get_shipping_city(),
					$order->get_shipping_state(),
					"'".$order->get_shipping_postcode(),
					$phone,
					"",
					$order->get_shipping_method(),
					$order_data['payment_method'],
					$order_data['payment_method_title'],
					$order->get_total_shipping(),
					$order_fees,
					$order->get_total(),
					"",
					"",
					$order->get_status(),
					"",
					"",
					"",
					"",
				);

				$createDeveloperMetadataRequests[] = new Google_Service_Sheets_Request([
					'createDeveloperMetadata' => [
						'developerMetadata' => [
							'metadataId' => $company_id.$order_data['id'],
							'metadataKey' => 'id',
							'metadataValue' => (string) $order_data['id'],
							'location' => [
								"dimensionRange" => [
									"sheetId" => $main_sheet->getProperties()->getSheetId(),
									"dimension"=> "ROWS",
									"startIndex"=> $index,
									"endIndex"=> $index+1
								]
						
							],
							'visibility' => "DOCUMENT"
						]
					],
					
				]);

				$index = $index + 1;

			}
			
			
		}

		$data = [];
        $data[] = new Google_Service_Sheets_ValueRange([
            'range' => $main_sheet->getProperties()->getTitle()."!A3",
			'majorDimension' => 'ROWS',
			'values' => $batch
        ]);
		

		$body = new Google_Service_Sheets_BatchUpdateValuesRequest([
            'valueInputOption' => "USER_ENTERED",
            'data' => $data
        ]);

        $result =$this->service->spreadsheets_values->batchUpdate($spreadsheet_id, $body);

		$body = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
			'requests' => $createDeveloperMetadataRequests
	
		]);

		$creadeMeta =$this->service->spreadsheets->batchUpdate($spreadsheet_id,$body);
		

	}

}