<?php 
/*********************************************************************/
/* PROGRAM    (C) 2022 VapeLab                                       */
/* PROPERTY   MÉXICO                                                 */
/* OF         + (52) 56 1720 2964                                    */
/*********************************************************************/

require_once(__DIR__ . '/AutoLoader/AutoLoader.php');
(new \VapeLab\WooCommerce\AutoLoader\AutoLoader(__DIR__, 'VapeLab\\WooCommerce\\'))->register();
(new \VapeLab\WooCommerce\AutoLoader\AutoLoader(__DIR__, 'VapeLab\\'))->register();