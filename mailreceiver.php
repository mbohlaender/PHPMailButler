<?php
/**
 *
 * @file
 * @version 0.1
 * @copyright 2014 CN-Consult GmbH
 * @author Marcel Bohländer <marcel.bohlaender@cn-consult.eu>
 */
class MailReceiver
{
	private $configParser;

	public function __construct($_configParser)
	{
		$this->configParser = $_configParser;
	}

	public function run()
	{
		try
		{
			// build connection to the mail server
			$mailServer = new \Fetch\Server($emailServer,$port,"pop3");
			$mailServer->setAuthentication($username,$password);
			$mailServer->setOptions(OP_SILENT);
			$messages = $mailServer->getMessages();

			$log->addDebug(count($messages)." mails in this mailbox");

			foreach ($messages as $message)
			{ // loop through all messages
				// get address from the sender
				$address = $message->getAddresses("from");
				$from = $address["address"];
				$to = $message->getAddresses("to",true);
				// build path out of subject where the attachments will be saved
				$log->addDebug("Check email:\"".$message->getSubject()."\"");
				$pathForAttachment = epfUtil::findSubDirectoryInPath($message->getSubject(),$customer->getDilocSyncRoot()."/".$directory);
				if (count($pathForAttachment) == 1 && file_exists($pathForAttachment[0]))
				{ // the path where the attachments will be saved exists
					$log->addDebug("Attachements will be saved in:\"$pathForAttachment[0]\"");
					// load attachments
					$attachments = $message->getAttachments();
					if ($attachments)
					{
						$log->addDebug(count($attachments)." attachments in this mailbox");
						$successfullySavedAttachments = array();
						$unsuccessfullySavedAttachments = array();
						foreach ($attachments as $attachment)
						{
							// save attachments
							$log->addDebug("Try to save ".$attachment->getFilename()." ...");
							if ($attachment->saveToDirectory($pathForAttachment[0]))
							{ // saving attachment was successfully
								$log->addDebug("... ".$attachment->getFilename()." successfully saved");
								$successfullySavedAttachments[] = $attachment->getFilename();
							}
							else
							{ // attachment couldn't be saved, log it and set an response mail
								$log->addError("... ".$attachment->getFilename()." couldn't be saved");
								$unsuccessfullySavedAttachments[] = $attachment->getFilename();
							}
						}

						if (count($unsuccessfullySavedAttachments) > 0)
						{ // not all attachments successfully saved
							$subject = tr("DiLoc|Sync: Unsuccessfully saved attachments");
							$text = tr("The following attachments were saved successfully in the directory \"%1\":\n- %2\nBut unfortunately we couldn't save the following attachments:\n- %3")
								->arg(str_replace(epfUtil::formatPath($customer->getDilocSyncRoot()),"",$pathForAttachment[0]))
								->arg(implode("\n- ",$successfullySavedAttachments))
								->arg(implode("\n- ",$unsuccessfullySavedAttachments));
						}
						else
						{ // all attachments successfully saved
							$subject = tr("DiLoc|Sync: Successfully saved attachments");
							$text = tr("The following attachments were saved successfully in the directory \"%1\":\n- %2")
								->arg(str_replace(epfUtil::formatPath($customer->getDilocSyncRoot()),"",$pathForAttachment[0]))
								->arg(implode("\n- ",$successfullySavedAttachments));
						}
						// send response mail
						sendResponseEmail($to,$from,$subject,$text,$log);
					}
					else $log->addInfo("No attachment attached to this message");

				}
				else if (count($pathForAttachment) > 1)
				{ // more then one path found
					$log->addError("More than one directory found with the name:\"".$message->getSubject()."\". The following directories where found: ".implode(", ",$pathForAttachment));

					array_walk($pathForAttachment,"stripOffFromPath",epfUtil::formatPath($customer->getDilocSyncRoot()));

					$text = tr("We found more than one directory with the name \"%1\" under the configured directory \"%2\".\nThe following directories where found:\n- %3")
						->arg($message->getSubject())
						->arg(str_replace(epfUtil::formatPath($customer->getDilocSyncRoot()),"",$pathForAttachment[0]))
						->arg(implode("\n- ",$pathForAttachment));
					sendResponseEmail($to, $from,tr("DiLoc|Sync: Unsuccessfully saved attachments"),$text,$log);
				}
				else if (!$message->getSubject())
				{ // no path found for saving attachments, log it and send an response mail
					$log->addError("No directory entered in the subject of the message");
					$text = tr("There were no directory entered in the subject of this message. Please send it again and enter the name of the directory in which the files should be saved.");
					sendResponseEmail($to, $from,tr("DiLoc|Sync: Unsuccessfully saved attachments"),$text,$log);
				}
				else
				{ // no path found for saving attachments, log it and send an response mail
					$log->addError("No path found for a directory with the name:\"".$message->getSubject()."\" under the configured directory:\"$directory\"");
					$text = tr("We couldn't found any directory with the name \"%1\" under the configured directory \"%2\". Be sure that you spell the directory in the email subject correctly.")
						->arg($message->getSubject())
						->arg($directory);
					sendResponseEmail($to, $from,tr("DiLoc|Sync: Unsuccessfully saved attachments"),$text,$log);
				}

				// delete message
				$log->addDebug("Delete email:\"".$message->getSubject()."\"");
				$message->delete();
			}

			// expunge all messages which have the delete flag set
			$mailServer->expunge();
		}
		catch (Exception $e)
		{ // Got unexpected exception
			$log->addError("Got unexpected exception:\"".$e->getMessage()."\"");
		}
	}
}