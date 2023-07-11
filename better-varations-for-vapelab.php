<?php
 /**
 * Plugin Name:       Better Variations for VapeLab
 * Description:       Disable, grey out, style and sort your sold-out and out-of-stock variations.
 * Version:           1.0.1
 * Author:            VapeLab
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       better-varations-for-vapelab
 */

namespace VapeLab\WooCommerce\Settings;

defined('ABSPATH') || exit;

require_once(__DIR__ . '/includes/autoloader.php');
	
(new Plugin(
		__FILE__, 
		'Better Variations for VapeLab',
        'Disable, grey out, style and sort your sold-out and out-of-stock variations', 
		'1.0.1'
	)
)->register();
