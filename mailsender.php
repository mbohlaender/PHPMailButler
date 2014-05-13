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
	 * Sends all supported files out of the export directory to the configured address, backups this files and deletes them out of the export directory
	 */
	public function run()
	{
		$this->logger->addInfo("Starting export all available files...","MAILSENDER");
		$exportPath = $this->configParser->get("exportPath");
		$exportBackupPath = $this->configParser->get("exportBackupPath");
		$exportFileTypes = $this->configParser->get("exportFileTypes",array());
		$fromMailAddress = $this->configParser->get("sendFromMailAddress");
		$mailSenderServerAddress = $this->configParser->get("mailSenderServerAddress");
		$mailSenderServerUsername = $this->configParser->get("mailSenderServerUsername");
		$mailSenderServerPassword = $this->configParser->get("mailSenderServerPassword");
		$fromFullName = $this->configParser->get("sendFromFullName","bau-rec");
		$toMailAddress = $this->configParser->get("sendToMailAddress");

		$this->logger->addDebug("Export Path: $exportPath, export backup path: $exportBackupPath, export filetypes: ".implode(" ",$exportFileTypes).", from: $fromMailAddress, full name: $fromFullName, to: $toMailAddress","MAILSENDER");

		if ($exportPath && $exportBackupPath && $fromMailAddress && $fromFullName && $toMailAddress && $mailSenderServerAddress && $mailSenderServerUsername && $mailSenderServerPassword)
		{
			// generate the response mail for the current customer
			$mail = new PHPMailer();

			$mail->IsSMTP();                                   // per SMTP verschicken
			$mail->Host     = $mailSenderServerAddress; // SMTP-Server
			$mail->SMTPAuth = true;     // SMTP mit Authentifizierung benutzen
			$mail->Username = $mailSenderServerUsername;  // SMTP-Benutzername
			$mail->Password = $mailSenderServerPassword; // SMTP-Passwort


			$mail->WordWrap = 200;
			$mail->CharSet = "UTF-8";

			$mail->Subject = "Datenaustausch Waage - Faktura";
			$mail->From = $fromMailAddress;
			$mail->FromName = $fromFullName;
			$mail->addAddress($toMailAddress);

			$mail->Body = "Im Anhang befinden sich alle Dateien, die für Ihren Import bereit stehen.";


			$this->logger->addDebug("Load attachments out of the export folder ($exportPath)","MAILSENDER");

			// add files
			$attachments = scandir($exportPath);
			$addedAttachments = array();
			foreach ($attachments as $attachment)
			{
				if (is_file("$exportPath/$attachment"))
				{ // attachment is a file and no directory
					if (count($exportFileTypes) > 0)
					{ // file type needs to be exported
						if (in_array(pathinfo("$exportPath/$attachment",PATHINFO_EXTENSION),$exportFileTypes))
						{
							$this->logger->addDebug("Add $attachment as attachment.","MAILSENDER");
							$mail->addAttachment("$exportPath/$attachment");
							$addedAttachments[] = $attachment;
						}
						else $this->logger->addDebug("File $attachment is not supported to be exported.","MAILSENDER");
					}
					else
					{ // no restrictions due to file types
						$this->logger->addDebug("Add $attachment as attachment.","MAILSENDER");
						$mail->addAttachment("$exportPath/$attachment");
						$addedAttachments[] = $attachment;
					}
				}
			}

			if (count($addedAttachments) > 0)
			{
				// send mail
				$this->logger->addDebug("Trying to send email to \"$toMailAddress\" with ".count($addedAttachments)." attachments","MAILSENDER");
				if ($mail->Send())
				{
					$this->logger->addDebug("Email successfully sent","MAILSENDER");
				}
				else $this->logger->addError("Email couldn't be sent","MAILSENDER");

				// backup attachments, delete from the main export path
				foreach ($addedAttachments as $addedAttachment)
				{
					// check whether we can backup the exported files
					if (is_dir("$exportBackupPath"))
					{ // backup directory exists
						$this->logger->addDebug("Add $addedAttachment to the backup directory.","MAILSENDER");
						copy("$exportPath/$addedAttachment","$exportBackupPath/$addedAttachment");
					}
					// clean up the export directory
					$this->logger->addDebug("Delete $addedAttachment out of the export directory.","MAILSENDER");
					unlink("$exportPath/$addedAttachment");
				}
			}
			$this->logger->addDebug("No files to export, abort.","MAILSENDER");
		}
		else $this->logger->addError("exportPath, sendFromMailAddress, sendFromFullName or sendToMailAddress may not set correctly in the config files, please check it!","MAILSENDER");
	}
}