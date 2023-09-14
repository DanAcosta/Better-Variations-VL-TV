<?php

/**
 * Fired during plugin deactivation
 *
 * @package    WC_Vapelab_Sheets_Connector
 * @subpackage WC_Vapelab_Sheets_Connector/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    WC_Vapelab_Sheets_Connector
 * @subpackage WC_Vapelab_Sheets_Connector/includes
 */
class WC_Vapelab_Sheets_Connector_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {

		if (wp_next_scheduled ( 'wc_vapelab_sheets_connector_move_orders' )) { 
            wp_clear_scheduled_hook(  'wc_vapelab_sheets_connector_move_orders' );
        }

        if (wp_next_scheduled ( 'wc_vapelab_sheets_connector_move_shippings' )) { 
            wp_clear_scheduled_hook(  'wc_vapelab_sheets_connector_move_shippings' );
        }

		if (wp_next_scheduled ( 'wc_vapelab_sheets_connector_shipping_move' )) { 
            wp_clear_scheduled_hook(  'wc_vapelab_sheets_connector_shipping_move' );
        }

		if (wp_next_scheduled ( 'wc_valpelab_sheets_connector_orders_move' )) { 
            wp_clear_scheduled_hook(  'wc_valpelab_sheets_connector_orders_move' );
        }

		if (wp_next_scheduled ( 'wc_vapelab_sheets_connector_orders_move' )) { 
            wp_clear_scheduled_hook(  'wc_vapelab_sheets_connector_orders_move' );
        }
		
        if (wp_next_scheduled ( 'wc_valpelab_sheets_connector_move_shippings' )) { 
            wp_clear_scheduled_hook(  'wc_valpelab_sheets_connector_move_shippings' );
        }

		if (wp_next_scheduled ( 'wc_valpelab_sheets_connector_shippings_move' )) { 
            wp_clear_scheduled_hook(  'wc_valpelab_sheets_connector_shippings_move' );
        }

		if (wp_next_scheduled ( 'wc_vapelab_sheets_connector_shippings_move' )) { 
            wp_clear_scheduled_hook(  'wc_vapelab_sheets_connector_shippings_move' );
        }

		if (wp_next_scheduled ( 'wc_valpelab_sheets_connector_shippings_move' )) { 
			wp_clear_scheduled_hook(  'wc_valpelab_sheets_connector_shippings_move' );
        }

        if (wp_next_scheduled ( 'wc_vapelab_sheets_connector_shipping_move_v2' )) { 
			wp_clear_scheduled_hook(  'wc_vapelab_sheets_connector_shipping_move_v2' );
        }

        if (wp_next_scheduled ( 'wc_vapelab_sheets_connector_orders_move_v2' )) { 
			wp_clear_scheduled_hook(  'wc_vapelab_sheets_connector_orders_move_v2' );
        }


	}

}
