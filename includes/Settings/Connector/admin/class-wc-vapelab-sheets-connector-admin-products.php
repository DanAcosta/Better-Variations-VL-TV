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

class WC_Vapelab_Sheets_Connector_Admin_Products
{

	private $id;

	private $settings;

	private $products_cat = array(
		'desechables' => 507,
		'equipos-para-vapear' => 45,
		'pods-resistencias' => 1866,
		'accesorios' => 29,
		'e-liquids' => 7,
		'sal-de-nicotina' => 102,
		'alternativo' => 843,
	);
    
	private $service;

	private $google_snippets;

    public function __construct($google_snippets)
    {

		$this->google_snippets = $google_snippets;

		$this->service = $this->google_snippets->getService();

		$this->init_settings();
		
    }

	public function init_settings(){

		$this->settings  = get_option( "wc_products_sheets_vapelab_settings" );
	}

	public function bulkEditVariations($bulk_action, $data, $product_id, $variations){

	
		foreach($variations as $variation_id){

			$variation  = wc_get_product( $variation_id );

			$variation_data = $variation->get_data();
			$variation_id = $variation_data['id'];
			
			$product_data = $variation->get_parent_data();
			
			$variation_name = explode($product_data['title'],$variation_data['name'])[1];
			$variation_name = trim(explode("-",$variation_name)[1]);

			$cost = $variation->get_meta('_wc_cog_cost');
			
			$row_arr = [
				$product_id,
				$variation_data['id'],
				$product_data['title'],
				$variation_name,
				$variation_data['sku'],
				$cost,
				!empty($variation_data['regular_price']) ? $variation_data['regular_price'] : "",
				!empty($variation_data['sale_price']) ? $variation_data['sale_price'] : "",
				!empty($variation_data['stock_quantity']) ? $variation_data['stock_quantity'] : 0,
				!empty($variation_data['weight']) ? $variation_data['weight'] : "",
				!empty($variation_data['length']) ? $variation_data['length'] : "",
				!empty($variation_data['width']) ? $variation_data['width'] : "",
				!empty($variation_data['height']) ? $variation_data['height'] : "",	
				"",
			];

			$response = $this->updateProductSheet($variation_data['id'],$row_arr);
			
		}

	}

	public function saveProductVariation( $producto_id ) {

		$settings = $this->settings;

		$spreadsheet_id = $settings['spreadsheet_id'];
		
		$product  = wc_get_product( $producto_id );
		
		$product_class = get_class($product);
		
		if($product_class == 'WC_Product_Variation'){

			$variation = $product;

			$variation_data = $variation->get_data();

			$product_data = $variation->get_parent_data();
			
			$product_name = $product_data['title'];

			$variation_name = explode($product_name,$variation_data['name'])[1];
			$variation_name = trim(explode("-",$variation_name)[1]);

			$cost = $variation->get_meta('_wc_cog_cost');
			
			$row_arr = array(
				$variation_data['parent_id'],
				$variation_data['id'],
				!empty($product_name) ? $product_name : "",
				!empty($variation_name) ? $variation_name : "N/A",
				!empty($variation_data['sku']) ? $variation_data['sku'] : "",
				!empty($cost) ? $cost : "",
				!empty($variation_data['regular_price']) ? $variation_data['regular_price'] : "",
				!empty($variation_data['sale_price']) ? $variation_data['sale_price'] : "",
				!empty($variation_data['stock_quantity']) ? $variation_data['stock_quantity'] : 0,
				!empty($variation_data['weight']) ? $variation_data['weight'] : "",
				!empty($variation_data['length']) ? $variation_data['length'] : "",
				!empty($variation_data['width']) ? $variation_data['width'] : "",
				!empty($variation_data['height']) ? $variation_data['height'] : "",
				""
			);
			
			$response = $this->updateProductSheet($variation_data['id'],$row_arr);
			

		}elseif($product_class == "WC_Product_Variable"){
			$this->updateProductVariable($product);

		}elseif($product_class == "WC_Product_Simple"){
			$this->updateProductSimple($product);
		}
		
	}

