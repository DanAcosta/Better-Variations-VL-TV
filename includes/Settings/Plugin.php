<?php 
/*********************************************************************/
/* PROGRAM    (C) 2022 VapeLab                                       */
/* PROPERTY   MÉXICO                                                 */
/* OF         + (52) 56 1720 2964                                    */
/*********************************************************************/

namespace VapeLab\WooCommerce\Settings;

defined('ABSPATH') || exit;

if (!class_exists(__NAMESPACE__ . '\\Plugin')):

class Plugin
{
	protected $id;
	protected $mainMenuId;
	protected $adapterName;
	protected $title;
	protected $description;
	protected $optionKey;
	protected $settings;
	protected $pluginSettings;
	protected $pluginPath;
	protected $version;
	protected $adapter;
	protected $settingsFormHooks;
	protected $logger;
	protected $pageDetector;
	protected $cartProxy;
	protected $sessionProxy;

    public function __construct($pluginPath, $adapterName, $description = '', $version = null) 
    {
		$this->id =  basename($pluginPath, '.php');
		$this->pluginPath = $pluginPath;
		$this->adapterName = $adapterName;
		$this->title = 'Better Varations';
		$this->description = $description;
		$this->version = $version;
		$this->optionKey = sprintf('woocommerce_%s_settings', str_replace("-","_",$this->id));
		$this->settings = array();
		$this->pluginSettings = array();
		
		$this->mainMenuId = 'vapelab';
		$this->adapter = null;
		$this->settingsFormHooks = null;

	}


	public function register()
	{
		if (!function_exists('is_plugin_active')) {
			require_once(ABSPATH . 'wp-admin/includes/plugin.php');
		}

		// do not register when WooCommerce is not enabled
		if (!is_plugin_active('woocommerce/woocommerce.php')) {
			return;
		}

		$this->loadSettings();

		if (is_admin()) {
			\VapeLab\WooCommerce\Admin\VapeLab::instance()->register();
		}else{
			
			
			/*
			wp_register_script(
				'select2-js',
				'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
				'jQuery',
				'4.1.0-rc.0',
			);

			//Add the Select2 JavaScript file
			wp_enqueue_script( 'select2-js');
			*/


			add_action(
				'wp_enqueue_scripts',
				function () {

					$assets =  array(
						'src' => plugins_url('assets/js/index.js', $this->pluginPath),
						'dependencies' => array(
							'jQuery',
						),
						'version' => $this->version,
					);

					wp_register_script(
						'vapelab-better-varations',
						$assets['src'],
						$assets['dependencies'],
						$assets['version'],
					);
					
					wp_enqueue_script( 'vapelab-better-varations' );

				},
				10, 0
			);

		
			add_filter( 'woocommerce_dropdown_variation_attribute_options_html', array($this,'override_attribute_variation_display'), 10, 2 ); 

		}



		/*
		add_filter('plugin_action_links_' . plugin_basename($this->pluginPath), array($this, 'onPluginActionLinks'), 1, 1);
		add_filter('woocommerce_shipping_methods', array($this, 'addShippingMethod'));
		add_filter($this->id . '_getPluginSettings', array($this, 'getPluginSettings'), 1, 0);
		add_filter($this->id . '_is_enabled', array($this, 'onIsEnabled'), 10, 1);
		add_filter($this->id . '_init_form_fields', array($this, 'updateFormFields'), 1, 1);
		add_filter($this->id . '_service_name', array($this, 'getDefaultServiceName'), 1, 2);
		add_filter($this->id . '_isCart', array($this->pageDetector, 'isCart'), 1, 0);
		add_filter($this->id . '_isCheckout', array($this->pageDetector, 'isCheckout'), 1, 0);
		add_action('plugins_loaded', array($this, 'initLazyClassProxies'), 1, 0);
		// we need to use plugins_loaded so other plugins will be able to integrate with it before it is too late
		add_action('plugins_loaded', array($this, 'registerFeatures'), 10, 0);
		add_action('wp_loaded', array($this, 'calculateShippingOnCheckout'), 1, 0);
		add_action('woocommerce_after_checkout_validation', array($this, 'onCheckoutValidation'), PHP_INT_MAX, 2);
		add_filter('woocommerce_billing_fields', array($this, 'setRequiredFields'), 10, 1);
		add_filter('woocommerce_shipping_fields', array($this, 'setRequiredFields'), 10, 1);
		*/



	}

