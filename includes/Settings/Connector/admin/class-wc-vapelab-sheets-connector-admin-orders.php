<?php


class WC_Vapelab_Sheets_Connector_Admin_Orders
{

	private $id;

	private $settings;

	private $logger;

	public function __construct($google_snippets)
    {

		$this->google_snippets = $google_snippets;

		$this->service = $this->google_snippets->getService();

		$this->init_settings();
		
    }

	public function init_settings(){

		$this->settings  = get_option( "wc_orders_sheets_vapelab_settings" );
	}

	public function sync(){
		
		/*
		$url = 'https://vapelab.mx/wp-json/wc/v3/orders?after=2023-02-01+00:00:00&per_page=100&page=2';

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
		

		$this->fixMetadata();
		//exit();
		*/
		$this->fixMetadata();
		$settings = $this->settings;
		
		$spreadsheet_id = $settings['spreadsheet_id'];
		$main_sheet_id = $settings['main_sheet_id'];
		

		$orders = json_decode(file_get_contents(WC_VAPELAB_SHEETS_CONNECTOR_PATH."/orders.json"),true);
		$orders=array_reverse($orders);
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
			if (strpos($shipping_method_id, 'enviaya') !== false || $shipping_method_id == 'enviaya' ) {
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
			$date_created->format('d-M-y'),
			$date_created->format('H:i'),
			$order['id'],
			$order['shipping']['first_name']." ".$order['shipping']['last_name'],
			$order['customer_id'],
			$order['payment_method'],
			$order['payment_method_title'],
			$order_subtotal,
			$order['shipping_total'],
			$order_fees,
			0 - $order['discount_total'],
			$order['total'],
			$efectivo_real,
			$order['status'],
			$phone,
			"",
			$order['billing']['email'],
			$shipping_method_id,
			$shipping_method_title,
			"",
			order['shipping']['address_1'],
			$order['shipping']['address_2'],
			$order['shipping']['city'],
			$order['shipping']['state'],
			"'".$order['shipping']['postcode'],
			$cobro

		);
		
		$request = array(
			"data" => [
				[
					"dataFilter"=> [
						"developerMetadataLookup"=> [
							"metadataId"=> $order['id'],
						]
					],
					"majorDimension"=> "ROWS",
					"values"=> array(
						$data
					)
				]
			],
			"includeValuesInResponse"=> true,
			"valueInputOption"=> "USER_ENTERED"
			
		);
		

		$res =$this->service->spreadsheets_values->batchUpdateByDataFilter(
			$spreadsheet_id,
			new Google_Service_Sheets_BatchUpdateValuesByDataFilterRequest($request)
		);

		echo '<pre>';var_dump($res);echo '</pre>';
		
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


			echo '<pre>';var_dump($query);echo '</pre>';exit();

		$request = array(
			"dataFilter"=> $data_filters,
			"majorDimension"=> "ROWS",
		);
		$responseMeta =$this->service->spreadsheets_developerMetadata->search($spreadsheet_id, new Google_Service_Sheets_SearchDeveloperMetadataRequest([
			"dataFilters" => $data_filters
		]));
		echo '<pre>';var_dump($responseMeta);echo '</pre>';exit();
		$response =$this->service->spreadsheets_values->batchUpdateByDataFilter(
			$spreadsheet_id,
			new Google_Service_Sheets_BatchUpdateValuesByDataFilterRequest([
				"data"=> array($request),
				"includeValuesInResponse"=> true,
				"valueInputOption"=> "USER_ENTERED"
			]),
		);
		echo '<pre>';var_dump($response);echo '</pre>';exit();

		
	}