	public function updateSpreadsheet($settings = array()){


		try {

			$settings = $this->settings;

			$spreadsheet_id = $settings['spreadsheet_id'];
			
			$group_by_categories = $settings['products_spreadsheet_group_by_categories'];
			
			
			if($group_by_categories == 'yes'){

				$this->update_sheet();

			}else{

				$sheet_id = $settings['sheet_id'];
				$sheet_title = $settings['sheet_title'];
				
				$responseClear = $this->service->spreadsheets_values->batchClearByDataFilter($spreadsheet_id, new Google_Service_Sheets_BatchClearValuesByDataFilterRequest([
					"dataFilters" => [
						[
							"gridRange" => [
								"sheetId" => $sheet_id,
								"startRowIndex" => 1,
								"startColumnIndex" => 0

							]
						]
					]
				]));

				$responseMeta = $this->service->spreadsheets_developerMetadata->search($spreadsheet_id, new Google_Service_Sheets_SearchDeveloperMetadataRequest([
					"dataFilters" => [
						[
							"developerMetadataLookup"=> [
								"metadataLocation" => [
									"sheetId" => $sheet_id,
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
					$responseMetadataDel = $this->service->spreadsheets->batchUpdate($spreadsheet_id, new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
						'requests' => $deleteDeveloperMetadataRequests
					]));
				}

				$args = array(
					'status' => 'publish',
					'type' => array('variable','simple'),
					'limit' => (WC_VAPELAB_SHEETS_CONNECTOR_ENV == "DEV") ? 100 : -1,
					'orderby' => 'id',
					'order' => 'ASC',
				);

				$products = wc_get_products($args);

				$startIndex = 2;
				foreach ($products as $key => $product) {
					$product_data = $product->get_data();

					$product = wc_get_product($product_data['id']);
					$product_name = $product_data['name'];

					if (get_class($product) == 'WC_Product_Variable') {
						$variations = $product->get_children();

						if (!empty($variations)) {
							foreach ($variations as $variation_id) {
								if (!in_array($variation_id, $products_ids)) {
									$variation = wc_get_product($variation_id);
									$variation_data = $variation->get_data();

									$variation_name = explode($product_name, $variation_data['name'])[1];
									$variation_name = trim(explode("-", $variation_name)[1]);

									$cost = $variation->get_meta('_wc_cog_cost');

									$batch[] = array(
										$product_data['id'],
										$variation_data['id'],
										!empty($product_name) ? $product_name : "",
										!empty($variation_name) ? $variation_name : "N/A",
										!empty($variation_data['sku']) ? $variation_data['sku'] : "",
										!empty($cost) ? $cost : "",
										!empty($variation_data['regular_price']) ? $variation_data['regular_price'] : "",
										!empty($variation_data['sale_price']) ? $variation_data['sale_price'] : "",
										!empty($variation_data['stock_quantity']) ? $variation_data['stock_quantity'] : 0,
										!empty($variation_data['weight']) ? $variation_data['weight'] : "",
										!empty($variation_data['length']) ? $variation_data['length'] : "",
										!empty($variation_data['width']) ? $variation_data['width'] : "",
										!empty($variation_data['height']) ? $variation_data['height'] : "",
										""
									);




									$createDeveloperMetadataRequests[] = new Google_Service_Sheets_Request([
										'createDeveloperMetadata' => [
											'developerMetadata' => [
												'metadataId' => $variation_data['id'],
												'metadataKey' => 'id',
												'metadataValue' => (string) $variation_data['id'],
												'location' => [
													"dimensionRange" => [
														"sheetId" => $sheet_id,
														"dimension"=> "ROWS",
														"startIndex"=> $startIndex,
														"endIndex"=> $startIndex+1
													]

												],
												"visibility" => "DOCUMENT"
											]
										],

									]);

									$products_ids[] = $variation_data['id'];
									$startIndex++;
								}
							}
						}
					} else {
						if (!in_array($product_data['id'], $products_ids)) {
							$cost = $product->get_meta('_wc_cog_cost');

							$batch[] = array(
								$product_data['id'],
								'',
								!empty($product_data['name']) ? $product_data['name'] : "",
								"N/A",
								!empty($product_data['sku']) ? $product_data['sku'] : "",
								!empty($cost) ? $cost : "",
								!empty($product_data['regular_price']) ? $product_data['regular_price'] : "",
								!empty($product_data['sale_price']) ? $product_data['sale_price'] : "",
								!empty($product_data['stock_quantity']) ? $product_data['stock_quantity'] : 0,
								!empty($product_data['weight']) ? $product_data['weight'] : "",
								!empty($product_data['length']) ? $product_data['length'] : "",
								!empty($product_data['width']) ? $product_data['width'] : "",
								!empty($product_data['height']) ? $product_data['height'] : "",
								""
							);

							$createDeveloperMetadataRequests[] = new Google_Service_Sheets_Request([
								'createDeveloperMetadata' => [
									'developerMetadata' => [
										'metadataId' => $product_data['id'],
										'metadataKey' => 'id',
										'metadataValue' => (string) $product_data['id'],
										'location' => [
											"dimensionRange" => [
												"sheetId" => $sheet_id,
												"dimension"=> "ROWS",
												"startIndex"=> $startIndex,
												"endIndex"=> $startIndex+1
											]

										],
										"visibility" => "DOCUMENT"
									]
								],

							]);

							$startIndex++;
						}
					}
				}


				$responseAppend = $this->service->spreadsheets_values->append(
					$spreadsheet_id,
					$sheet_title."!A3:A",
					new Google_Service_Sheets_ValueRange([
						'values' => $batch
					]),
					array(
						'valueInputOption' => 'USER_ENTERED',
						'insertDataOption' => 'INSERT_ROWS'
					)
				);

				$responseMeta = $this->service->spreadsheets->batchUpdate($spreadsheet_id, new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
					'requests' => $createDeveloperMetadataRequests
				]));



				if (!is_null($sheet_id)) {

					$spreadsheets = $this->google_snippets->getSpreadsheet($spreadsheet_id);

					foreach ($spreadsheets->sheets as $sheet) {
						if ($sheet_id == $sheet->properties->sheetId) {
							$sheet_title = $sheet->properties->title;
						}
					}
				}
			}


		}catch(Google\Service\Exception $e){
			echo '<pre>';var_dump($e->getMessage());echo '</pre>';exit();
		}

		

	}


	public function createProductsSheet($settings = array()){

		
		try{

			$settings = $this->settings;

			$spreadsheet_id = $this->google_snippets->create('VapeLab-Woo Stock Sync');

			if ($settings['products_spreadsheet_group_by_categories'] == 'yes') {
				$requests = array(


					new Google_Service_Sheets_Request([
						'addSheet' => [
							'properties' => [
								'sheetId' => $this->products_cat['desechables'],
								'title' => 'desechables'
							]
						]
					]),
					new Google_Service_Sheets_Request([
						'addSheet' => [
							'properties' => [
								'sheetId' => $this->products_cat['equipos-para-vapear'],
								'title' => 'equipos-para-vapear'
							]
						]
					]),
					new Google_Service_Sheets_Request([
						'addSheet' => [
							'properties' => [
								'sheetId' => $this->products_cat['pods-resistencias'],
								'title' => 'pods-resistencias'
							]
						]
					]),
					new Google_Service_Sheets_Request([
						'addSheet' => [
							'properties' => [
								'sheetId' => $this->products_cat['accesorios'],
								'title' => 'accesorios'
							]
						]
					]),
					new Google_Service_Sheets_Request([
						'addSheet' => [
							'properties' => [
								'sheetId' => $this->products_cat['e-liquids'],
								'title' => 'e-liquids'
							]
						]
					]),
					new Google_Service_Sheets_Request([
						'addSheet' => [
							'properties' => [
								'sheetId' => $this->products_cat['sal-de-nicotina'],
								'title' => 'sal-de-nicotina'
							]
						]
					]),
					new Google_Service_Sheets_Request([
						'addSheet' => [
							'properties' => [
								'sheetId' => $this->products_cat['alternativo'],
								'title' => 'alternativo'
							]
						]
					]),
					new Google_Service_Sheets_Request([
						'deleteSheet' => [
							'sheetId' => 0
						]
					]),
				);
			}else{

				$requests = array(


					new Google_Service_Sheets_Request([
						'addSheet' => [
							'properties' => [
								'title' => 'Inventario'
							]
						]
					]),
				
					new Google_Service_Sheets_Request([
						'deleteSheet' => [
							'sheetId' => 0
						]
					]),
				);


			}

	
			$batchUpdateResponse = $this->google_snippets->batchUpdate($spreadsheet_id, $requests);
			

			$settings['spreadsheet_id'] = $spreadsheet_id;

			update_option( 'wc_products_sheets_vapelab_settings', $settings );

			return $settings;
			
		}catch(Google\Service\Exception $e){
			
		}
		

		
	}

	public function updateProductVariable( $product ){

			$product_data = $product->get_data();
			$variations = $product->get_children();

			foreach($variations as $variation_id){

				$variation = wc_get_product( $variation_id );
				
				$product_name = $product_data['name'];

				$variation_data = $variation->get_data();
				
				$variation_name = explode($product_name,$variation_data['name'])[1];
				$variation_name = trim(explode("-",$variation_name)[1]);
				
				$cost = $variation->get_meta('_wc_cog_cost');
				
				$id = $data['id'];

				$row_arr = array(
					$product_data['id'],
					$variation_data['id'],
					!empty($product_name) ? $product_name : "",
					!empty($variation_name) ? $variation_name : "N/A",
					!empty($variation_data['sku']) ? $variation_data['sku'] : "",
					!empty($cost) ? $cost : "",
					!empty($variation_data['regular_price']) ? $variation_data['regular_price'] : "",
					!empty($variation_data['sale_price']) ? $variation_data['sale_price'] : "",
					!empty($variation_data['stock_quantity']) ? $variation_data['stock_quantity'] : 0,
					!empty($variation_data['weight']) ? $variation_data['weight'] : "",
					!empty($variation_data['length']) ? $variation_data['length'] : "",
					!empty($variation_data['width']) ? $variation_data['width'] : "",
					!empty($variation_data['height']) ? $variation_data['height'] : "",
					""
				);
				
				$this->updateProductSheet($variation_data['id'],$row_arr);

			}


	}


	public function updateProductVariation( $variation ){
		

		$variation_data = $variation->get_data();
		
		
		$product = wc_get_product( $variation_data['parent_id'] );
	
		$product_data = $product->get_data();

		$product_name = $product_data['name'];

		$variation_name = explode($product_name,$variation_data['name'])[1];
		$variation_name = trim(explode("-",$variation_name)[1]);
		
		$cost = $variation->get_meta('_wc_cog_cost');
		
		$id = $data['id'];

		$row_arr = array(
			$product_data['id'],
			$variation_data['id'],
			!empty($product_name) ? $product_name : "",
			!empty($variation_name) ? $variation_name : "N/A",
			!empty($variation_data['sku']) ? $variation_data['sku'] : "",
			!empty($cost) ? $cost : "",
			!empty($variation_data['regular_price']) ? $variation_data['regular_price'] : "",
			!empty($variation_data['sale_price']) ? $variation_data['sale_price'] : "",
			!empty($variation_data['stock_quantity']) ? $variation_data['stock_quantity'] : 0,
			!empty($variation_data['weight']) ? $variation_data['weight'] : "",
			!empty($variation_data['length']) ? $variation_data['length'] : "",
			!empty($variation_data['width']) ? $variation_data['width'] : "",
			!empty($variation_data['height']) ? $variation_data['height'] : "",
			""
		);

		
		$this->updateProductSheet($variation_data['id'],$row_arr,$this->settings);

	}

	public function updateProductSimple( $product ){

		$product_data = $product->get_data();

		$cost = $product->get_meta('_wc_cog_cost');

		$row_arr = array(
			$product_data['id'],
			"",
			!empty($product_data['name']) ? $product_data['name'] : "",
			"N/A",
			!empty($product_data['sku']) ? $product_data['sku'] : "",
			!empty($cost) ? $cost : "",
			!empty($product_data['regular_price']) ? $product_data['regular_price'] : "",
			!empty($product_data['sale_price']) ? $product_data['sale_price'] : "",
			!empty($product_data['stock_quantity']) ? $product_data['stock_quantity'] : 0,
			!empty($product_data['weight']) ? $product_data['weight'] : "",
			!empty($product_data['length']) ? $product_data['length'] : "",
			!empty($product_data['width']) ? $product_data['width'] : "",
			!empty($product_data['height']) ? $product_data['height'] : "",
			""
		);
		
		$this->updateProductSheet($product_data['id'],$row_arr);
		

	}

	public function updateProductSheet($id, $data ){
		
		try{

			$settings = $this->settings;

			$settings = get_option( "wc_products_sheets_vapelab_settings" );
			$spreadsheet_id = $settings['spreadsheet_id'];

			$request = array(
				"dataFilter"=> [
					"developerMetadataLookup"=> [
						"metadataId"=> $id
					]
				],
				"majorDimension"=> "ROWS",
				"values"=> [
					$data
				]
			);
			
			$res = $this->service->spreadsheets_values->batchUpdateByDataFilter(
				$spreadsheet_id,
				new Google_Service_Sheets_BatchUpdateValuesByDataFilterRequest([
					"data"=> array($request),
					"includeValuesInResponse"=> FALSE,
					"valueInputOption"=> "USER_ENTERED"
				]),
			);
			
			if(is_null($res->responses)){

				$product  = wc_get_product( $id );
				
				if($settings['products_spreadsheet_group_by_categories'] == "yes"){

					$terms = get_the_terms( $id, 'product_cat' );
					if($data[1] != "" && $data[1] != null){
						$terms = get_the_terms( $data[0], 'product_cat' );
					}
					
					foreach ($terms as $term) {
						$slug = $term->slug;
						$category_id = $term->term_id;

						if(isset($this->products_cat[$slug])){

							$a1range = $slug . "!A2";

							$result = $this->google_snippets->appendValues($spreadsheet_id, $a1range, "USER_ENTERED", [$data]);
							
							if(isset($result->tableRange) && !is_null($result->tableRange)){

								preg_match( '/!A1:[A-Z]{1}(\d+)/', $result->tableRange, $matches);
								
								$start_index = (int) $matches[1];
								
								$metadata_id = empty($data[1]) ? $data[0] : $data[1];

				
								
								$createDeveloperMetadataRequests[] = new Google_Service_Sheets_Request([
									'createDeveloperMetadata' => [
										'developerMetadata' => [
											'metadataId' => (int) $metadata_id,
											'metadataKey' => 'id',
											'metadataValue' => (string) $metadata_id,
											'location' => [
												"dimensionRange" => [
													"sheetId" => $category_id,
													"dimension"=> "ROWS",
													"startIndex"=> $start_index,
													"endIndex"=> $start_index+1
												]
										
											],
											'visibility' => "DOCUMENT"
										]
									],
									
								]);

		
								$body = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
									'requests' => $createDeveloperMetadataRequests
			
								]);
								
								$meta_req =$this->service->spreadsheets->batchUpdate($spreadsheet_id, $body);
								
							
								
							}

						}
					
					}

				}else{


					$a1range = $settings['sheet_title'] . "!A2";

					$result = $this->google_snippets->appendValues($spreadsheet_id, $a1range, "USER_ENTERED", [$data]);
					
					if(isset($result->tableRange) && !is_null($result->tableRange)){

						preg_match( '/!A1:[A-Z]{1}(\d+)/', $result->tableRange, $matches);
						
						$start_index = (int) $matches[1];
						
						$metadata_id = empty($data[1]) ? $data[0] : $data[1];

						$createDeveloperMetadataRequests[] = new Google_Service_Sheets_Request([
							'createDeveloperMetadata' => [
								'developerMetadata' => [
									'metadataId' => (int) $metadata_id,
									'metadataKey' => 'id',
									'metadataValue' => (string) $metadata_id,
									'location' => [
										"dimensionRange" => [
											"sheetId" => $settings['sheet_id'],
											"dimension"=> "ROWS",
											"startIndex"=> $start_index,
											"endIndex"=> $start_index+1
										]
								
									],
									'visibility' => "DOCUMENT"
								]
							],
							
						]);


						$body = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
							'requests' => $createDeveloperMetadataRequests
	
						]);
						
						$meta_req =$this->service->spreadsheets->batchUpdate($spreadsheet_id, $body);
						
						
					}

				}


			}

