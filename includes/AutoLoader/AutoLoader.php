<?php 
/*********************************************************************/
/* PROGRAM    (C) 2022 VapeLab                                       */
/* PROPERTY   MÉXICO                                                 */
/* OF         + (52) 56 1720 2964                                    */
/*********************************************************************/

namespace VapeLab\WooCommerce\AutoLoader;

if (!class_exists(__NAMESPACE__ . '\\AutoLoader')):

class AutoLoader
{
	protected $namespace;
	protected $includePath;

	public function __construct($includePath, $namespace)
	{
		$this->namespace = trim($namespace, '\\') . '\\';
		$this->includePath = $includePath;
	}

	public function autoload($class)
	{
		if (strpos($class, $this->namespace) === 0) {
			$filePath = $this->includePath . '/' . str_replace('\\', '/', substr($class, strlen($this->namespace))) . '.php';
			if (file_exists($filePath)) {
				include_once($filePath);
			}
		}
	}

	public function register()
	{
		spl_autoload_register(array($this, 'autoload'));
	}
}

endif;