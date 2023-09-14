<?php

/**
 * Plugin Name:       WooCommerce Sheets Connector for VapeLab 
 * Description:       Syncs data between your online store and Google Sheets. It automates the transfer of information, ensuring real-time updates for orders, product details, and inventory. With customizable mapping and error handling, managing your store's data has never been easier. Stay organized and save time with our efficient synchronization solution.
 * Version:           3.0.5
 * Author:            VapeLab
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wc-vapelab-sheets-connector
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WC_VAPELAB_SHEETS_CONNECTOR_VERSION', '3.0.5' );

/*
define( 'WC_VAPELAB_SHEETS_CONNECTOR_ENV', 'PROD' );
define( 'WC_VAPELAB_SHEETS_CONNECTOR_COMPANY_ID', 1 );
define( 'WC_VAPELAB_SHEETS_CONNECTOR_COMPANY', 'VL' );

define( 'WC_VAPELAB_SHEETS_CONNECTOR_ENV', 'TOUCH' );
define( 'WC_VAPELAB_SHEETS_CONNECTOR_COMPANY_ID', 2 );
define( 'WC_VAPELAB_SHEETS_CONNECTOR_COMPANY', 'TV' );


define( 'WC_VAPELAB_SHEETS_CONNECTOR_ENV', 'DEV' );
define( 'WC_VAPELAB_SHEETS_CONNECTOR_COMPANY_ID', 3 );
define( 'WC_VAPELAB_SHEETS_CONNECTOR_COMPANY', 'DEV' );
*/

define( 'WC_VAPELAB_SHEETS_CONNECTOR_PATH', __DIR__ );

/*
if($_SERVER['SERVER_NAME'] == 'vapelab.mx'){
	define( 'WC_VAPELAB_SHEETS_CONNECTOR_ENV', 'PROD' );
	define( 'WC_VAPELAB_SHEETS_CONNECTOR_COMPANY_ID', 1 );
	define( 'WC_VAPELAB_SHEETS_CONNECTOR_COMPANY', 'VL' );
}elseif($_SERVER['SERVER_NAME'] == 'touchvapes.com'){
	define( 'WC_VAPELAB_SHEETS_CONNECTOR_ENV', 'TOUCH' );
	define( 'WC_VAPELAB_SHEETS_CONNECTOR_COMPANY_ID', 2 );
	define( 'WC_VAPELAB_SHEETS_CONNECTOR_COMPANY', 'TV' );
}else{
	define( 'WC_VAPELAB_SHEETS_CONNECTOR_ENV', 'DEV' );
	define( 'WC_VAPELAB_SHEETS_CONNECTOR_COMPANY_ID', 3 );
	define( 'WC_VAPELAB_SHEETS_CONNECTOR_COMPANY', 'DEV' );
}
*/

define( 'WC_VAPELAB_SHEETS_CONNECTOR_ENV', 'PROD' );


define( 'SHEETS_VAPELAB_CAT', array(
	'desechables' => 507,
	'equipos-para-vapear' => 45,
	'pods-resistencias' => 1866,
	'accesorios' => 29,
	'e-liquids' => 7,
	'sal-de-nicotina' => 102,
	'alternativo' => 843,
));

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-sheets-vapelab-deactivator.php
 */



register_activation_hook( __FILE__, 'activate_wc_vapelab_sheets_connector' );
register_deactivation_hook( __FILE__, 'deactivate_wc_vapelab_sheets_connector' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wc-vapelab-sheets-connector.php';

/**
 * Begins execution of the plugin.ls
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wc_vapelab_sheets_connector() {

	
	$plugin = new WC_Vapelab_Sheets_Connector();
	$plugin->run();

}


function deactivate_wc_vapelab_sheets_connector() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-vapelab-sheets-connector-deactivator.php';
	WC_Vapelab_Sheets_Connector_Deactivator::deactivate();
}

function activate_wc_vapelab_sheets_connector() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-vapelab-sheets-connector-activator.php';
	WC_Vapelab_Sheets_Connector_Activator::activate();
}



run_wc_vapelab_sheets_connector();
