<?php

/**
 * Fired during plugin activation

 * @package    WC_Vapelab_Sheets_Connector
 * @subpackage WC_Vapelab_Sheets_Connector/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    WC_Vapelab_Sheets_Connector
 * @subpackage WC_Vapelab_Sheets_Connector/includes
 */
class WC_Vapelab_Sheets_Connector_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		
        wp_schedule_event(strtotime('11:30:00'), 'daily',  'wc_vapelab_sheets_connector_orders_move_v2');
        wp_schedule_event(strtotime('11:25:00'), 'daily',  'wc_vapelab_sheets_connector_shipping_move_v2');
		
	}

}
