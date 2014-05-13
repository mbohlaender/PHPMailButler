<?php
/**
 *
 * @file
 * @version 0.1
 * @copyright 2014 Marcel Bohländer
 * @author Marcel Bohländer <marcel-bohlaender@gmx.de>
 */
class MailSender
{
	/** @var  ConfigParser */
	private $configParser;
	/** @var  Logger */
	private $logger;

	/**
	 * Constructs a MailSender
	 *
	 * @param ConfigParser $_configParser The config parser object with the merged config
	 * @param Logger $_logger A logger for giving some debug output
	 */
	public function __construct($_configParser, $_logger)
	{
		$this->configParser = $_configParser;
		$this->logger = $_logger;
	}

	/**
	 *
	 */
	public function run()
	{
		$this->logger->addInfo("Starting export all available files...","MAILSENDER");
		$exportPath = $this->configParser->get("exportPath");
		$fromMailAddress = $this->configParser->get("sendFromMailAddress");
		$fromFullName = $this->configParser->get("sendFromFullName","bau-rec");
		$toMailAddress = $this->configParser->get("sendToMailAddress");

		$this->logger->addDebug("Export Path: $exportPath, from: $fromMailAddress, full name: $fromFullName, to: $toMailAddress","MAILSENDER");

		if ($exportPath && $fromMailAddress && $fromFullName && $toMailAddress)
		{
			// generate the response mail for the current customer
			$mail = new PHPMailer();

			$mail->WordWrap = 200;
			$mail->CharSet = "UTF-8";

			$mail->Subject = "Datenaustausch Waage - Faktura";
			$mail->From = $fromMailAddress;
			$mail->FromName = $fromFullName;
			$mail->AddAddress($toMailAddress);

			$mail->Body = "Im Anhang befinden sich alle Dateien, die für Ihren Import bereit stehen.";

			$this->logger->addDebug("Trying to send email to \"$toMailAddress\" with ".count($attachments)." attachments","MAILSENDER");

			if ($mail->Send())
			{
				$this->logger->addDebug("Email successfully sent","MAILSENDER");
			}
			else $this->logger->addError("Email couldn't be sent","MAILSENDER");
		}
		else $this->logger->addError("exportPath, sendFromMailAddress, sendFromFullName or sendToMailAddress may not set correctly in the config files, please check it!","MAILSENDER");
	}
}