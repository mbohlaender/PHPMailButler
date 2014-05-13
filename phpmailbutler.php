<?php
/**
 *
 * @file
 * @version 0.1
 * @copyright 2014 Marcel Bohländer
 * @author Marcel Bohländer <marcel-bohlaender@gmx.de>
 */
include_once("boot.php");
// build config parser
$configParser = new ConfigParser(__DIR__."/data/phpmailbutler-general-conf.json",__DIR__."/data/customized-conf.json");
// build logger
$logger = new Logger($configParser->get("logFile"),"PHPMailButler");

$logger->addInfo("Starting PHPMailButler...");

// send mails
$mailSender = new MailSender($configParser,$logger);
$mailSender->run();

// receive emails
$mailReceiver = new MailReceiver($configParser,$logger);
$mailReceiver->run();


$logger->addInfo("PHPMailButler finished!");