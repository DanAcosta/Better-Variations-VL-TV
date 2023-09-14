<?php

defined('ABSPATH') || exit;

if (!class_exists(__NAMESPACE__ . '\\VapelabLoggerInstance')):

class VapelabLoggerInstance
{
	private static $instances = array();

	public static function &getInstance($id)
	{
		if (empty(self::$instances[$id])) {
			self::$instances[$id] = new VapelabLogger($id);
		}

		return self::$instances[$id];
	}
}

endif;