<?php
/**
 *
 * @file
 * @version 0.1
 * @copyright 2014 CN-Consult GmbH
 * @author Marcel Bohländer <marcel.bohlaender@cn-consult.eu>
 */
include_once("boot.php");
// build config parser
$configParser = new ConfigParser("phpmailbutler-general-conf.json","steinbruch-conf.json");
// receive emails
$mailReceiver = new MailReceiver($configParser);