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
class WC_Vapelab_Sheets_Connector_Admin_Details
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

		$this->settings  = get_option( "wc_details_sheets_vapelab_settings" );
	}

	public function reload(){

		$settings = $this->settings;

		$response = $this->service->spreadsheets_developerMetadata->search($settings['spreadsheet_id'], new Google_Service_Sheets_SearchDeveloperMetadataRequest([
			"dataFilters" => [
				[
					"developerMetadataLookup" => [
						"metadataLocation" => [
							'sheetId' => $settings['main_sheet_id']
						],
					]
				]
			]
		]));
		
		$order_ids = array();
		if(!is_null($response->matchedDeveloperMetadata)) {
			foreach($response->matchedDeveloperMetadata as $developerMetadata){
				
				$order_ids[] = $developerMetadata->developerMetadata->metadataValue;
			}

			if(count($order_ids)){

				sort($order_ids);
			
				$deleteRange = $settings['main_sheet_title'].'!A2:ZZ';
	
				// Define the request to delete rows
				$requestBody = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
					'requests' => [
						'deleteDimension' => [
							'range' => [
								'sheetId' => $settings['main_sheet_id'],  // Specify the sheet ID, 0 for the first sheet
								'dimension' => 'ROWS',
								'startIndex' => 1,  // Specify the starting row index to delete (excluding the first row)
							],
						],
					],
				]);
	
				// Execute the request
				$response = $this->service->spreadsheets->batchUpdate($settings['spreadsheet_id'], $requestBody);
	
	
	
				foreach ($order_ids as $order_id) {
	
					$this->insertIntoSheet($order_id);
	
				}
	
			}

		}
		
	}

	public function deleteDeveloperMetadata(){

		$settings = $this->settings;

		$requests[] = [
			"deleteDeveloperMetadata" => [
				"dataFilter" => [
					"developerMetadataLookup"=> [
						"metadataLocation" => [
							"sheetId" => $settings['main_sheet_id']
						]
						
					]
				]
			]
		];

		$response =$this->service->spreadsheets->batchUpdate($settings['spreadsheet_id'], new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
			'requests' => $requests
		]));

		return $response;

	}

	public function updateDetailsSheet($order_id){

		$settings = $this->settings;
		
		$order = wc_get_order($order_id);

		$response = $this->service->spreadsheets_developerMetadata->search($settings['spreadsheet_id'], new Google_Service_Sheets_SearchDeveloperMetadataRequest([
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
		
		if(!is_null($response->matchedDeveloperMetadata)) {
			
			if($order->meta_exists('a1_details_range')){

				$a1not = $order->get_meta('a1_details_range');
				
				$status = $order->get_status();

				$pattern = '/([A-Z]+)(\d+):([A-Z]+)(\d+)/';
				
				preg_match($pattern, $a1not, $matches);
				

				$total_shipping = $order->get_total_shipping();

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

				$order_fees = number_format($order_fees,2,'.','');
				$decomiso_fees = number_format($decomiso_fees,2,'.','');

				$status = $order->get_status();
				$total = $order->get_total();



				$data = array();
				for($i=(int)$matches[2];$i<=$matches[4];$i++){
					
					$tmp = array();

					if($i == (int)$matches[2]){

						$tmp[] =$total_shipping;
						$tmp[] =$decomiso_fees;
						$tmp[] =$order_fees;
						$tmp[] =$total;
						$tmp[] =$status;

					}else{

						$tmp[] = 0;
						$tmp[] = 0;
						$tmp[] = 0;
						$tmp[] = 0;
						$tmp[] =$status;

					}
					

					$data[] = $tmp;
				}

				
				

				$request = array(
					"includeValuesInResponse" => false,
					"valueInputOption" => "USER_ENTERED",
					"data" => array(
						array(
							"dataFilter" => [
								"gridRange" => array(
									'sheetId' => $settings['main_sheet_id'],
									'startRowIndex' => $response->matchedDeveloperMetadata[0]->developerMetadata->location->dimensionRange->startIndex,
									'endRowIndex' =>  $response->matchedDeveloperMetadata[0]->developerMetadata->location->dimensionRange->startIndex + count($order->get_items()),
									'startColumnIndex' => 7,
									'endColumnIndex' => 12,
								),
							],
							"majorDimension" => "ROWS",
							"values" => $data
						)
					),
					
				);
				

				$response =$this->service->spreadsheets_values->batchUpdateByDataFilter(
					$settings['spreadsheet_id'],
					new Google_Service_Sheets_BatchUpdateValuesByDataFilterRequest($request),
				);
				
				return $response;
			}

			
		}
	

		
	}

	public function insertIntoSheet($order_id, $meta_data = true ){
		//echo '<pre>';var_dump($order_id);echo '</pre>';
		$order = wc_get_order($order_id);
		
		$date_created = $order->get_date_created()->format('d-M-y');

		$customer_id = $order->get_customer_id();
		$customer = $customer_id ? get_user_by('ID', $customer_id) : false;

		if($customer){
			$first_name = $customer->get('first_name');
			$last_name = $customer->get('last_name');

			$customer_fullname = $first_name . ' ' . $last_name;
		}else{
			$customer_fullname = $order->get_billing_first_name()." ".$order->get_billing_last_name();
		}
		

		$total_shipping = $order->get_total_shipping();

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

		$order_fees = number_format($order_fees,2,'.','');
		$decomiso_fees = number_format($decomiso_fees,2,'.','');

		$status = $order->get_status();
		$total = $order->get_total();
		

		$values = array();
		$count = 0;

		if(!count($order->get_items())){
			return false;
		}
		
		foreach ( $order->get_items() as $item_id => $item ) {
			
			
			$tmp = array(
				$date_created,
				$order_id,
				$customer_fullname,
			);
	
			$item_name = $item->get_name();
		
			$product = wc_get_product( $item->get_product_id() );

			if($this->settings['variation_column'] == "yes"){

				$product_name = $product->get_name();
				
				$variation_name = explode($product_name,$item_name)[1];
				$variation_name = trim(explode("-",$variation_name)[1]);

				$tmp[] = $product_name;
				$tmp[] = $variation_name;

				
			}else{

				$tmp[] = $item_name;

				if(!empty($this->settings['delimiter'])){
				
					$delimiter = $this->settings['delimiter'];
					$product_name = $product->get_name();
					
					$product_name_arr = explode($delimiter,$product_name);

					$variation_name = "";
					$variation_name_arr = explode($product_name,$item_name);
					if(isset($variation_name_arr[1])){
						$variation_name = $variation_name_arr[1];
						$variation_name = trim(explode("-",$variation_name)[1]);
					}
					
	
					$product_name_short = trim($product_name_arr[0]);
					
					$tmp[3] = $product_name_short;

					if(!empty($variation_name)){
						$tmp[3] = $product_name_short." - ".$variation_name;
					}
					
					
				}
			}
						
			$quantity = $item->get_quantity();
			$subtotal = $item->get_total();
			$price = $subtotal / $quantity;

			if($count > 0){
				$total_shipping = 0;
				$order_fees = 0;
				$decomiso_fees = 0;
				$total = 0;
			}

			$tmp[] =$quantity;
			$tmp[] =$price;
			$tmp[] =$subtotal;
			$tmp[] =$total_shipping;
			$tmp[] =$decomiso_fees;
			$tmp[] =$order_fees;
			$tmp[] =$total;
			$tmp[] =$status;


			$values[] = $tmp;

			
			$count++;
			
		}
		
		$settings = $this->settings;
		$spreadsheet_id = $settings['spreadsheet_id'];
		$main_sheet_id = $settings['main_sheet_id'];
		$main_sheet_title = $settings['main_sheet_title'];

		$a1range = $main_sheet_title . "!A2";


		$body = new Google_Service_Sheets_ValueRange([
			"majorDimension" => "ROWS",
			'values' => $values,
		]);

		$params = [
			'valueInputOption' => 'USER_ENTERED',
			'insertDataOption' => 'INSERT_ROWS'
		];
				
		$result =$this->service->spreadsheets_values->append($spreadsheet_id, $a1range, $body, $params);
		
		if(isset($result->updates) && !is_null($result->updates->updatedRange)){

			//echo '<pre>';var_dump($result->updates->updatedRange);echo '</pre>';

			$order->update_meta_data('a1_details_range',$result->updates->updatedRange);
			$order->save();

			$pattern = '/([A-Z]+)(\d+):([A-Z]+)(\d+)/';

			preg_match($pattern, $result->updates->updatedRange, $matches);
			
			$startColumn = $matches[1];
			$startRow = intval($matches[2]);
			$endColumn = $matches[3];
			$endRow = intval($matches[4]);
			
			/*
			echo '<pre>';var_dump([
				"sheetId" => $main_sheet_id,
				"dimension"=> "ROWS",
				"startIndex"=> $startRow - 1,
				"endIndex"=> $endRow
			]);echo '</pre>';
			*/

			$createDeveloperMetadataRequests[] = new Google_Service_Sheets_Request([
				'createDeveloperMetadata' => [
					'developerMetadata' => [
						//'metadataId' => $order_id,
						'metadataKey' => 'id',
						'metadataValue' =>  (string) $order_id,
						'location' => [
							'dimensionRange' => [
								"sheetId" => $main_sheet_id,
								"dimension"=> "ROWS",
								"startIndex"=> $startRow - 1,
								"endIndex"=> $startRow
							],
						],
						'visibility' => "DOCUMENT"
					]
				],
	
			]);
			
			$body = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
				'requests' => $createDeveloperMetadataRequests
	
			]);
	
			$this->service->spreadsheets->batchUpdate($settings['spreadsheet_id'], $body);
			

		}
		/*
		if(isset($result->updates) && !is_null($result->updates->updatedRange)){

			$order->update_meta_data('sheet_order_details_range',$result->updates->updatedRange);
			$order->save();

			if($meta_data){

				$pattern = '/([A-Z]+)(\d+):([A-Z]+)(\d+)/';

				preg_match($pattern, $result->updates->updatedRange, $matches);
				$startColumn = $matches[1];
				$startRow = intval($matches[2]);
				$endColumn = $matches[3];
				$endRow = intval($matches[4]);
				
				$start_index = (int) $matches[1] - 1;
				$end_index = (int) $matches[1];
				
				$createDeveloperMetadataRequests[] = new Google_Service_Sheets_Request([
					'createDeveloperMetadata' => [
						'developerMetadata' => [
							'metadataId' => $order_id,
							'metadataKey' => 'id',
							'metadataValue' => (string) $order_id,
							'location' => [
								"dimensionRange" => [
									"sheetId" => $main_sheet_id,
									"dimension"=> "ROWS",
									"startIndex"=> $startRow - 1,
									"endIndex"=> $startRow
								],
							],
							'visibility' => "DOCUMENT"
						]
					],
		
				]);
		
				$body = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
					'requests' => $createDeveloperMetadataRequests
		
				]);
		
				$this->service->spreadsheets->batchUpdate($settings['spreadsheet_id'], $body);

			}
			

		}

		*/



	}

	public function fixMetadata(){

		$settings = $this->settings;

		$spreadsheetId = $settings['spreadsheet_id'];
		$range = $settings['main_sheet_title'].'!A3:ZZ';

		// Get existing values
		$response = $this->service->spreadsheets_values->get($spreadsheetId, $range);
		$values = $response->getValues();

		$order_ids = array();

		foreach($values as $value){
			$order_ids[] = (int) $value[1];
			
		}

		

		$order_ids = array_unique($order_ids);

		sort($order_ids);

		if(count($order_ids)){
			
			$this->deleteDeveloperMetadata();

			$deleteRange = $settings['main_sheet_title'].'!A3:ZZ';
	
			// Define the request to delete rows
			$requestBody = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
				'requests' => [
					'deleteDimension' => [
						'range' => [
							'sheetId' => $settings['main_sheet_id'],  // Specify the sheet ID, 0 for the first sheet
							'dimension' => 'ROWS',
							'startIndex' => 1,  // Specify the starting row index to delete (excluding the first row)
						],
					],
				],
			]);

			// Execute the request
			$response = $this->service->spreadsheets->batchUpdate($settings['spreadsheet_id'], $requestBody);



			foreach ($order_ids as $order_id) {

				$this->insertIntoSheet($order_id);

			}


		}

	}
	

}