			return $res;

		}catch(Google\Service\Exception $e){
			echo '<pre>';var_dump($e->getMessage());echo '</pre>';exit();

		}
		

	}

	public function update_sheet(){

		$settings = $this->settings;

		$spreadsheet_id = $settings['spreadsheet_id'];
		
		$spreadsheets = $this->google_snippets->getSpreadsheet($spreadsheet_id);
		
		$sheets_rowcount = array();

		foreach ($spreadsheets->getSheets() as $sheet) {

			$sheet_properties = $sheet->getProperties();
			$title  = $sheet_properties->getTitle();
			$sheets_rowcount[$title] = $sheet_properties->gridProperties->rowCount;

		}
		

		$categories = array_keys($this->products_cat);

		$requests = array();
		foreach($categories as $cat_key => $category){
			
			if($sheets_rowcount[$category] > 1){

				$requests[] = array(
					"deleteDimension" => [
						"range"=> [
						  "sheetId"=> SHEETS_VAPELAB_CAT[$category],
						  "dimension"=> "ROWS",
						  "startIndex"=> 1
						]
					]
				);
				
				$requests[] = array(
					'deleteDeveloperMetadata' => [
						'dataFilter' => [
							'developerMetadataLookup' => [
								'locationType' => "ROW",
								'metadataLocation' => [
									'sheetId' => SHEETS_VAPELAB_CAT[$category],
								]
							],	
						],
					]
				);

			}
			
		}
		
		if(count($requests) > 0){

			$deleteDimensionRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
				'requests' => $requests
			]);
	
			$deleteDimensionResponse = $this->service->spreadsheets->batchUpdate($spreadsheet_id, $deleteDimensionRequest);

		}
		
		
		
		$products_ids = array();
		
		foreach($categories as $cat_key => $category){

			$args = array(
				'status' => 'publish',
				'type' => array('variable','simple'),
				'limit' => -1,
				'category' => array( $category ),
				'orderby' => 'id',
				'order' => 'ASC',
			);

			$products = wc_get_products( $args );
			
			$batch = array(
				array(
					"PRODUCTO ID",
					"VARIATION ID",
					"NOMBRE",
					"VARIACIÓN",
					"SKU",
					"COSTO",
					"PRECIO REGULAR",
					"PRECIO REBAJADO",
					"STOCK",
					"PESO ( KG )",
					"LARGO ( CM )",
					"ANCHO ( CM )",
					"ALTO ( CM )",
					"UPDATE",
				),
			);

			$index = 1;

			$createDeveloperMetadataRequests = array(); $deleteDeveloperMetadataRequests = array();
		
			foreach($products as $key =>  $product){

				$product_data = $product->get_data();

				$product = wc_get_product( $product_data['id'] );
				$product_name = $product_data['name'];

				if(get_class($product) == 'WC_Product_Variable'){

					$variations = $product->get_children();
					
					if(!empty($variations)){

						sort($variations);
						
						foreach($variations as $variation_id){

							if(!in_array($variation_id,$products_ids)){

								$variation = wc_get_product( $variation_id );
								$variation_data = $variation->get_data();

								$variation_name = explode($product_name,$variation_data['name'])[1];
								$variation_name = trim(explode("-",$variation_name)[1]);

								$cost = $variation->get_meta('_wc_cog_cost');
						
								$batch[] = array(
									$product_data['id'],
									$variation_data['id'],
									!empty($product_name) ? $product_name : "",
									!empty($variation_name) ? $variation_name : "N/A",
									!empty($variation_data['sku']) ? $variation_data['sku'] : "",
									!empty($cost) ? $cost : "",
									!empty($variation_data['regular_price']) ? $variation_data['regular_price'] : "",
									!empty($variation_data['sale_price']) ? $variation_data['sale_price'] : "",
									!empty($variation_data['stock_quantity']) ? $variation_data['stock_quantity'] : 0,
									!empty($variation_data['weight']) ? $variation_data['weight'] : "",
									!empty($variation_data['length']) ? $variation_data['length'] : "",
									!empty($variation_data['width']) ? $variation_data['width'] : "",
									!empty($variation_data['height']) ? $variation_data['height'] : "",
									""
								);

								$createDeveloperMetadataRequests[] = new Google_Service_Sheets_Request([
									'createDeveloperMetadata' => [
										'developerMetadata' => [
											'metadataId' => $variation_data['id'],
											'metadataKey' => 'id',
											'metadataValue' => (string) $variation_data['id'],
											'location' => [
												"dimensionRange" => [
													"sheetId" => SHEETS_VAPELAB_CAT[$category],
													"dimension"=> "ROWS",
													"startIndex"=> $index,
													"endIndex"=> $index+1
												]
										
											],
											'visibility' => "DOCUMENT"
										]
									],
									
								]);

								$products_ids[] = $variation_data['id'];
								$index = $index + 1;

							}
							
						}

					}

				}else{

					if(!in_array($product_data['id'],$products_ids)){

						$cost = $product->get_meta('_wc_cog_cost');
				
						$batch[] = array(
							$product_data['id'],
							'',
							!empty($product_data['name']) ? $product_data['name'] : "",
							"N/A",
							!empty($product_data['sku']) ? $product_data['sku'] : "",
							!empty($cost) ? $cost : "",
							!empty($product_data['regular_price']) ? $product_data['regular_price'] : "",
							!empty($product_data['sale_price']) ? $product_data['sale_price'] : "",
							!empty($product_data['stock_quantity']) ? $product_data['stock_quantity'] : 0,
							!empty($product_data['weight']) ? $product_data['weight'] : "",
							!empty($product_data['length']) ? $product_data['length'] : "",
							!empty($product_data['width']) ? $product_data['width'] : "",
							!empty($product_data['height']) ? $product_data['height'] : "",	
							""	
						);

						$createDeveloperMetadataRequests[] = new Google_Service_Sheets_Request([
							'createDeveloperMetadata' => [
								'developerMetadata' => [
									'metadataId' => $product_data['id'],
									'metadataKey' => 'id',
									'metadataValue' => (string) $product_data['id'],
									'location' => [
										"dimensionRange" => [
											"sheetId" => SHEETS_VAPELAB_CAT[$category],
											"dimension"=> "ROWS",
											"startIndex"=> $index,
											"endIndex"=> $index+1
										]
								
									],
									'visibility' => "DOCUMENT"
								]
							],
							
						]);
						
						$products_ids[] = $product_data['id'];
						$index = $index + 1;

					}

				}

			}
			
			$response = $this->google_snippets->batchUpdateValues($spreadsheet_id,$category.'!A1:N'.$index ,"USER_ENTERED",$batch);

			$body = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
				'requests' => $createDeveloperMetadataRequests
		
			]);
	
			$r = $this->service->spreadsheets->batchUpdate($spreadsheet_id,$body);
			
			

		}

		return $spreadsheet_id;


	}

	public function update_woo($settings){

		$google_client = new WC_Vapelab_Sheets_Connector_Google_Client();

		$google_client->client->addScope(Google\Service\Drive::DRIVE);

		$service = new Google_Service_Sheets($google_client->client);

		$spreadsheet_snippets = new WC_Vapelab_Sheets_Connector_Spreadsheet_Snippets($service);
		
		$spreadsheet_id = $settings['spreadsheet_id'];

		$result = $this->google_snippets->batchGetValues($spreadsheet_id,"Inv!A:M");
		
		$data = ($result->valueRanges[0]->values);

		if(!is_null($data)){
			$max_row = count($data);

			array_shift($data);

			
			
			foreach($data as $row){
				
				if(!empty($row[1])){

					$product  = wc_get_product( $row[1] );

				}else{
					
					$product  = wc_get_product( $row[0] );

				}

				

				if(!is_null($product)){

					
					if(isset($row[5]) && !empty($row[5])){
						$product->update_meta_data('_wc_cog_cost', $row[5]);
					}
		
					if(isset($row[6]) && !empty($row[6])){
						$product->set_regular_price($row[6]);
					}
		
					if(isset($row[7]) && !empty($row[7])){
						$product->set_sale_price($row[7]);
					}
		
					if(isset($row[8]) && !empty($row[8])){
						$product->set_stock_quantity($row[8]);
					}
		
					if(isset($row[9]) && !empty($row[9])){
						$product->set_weight($row[9]);
					}
		
					if(isset($row[10]) && !empty($row[10])){
						$product->set_length($row[10]);
					}
		
					if(isset($row[11]) && !empty($row[11])){
						$product->set_width($row[11]);
					}
		
					if(isset($row[12]) && !empty($row[12])){
						$product->set_height($row[12]);
					}

					$product->save();
				}

			}

			$spreadsheets = $this->google_snippets->getSpreadsheet($spreadsheet_id);
			

			$body = new Google_Service_Sheets_ClearValuesRequest();
			/*
			$rowCount = $spreadsheets->sheets[0]['properties']['gridProperties']['rowCount'];
			$this->service->spreadsheets_values->clear($spreadsheet_id,'desechables!N2:N'.$rowCount,$body);

			$rowCount = $spreadsheets->sheets[1]['properties']['gridProperties']['rowCount'];
			$this->service->spreadsheets_values->clear($spreadsheet_id,'equipos-para-vapear!N2:N'.$rowCount,$body);

			$rowCount = $spreadsheets->sheets[2]['properties']['gridProperties']['rowCount'];
			$this->service->spreadsheets_values->clear($spreadsheet_id,'pods-resistencias!N2:N'.$rowCount,$body);

			$rowCount = $spreadsheets->sheets[3]['properties']['gridProperties']['rowCount'];
			$this->service->spreadsheets_values->clear($spreadsheet_id,'accesorios!N2:N'.$rowCount,$body);

			$rowCount = $spreadsheets->sheets[4]['properties']['gridProperties']['rowCount'];
			$this->service->spreadsheets_values->clear($spreadsheet_id,'e-liquids!N2:N'.$rowCount,$body);

			$rowCount = $spreadsheets->sheets[5]['properties']['gridProperties']['rowCount'];
			$this->service->spreadsheets_values->clear($spreadsheet_id,'sal-de-nicotina!N2:N'.$rowCount,$body);

			$rowCount = $spreadsheets->sheets[6]['properties']['gridProperties']['rowCount'];
			$this->service->spreadsheets_values->clear($spreadsheet_id,'alternativo!N2:N'.$rowCount,$body);
			*/
		}
		

	}

}