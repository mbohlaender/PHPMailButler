<?php
/**
 *
 * @file
 * @version 0.1
 * @copyright 2014 Marcel Bohländer
 * @author Marcel Bohländer <marcel-bohlaender@gmx.de>
 */

/**
 * Loops through all incoming mails, saves their attachments and expunges the mailbox
 */
class MailReceiver
{
	/** @var  ConfigParser */
	private $configParser;
	/** @var  Logger */
	private $logger;

	/**
	 * Constructs a MailReceiver
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
	 * Imports the attachments
	 */
	public function run()
	{
		$this->logger->addInfo("Starting importing of mail attachments...","MAILRECEIVER");
		$mailServerAddress = $this->configParser->get("receiveMailServerAddress");
		$mailServerPort = $this->configParser->get("receiveMailServerPort",110);
		$mailServerUsername = $this->configParser->get("receiveMailServerUsername");
		$mailServerPassword = $this->configParser->get("receiveMailServerPassword");
		$importPath = $this->configParser->get("importPath");
		$allowedIncomingMailAddresses = $this->configParser->get("allowedIncomingMailAddresses",array());

		if ($mailServerAddress && $mailServerPort && $mailServerUsername && $mailServerPassword)
		{
			try
			{
				// build connection to the mail server
				$mailServer = new \Fetch\Server($mailServerAddress,$mailServerPort,"pop3");
				$mailServer->setAuthentication($mailServerUsername,$mailServerPassword);
				$mailServer->setOptions(OP_SILENT);
				$messages = $mailServer->getMessages();

				$this->logger->addDebug(count($messages)." mails in this mailbox","MAILRECEIVER");

				foreach ($messages as $message)
				{ // loop through all messages
					// get address from the sender
					$address = $message->getAddresses("from");
					$from = $address["address"];

					$this->logger->addDebug("Got message from: $from","MAILRECEIVER");

					if (count($allowedIncomingMailAddresses) > 0 && !in_array($from,$allowedIncomingMailAddresses))
					{ // check whether email address is supported
						$this->logger->addError("Mail address is not supported: $from","MAILRECEIVER");
						continue;
					}
					$attachments = $message->getAttachments();
					if ($attachments)
					{
						$this->logger->addDebug(count($attachments)." attachments in this mail","MAILRECEIVER");

						$successfullySavedAttachments = array();
						$unsuccessfullySavedAttachments = array();

						foreach ($attachments as $attachment)
						{
							// save attachments
							$this->logger->addDebug("Try to save ".$attachment->getFilename()." ...","MAILRECEIVER");
							if ($attachment->saveToDirectory($importPath))
							{ // saving attachment was successfully
								$this->logger->addDebug("... ".$attachment->getFilename()." successfully saved","MAILRECEIVER");
								$successfullySavedAttachments[] = $attachment->getFilename();
							}
							else
							{ // attachment couldn't be saved, log it and set an response mail
								$this->logger->addError("... ".$attachment->getFilename()." couldn't be saved","MAILRECEIVER");
								$unsuccessfullySavedAttachments[] = $attachment->getFilename();
							}
						}

						if (count($unsuccessfullySavedAttachments) > 0)
						{ // not all attachments successfully saved
							$this->logger->addError("The following attachments couldn't be saved:","MAILRECEIVER");
							foreach ($unsuccessfullySavedAttachments as $unsuccessfullySavedAttachment)
							{
								$this->logger->addError("---> $unsuccessfullySavedAttachment","MAILRECEIVER");
							}
						}
						else
						{ // all attachments successfully saved
							$this->logger->addDebug("Successfully saved all attachments!","MAILRECEIVER");
						}
					}
					else $this->logger->addInfo("No attachment attached to this message","MAILRECEIVER");

					// delete message
					$this->logger->addDebug("Delete email:\"".$message->getSubject()."\"","MAILRECEIVER");
					$message->delete();
				}

				// expunge all messages which have the delete flag set
				$mailServer->expunge();
				$this->logger->addInfo("Finished importing of mail attachments!","MAILRECEIVER");
			}
			catch (Exception $e)
			{
				$this->logger->addError("Catched exception: ".$e->getMessage(),"MAILRECEIVER");
			}
		}
		else
		{
			$this->logger->addError("","MAILRECEIVER");
		}
	}
}