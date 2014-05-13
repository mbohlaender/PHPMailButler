<?php
/**
 *
 * @file
 * @version 0.1
 * @copyright 2014 Marcel Bohländer
 * @author Marcel Bohländer <marcel-bohlaender@gmx.de>
 */

/**
 * Class Logger - logs messages into a given file with a given prefix
 */
class Logger
{
	private $logFile;
	private $logPrefix;

	public function __construct($_logFile, $_logPrefix)
	{
		$this->logFile = $_logFile;
		$this->logPrefix = $_logPrefix;
	}

	public function addInfo($_message,$_prefix = null)
	{
		$prefix = ($_prefix !== null ? $_prefix : $this->logPrefix);
		file_put_contents($this->logFile,date("Y-m-d H:i:s")."--INFO--$prefix: $_message\n",FILE_APPEND);
	}

	public function addDebug($_message,$_prefix = null)
	{
		$prefix = ($_prefix !== null ? $_prefix : $this->logPrefix);
		file_put_contents($this->logFile,date("Y-m-d H:i:s")."--DEBUG--$prefix: $_message\n",FILE_APPEND);
	}

	public function addError($_message,$_prefix = null)
	{
		$prefix = ($_prefix !== null ? $_prefix : $this->logPrefix);
		file_put_contents($this->logFile,date("Y-m-d H:i:s")."--ERROR--$prefix: $_message\n",FILE_APPEND);
	}
}