	public function override_attribute_variation_display( $html, $args ) {

		global $product;
		$attribute_name = $args['attribute'];

		$variations_ids = $product->get_children();
		
		$outofstock = $instock = array();
		foreach($variations_ids as $variation_id){

			$variation = $product->get_child($variation_id);
			$id = $variation->get_id();
			if($variation->get_stock_status() == 'outofstock'){
				$outofstock[$id] = $variation->get_attribute($attribute_name);
			}else{
				$instock[$id] = $variation->get_attribute($attribute_name);
			}

		}

		sort($outofstock);
		sort($instock);

		$content = new \DOMDocument('1.0','UTF-8');
    	$content->preserveWhiteSpace = FALSE;

		@$content->loadHTML(utf8_decode($html));
		$elements = $content->getElementsByTagName('option');
		$elements_length =  $elements->length;
	
		for ($k = 1 ; $k < $elements_length; $k++){ 
			
			$option = $elements->item($i+1);

			$option->parentNode->removeChild($option); 

		}

		$select = $content->getElementsByTagName('select')->item(0);
	

		foreach($instock as $value){
		
			$option = $content->createElement('option');
			$option->setAttribute('class', 'attached enabled');
			$option->setAttribute('value', $value);
			$option->appendChild(new \DOMText($value));
			$select->appendChild($option);

		}

		foreach($outofstock as $value){
		
			$option = $content->createElement('option');
			$option->setAttribute('class', 'attached enabled');
			$option->setAttribute('value', $value);
			$option->appendChild(new \DOMText($value." - Agotado"));
			$select->appendChild($option);

		}



		return $content->saveHTML();


	}

	public function setRequiredFields($fields)
	{
		if ('yes' !== $this->settings['requireCompanyName']) {
			return $fields;
		}
		
		if (isset($fields['billing_company'])) {
			$fields['billing_company']['required'] = true;
		}

		if (isset($fields['shipping_company'])) {
			$fields['shipping_company']['required'] = true;
		}

		return $fields;
	}

	public function onCheckoutValidation($postedData, $checkoutErrors)
	{		
		if ($this->settings['validateAddress'] != 'yes') {
			return;
		}
		
		$validationErrors = $this->sessionProxy->get($this->id . '_validationErrors');
		if (empty($validationErrors)) {
			$validationErrors = array();
		}

		$this->logger->debug(__FILE__, __LINE__, 'onCheckoutValidation: ' . print_r($validationErrors, true));

		foreach ($validationErrors as $fieldKey => $errors) {
			$errorPrefix = '';
			if ($fieldKey == 'origin') {
				$errorPrefix = __('From Address:', $this->id);
			} else if ($fieldKey == 'destination') {
				$errorPrefix = __('Shipping Address:', $this->id);
			}

			foreach ($errors as $idx => $error) {
				$checkoutErrors->add($this->id . '_validation_error_' . $idx, sprintf('<strong>%s</strong> %s', $errorPrefix, $error));
			}
		}
	}

	public function onPluginActionLinks($links)
	{
		$link = sprintf('<a href="%s">%s</a>', admin_url('admin.php?page=wc-settings&tab=shipping&section=' . $this->id), __('Settings', $this->id));
		array_unshift($links, $link);
		return $links;
	}

	public function getPluginSettings()
	{
		return $this->pluginSettings;
	}

	public function updateFormFields($formFields)
	{
		return $this->adapter->updateFormFields($formFields);
	}

	public function getDefaultServiceName($name, $service)
	{
		if (empty($name)) {
			$services = $this->adapter->getServices();
			if (!empty($services[$service])) {
				$name = $services[$service];
			}
		}

		return $name;
	}