	public function insertOrderSheet($order_id)
	{

		try {

			$order = wc_get_order($order_id);

			$order_data = $order->get_data();

			$customer = $order->get_user();
			
			if ($customer) {

				$settings = $this->settings;

				
				
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
				
				$phone = $order->get_billing_phone();
				if ($order->meta_exists('shipping_country_phone_code')) {
					$country_code_phone = $order->get_meta('shipping_country_phone_code');
					$phone = $country_code_phone . $phone;
				}
				
				$shipping_method_id = "";
				if (isset($order->get_data()['shipping_lines'])) {
					if (count($order->get_data()['shipping_lines']) > 0) {
						$shipping_method_id = current($order->get_data()['shipping_lines'])->get_data()['method_id'];
					}
				}
				
				$cobro = "";
				if (file_exists(WP_PLUGIN_DIR.'/wc-vapelab-sheets-connector-rules/orders_rules.php')) {

					include WP_PLUGIN_DIR.'/wc-vapelab-sheets-connector-rules/orders_rules.php'; 
					
					if(!empty($cobro)){
	
						if (!$order->meta_exists('cobro')) {
							$order->add_meta_data('cobro',$cobro);
							$order->save();
						}
	
					}
	
				}

				
				$data = array(
					$order_data['date_created']->date('d-M-y'),
					$order_data['date_created']->date('H:i'),
					$order_data['id'],
					$order_data['shipping']['first_name'] . " " . $order_data['shipping']['last_name'],
					$customer_data['ID'],
					$order_data['payment_method'],
					$order_data['payment_method_title'],
					$order->get_subtotal(),
					$order->get_total_shipping(),
					$decomiso_fees,
					$order_fees,
					0 - $order->get_discount_total(),
					$order->get_total(),
					"",
					$order->get_status(),
					$phone,
					"",
					$order->get_billing_email(),
					$shipping_method_id,
					$order->get_shipping_method(),
					"",
					$order->get_shipping_address_1(),
					$order->get_shipping_address_2(),
					$order->get_shipping_city(),
					$order->get_shipping_state(),
					(string)$order->get_shipping_postcode(),
					$cobro,
				);
				
				if(!empty($settings['move_target_col'])){
					while (count($data) < $this->columnIndexFromString($settings['move_target_col'])) {
						$data[] = "";
					}
				}

				$body = new Google_Service_Sheets_ValueRange([
					"majorDimension" => "ROWS",
					'values' => array($data),
				]);

				$params = [
					'valueInputOption' => 'USER_ENTERED',
					'insertDataOption' => 'INSERT_ROWS'
				];


				$spreadsheet_id = $settings['spreadsheet_id'];
				
				$sheet_title = $settings['main_sheet_title'];

				$result =$this->service->spreadsheets_values->append($spreadsheet_id, $sheet_title . "!A3", $body, $params);
				
				if(isset($result->updates) && !is_null($result->updates->updatedRange)) {

					$order->add_meta_data('a1_orders_range', $result->updates->updatedRange);
					$order->save();

				}
				/*
				$responseMeta =$this->service->spreadsheets_developerMetadata->search($spreadsheet_id, new Google_Service_Sheets_SearchDeveloperMetadataRequest([
					"dataFilters" => [
						[
							"developerMetadataLookup" => [
								'metadataId' => $order_id
							]
						]
					]
				]));

				if (!is_null($main_sheet) && empty($responseMeta->getMatchedDeveloperMetadata())) {

					$table_range = $result->updates->updatedRange;
					$index = explode(":" . $settings['move_target_col'], $table_range);
					$index = (int)  $index[1];

					$createDeveloperMetadataRequests[] = new Google_Service_Sheets_Request([
						'createDeveloperMetadata' => [
							'developerMetadata' => [
								'metadataId' => $order_id,
								'metadataKey' => 'id',
								'metadataValue' => (string) $order_data['id'],
								'location' => [
									"dimensionRange" => [
										"sheetId" => $settings['main_sheet_id'],
										"dimension" => "ROWS",
										"startIndex" => $index - 1,
										"endIndex" => $index
									]

								],
								'visibility' => "DOCUMENT"
							]
						],

					]);



					$body = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
						'requests' => $createDeveloperMetadataRequests

					]);

					$this->service->spreadsheets->batchUpdate($spreadsheet_id, $body);
				}
				*/


			}

		} catch (Google\Service\Exception $e) {
			
			// Si falla manda un mail    
			wp_mail(get_bloginfo('admin_email'), "Error en la validación de Token", "Ha ocurrido un error durante la validación de Token ");

			foreach ($e->getErrors() as $error) {
				$this->logger->debug(__FILE__, __LINE__, 'updateSpreadsheet: ' . print_r($error, true));
			}
		}
	}

