<?php
/**
 *
 * @file
 * @version 0.1
 * @copyright 2014 CN-Consult GmbH
 * @author Marcel Bohländer <marcel.bohlaender@cn-consult.eu>
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
			$defaultValues = json_decode($this->configFile,true);
			$customizedValues = json_decode($this->customizedConfigFile,true);
			$this->configValues = array_merge($defaultValues,$customizedValues);
		}
	}

	/**
	 * Returns the value of the given key. If the key isn't set it returns null.
	 *
	 * @param string $_key The key for which the value should be returned
	 *
	 * @return string|null
	 */
	public function get($_key)
	{
		if (isset($this->configValues[$_key])) return $this->configValues[$_key];
		else return null;
	}
}