	public function initLazyClassProxies()
	{
		// it can't work in the ADMIN or when WC is undefined
		if (is_admin() || !function_exists('WC')) {
			return;
		}

		$this->cartProxy = new \OneTeamSoftware\Proxies\LazyClassProxy('WC_Cart', WC()->cart);
		$this->sessionProxy = new \OneTeamSoftware\Proxies\LazyClassProxy(apply_filters('woocommerce_session_handler', 'WC_Session_Handler'), WC()->session);
	}

	public function registerFeatures()
	{
		$this->loadSettings();

		$this->settingsFormHooks = new Hooks\SettingsForm($this->id, $this->settings);
		$this->settingsFormHooks->register();
	}

	public function calculateShippingOnCheckout()
	{
		if ($this->settings['fetchRatesPageCondition'] != 'checkout') {
			return;
		}

		if (!apply_filters($this->id . '_isCheckout', false)) {
			$this->sessionProxy->set($this->id . '_' . __FUNCTION__, false);
			return;
		}

		$this->logger->debug(__FILE__, __LINE__,  __FUNCTION__);

		$packages = $this->cartProxy->get_shipping_packages();
		if (is_array($packages) && !empty($packages) && !$this->sessionProxy->get($this->id . '_' . __FUNCTION__)) {
			foreach ($packages as $packageKey => $package) {
				$sessionKey = 'shipping_for_package_' . $packageKey;
				$this->sessionProxy->set($sessionKey, null);
			}
	
			$this->cartProxy->calculate_shipping();
			$this->cartProxy->calculate_totals();	

			$this->sessionProxy->set($this->id . '_' . __FUNCTION__, true);
		}
	}

    public function addShippingMethod($methods)
	{
		$this->defineShippingMethodClass();
		$methods[$this->id] = $this->getShippingMethodClassName();

		return $methods;
	}

	public function onIsEnabled($enabled = false)
	{
		if ($this->settings['enabled'] == 'yes') {
			$enabled = true;
		}

		return $enabled;
	}
	
	protected function initSettings()
	{

		$this->settings = array();

	}

	protected function loadSettings()
	{		
		$this->initSettings();
		
		$this->settings = array_merge($this->settings, (array)get_option($this->optionKey, array()));

		$this->pluginSettings = $this->settings;

	}

	protected function getShippingMethodClassName()
	{
		return 'OneTeamSoftware_WooCommerce_Shipping_' . $this->adapterName . '_' . $this->getBaseShippingMethodClassName();
	}

	protected function defineShippingMethodClass()
	{
		$className = $this->getShippingMethodClassName();
		if (class_exists($className)) {
			return;
		}

		$baseShippingMethodClassName = sprintf('\\OneTeamSoftware\\WooCommerce\\Shipping\\%s', $this->getBaseShippingMethodClassName());
		if (!class_exists($baseShippingMethodClassName)) {
			return;
		}

		$adapterInstanceName = $className . '_instance';
		$GLOBALS[$adapterInstanceName] = $this->adapter;
		
		// THIS IS A SAVE CODE, EVAL IS REQUIRED BECAUSE OF WOOCOMMERCE SHIPPING METHOD MECHANISM LIMITATIONS THAT DOES NOT ALLOW TO PASS EXTRA PARAMETERS
		// TO REDUCE CODE DUPLICATION WE NEED TO PASS AN INSTANCE OF AN ADAPTER, WHICH IS ACHIEVED WITH THIS CODE
		$classDefinition = sprintf('class %s extends %s
		{
			public function __construct($instance_id = 0) 
			{
				parent::__construct(
					\'%s\',
					$GLOBALS[\'%s\'],
					$instance_id,
					\'%s\',
					\'%s\'
				);
			}
		};', $className, $baseShippingMethodClassName, $this->id, $adapterInstanceName, addcslashes($this->title, '\''), addcslashes($this->description, '\''));
		
		eval($classDefinition);
	}
};

endif;