	public function updateOrderSheet($order_id, $create_metadata = false)
	{

		$order = wc_get_order($order_id);

		$order_data = $order->get_data();

		$customer = $order->get_user();

		if ($customer) {

			$settings = $this->settings;

			if($create_metadata){

				if ($order->meta_exists('a1_orders_range')) {

					$sheet_updated_range = $order->get_meta('a1_orders_range');

					preg_match( '/!A\d+:[A-Z]{2}(\d+)/', $sheet_updated_range, $matches);
				
					$start_index = (int) $matches[1] - 1;
					$end_index = (int) $matches[1];

					$createDeveloperMetadataRequests[] = new Google_Service_Sheets_Request([
						'createDeveloperMetadata' => [
							'developerMetadata' => [
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
							"metadataLocation" => [
								'sheetId' => $settings['main_sheet_id']
							],
							"metadataKey" => "id",
							"metadataValue" => (string) $order_id
						]
					]
				]
			]));

			
			
			if (!is_null($searchResponse->matchedDeveloperMetadata)) {

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

				$phone = $order->get_billing_phone();
				if ($order->meta_exists('shipping_country_phone_code')) {
					$country_code_phone = $order->get_meta('shipping_country_phone_code');
					$phone = $country_code_phone . $phone;
				}

				$shipping_method_id = "";
				if (isset($order->get_data()['shipping_lines'])) {
					if (count($order->get_data()['shipping_lines']) > 0) {
						$shipping_method_id = current($order->get_data()['shipping_lines'])->get_data()['method_id'];
					}
				}

				$cobro = "";
				$efereal = "";


				if ($order->meta_exists('efectivo_real')) {
					$efereal = $order->get_meta('efectivo_real');
				}

				if ($order->meta_exists('cobro')) {
					$cobro = $order->get_meta('cobro');
				}



				$data = array(
					$order_data['date_created']->date('d-M-y'),
					$order_data['date_created']->date('H:i'),
					$order_data['id'],
					$order_data['billing']['first_name'] . " " . $order_data['billing']['last_name'],
					$customer_data['ID'],
					$order_data['payment_method'],
					$order_data['payment_method_title'],
					$order->get_subtotal(),
					$order->get_total_shipping(),
					$decomiso_fees,
					$order_fees,
					0 - $order->get_discount_total(),
					$order->get_total(),
					$efereal,
					$order->get_status(),
					$phone,
					"",
					$order->get_billing_email(),
					$shipping_method_id,
					$order->get_shipping_method(),
					"",
					$order->get_shipping_address_1(),
					$order->get_shipping_address_2(),
					$order->get_shipping_city(),
					$order->get_shipping_state(),
					"'" . $order->get_shipping_postcode(),
					$cobro
				);

				$spreadsheet_id = $settings['spreadsheet_id'];

				$request = array(
					"dataFilter" => [
						"developerMetadataLookup" => [
						"metadataLocation" => [
							'sheetId' => $settings['main_sheet_id']
						],
						"metadataKey" => "id",
        				"metadataValue" => (string) $order_id
						]
					],
					"majorDimension" => "ROWS",
					"values" => [
						$data
					]
				);

				$res =$this->service->spreadsheets_values->batchUpdateByDataFilter(
					$spreadsheet_id,
					new Google_Service_Sheets_BatchUpdateValuesByDataFilterRequest([
						"data" => array($request),
						"includeValuesInResponse" => true,
						"valueInputOption" => "USER_ENTERED"
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

	public function createOrdersSheet()
	{

		try {

			$google_client = new WC_Vapelab_Sheets_Connector_Google_Client();

			$google_client->client->addScope(Google\Service\Drive::DRIVE);

			$service = new Google_Service_Sheets($google_client->client);

			$spreadsheet = new Google_Service_Sheets_Spreadsheet([
				'properties' => [
					'title' => 'VapeLab-Woo Orders Sync',
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
							'title' => 'Pedidos'
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
		} catch (Google\Service\Exception $e) {

			foreach ($e->getErrors() as $error) {

				$this->logger->debug(__FILE__, __LINE__, 'updateSpreadsheet: ' . print_r($error, true));
			}
		}
	}

	public function fixMetadata(){

		$settings = get_option( "wc_orders_sheets_vapelab_settings" );

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
			
			foreach($sheetValues as $roWValue){
				
				if(empty($roWValue[2])){
					break;
				}

				$createDeveloperMetadataRequests[] = new Google_Service_Sheets_Request([
					'createDeveloperMetadata' => [
						'developerMetadata' => [
							'metadataKey' => 'id',
							'metadataValue' => (string) $roWValue[2],
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

			if(!empty($createDeveloperMetadataRequests)){

				$responseMeta =$this->service->spreadsheets->batchUpdate($spreadsheet_id, new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
					'requests' => $createDeveloperMetadataRequests
				]));
				
			}
			
			
		
		}
		
		
	}

	public function moveOrdersToHistory(){

		$settings = get_option("wc_orders_sheets_vapelab_settings");

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

				if (empty($roWValue[2])) {
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

				$update_arr = array_map(function($n) {
					
					$p_idx = $this->columnIndexFromString('P') -1;
					$t_idx = $this->columnIndexFromString('T') -1;
					$ac_idx = $this->columnIndexFromString('AC') -1;
					$ad_idx = $this->columnIndexFromString('AD') -1;
					$ae_idx = $this->columnIndexFromString('AE') -1;
					$af_idx = $this->columnIndexFromString('AF') -1;
					$ag_idx = $this->columnIndexFromString('AG') -1;
					$aj_idx = $this->columnIndexFromString('AJ') -1;
	
					$n[$p_idx] = "";	
					$n[$t_idx] = "";
					$n[$ac_idx] = "";
					$n[$ad_idx] = "";
					$n[$ae_idx] = "";
					$n[$af_idx] = "";
					$n[$ag_idx] = "";
					$n[$aj_idx] = "";
				
					
					return $n;

				},$update_arr);
				


				$updateResponse = $this->google_snippets->updateValues($spreadsheet_id, $main_sheet->getProperties()->getTitle()."!A".$startIndex.":".$this->stringFromColumnIndex($endColumnIndex).$endRowIndex, "USER_ENTERED", $update_arr);
	
				$moveResponse =$this->service->spreadsheets_values->append($spreadsheet_id, $move_sheet->getProperties()->getTitle()."!A".$startIndex, $body, $params);
				
			}
			
			
		}



	}


}
