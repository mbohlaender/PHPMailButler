<?php
/**
 *
 * @file
 * @version 0.1
 * @copyright 2014 Marcel Bohländer
 * @author Marcel Bohländer <marcel.bohlaender@gmx.de>
 */
include_once(__DIR__."/../boot.php");

/**
 * Parses the default config file and merges the customized file into the values.
 */
class ConfigParser
{
	private $configFile;
	private $customizedConfigFile;
	private $configValues = array();

	public function __construct($_configFile,$_customizedConfigFile)
	{
		$this->configFile = $_configFile;
		$this->customizedConfigFile = $_customizedConfigFile;

		if (file_exists($this->configFile) && file_exists($this->customizedConfigFile))
		{
			$defaultValues = json_decode(file_get_contents($this->configFile),true);
			$customizedValues = json_decode(file_get_contents($this->customizedConfigFile),true);

			$this->configValues = array_merge($defaultValues,$customizedValues);
		}
	}

	/**
	 * Returns the value of the given key. If the key isn't set it returns null.
	 *
	 * @param string $_key The key for which the value should be returned
	 * @param mixed $_default The default value, if no value is set
	 *
	 * @return string|null
	 */
	public function get($_key, $_default = null)
	{
		if (isset($this->configValues[$_key])) return $this->configValues[$_key];
		else return $_default;
	}
}