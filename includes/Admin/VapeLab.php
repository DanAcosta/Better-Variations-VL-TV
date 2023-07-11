<?php 
/*********************************************************************/
/* PROGRAM    (C) 2022 VapeLab                                       */
/* PROPERTY   MÉXICO                                                 */
/* OF         + (52) 56 1720 2964                                    */
/*********************************************************************/

namespace VapeLab\WooCommerce\Admin;

//declare(strict_types=1);

defined('ABSPATH') || exit;

// make sure that we will include shared class only once
if (!class_exists(__NAMESPACE__ . '\\VapeLab')):

class VapeLab
{
	protected static $instance = null;
	protected $mainMenuId;
	protected $author;
	protected $isRegistered;

	static public function instance()
	{
		if (!isset(self::$instance)) {
			self::$instance = new VapeLab();
		}

		return self::$instance;
	}

	public function __construct()
	{
		$this->mainMenuId = 'vapelab';
		$this->author = 'vapelab';
		$this->isRegistered = false;
	}

	public function register()
	{
		if ($this->isRegistered) {
			return;
		}

		$this->isRegistered = true;
		
		add_action('admin_init', array($this, 'onEnqueueScripts'));

	}

	public function display()
	{

	}

	public function onEnqueueScripts()
	{
		$styles = "
			.{$this->mainMenuId} .card {
				max-width: none;
			}

			.{$this->mainMenuId} .item {
				border-bottom: 1px solid #eee;
				margin: 0;
				padding: 10px 0;
				display: inline-block;
				width: 100%;
			}

			.{$this->mainMenuId} .card ul {
				list-style-type: inherit;
				padding: inherit;
			}

			.{$this->mainMenuId} .item:last-child {
				border-bottom: none;
			}

			.{$this->mainMenuId} .item a {
				display: inline-block;
				width: 100%;
				color: #23282d;
				text-decoration: none;
				outline: none;
				box-shadow: none;
			}

			.{$this->mainMenuId} .item .num {
				width: 40px;
				height: 40px;
				margin-bottom: 30px;
				float: left;
				margin-right: 10px;
				border-radius: 20px;
				background-color: #0079c6;
				text-align: center;
				line-height: 40px;
				color: #ffffff;
				font-weight: bold;
				font-size: 20px;
			}

			.{$this->mainMenuId} .item p {
				margin: 5px 0;
			}

			.{$this->mainMenuId} .item .title {
				font-weight: bold;
			}

			.{$this->mainMenuId} .item .extra {
				opacity: .5;
			}
		";

		$styleId = $this->mainMenuId . '_custom_css';
		wp_register_style($styleId, false);
    	wp_enqueue_style($styleId);
		wp_add_inline_style($styleId, $styles);
	}

}

endif;