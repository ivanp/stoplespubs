<?php

class DaemonCommand extends CConsoleCommand
{
	/**
	 * @var Mailbox
	 */
	private $mailbox;
	
	public function actionIndex($mailbox_id)
	{
		$this->mailbox=Mailbox::model()->findByPk($mailbox_id);
		if (!($this->mailbox instanceof Mailbox))
			throw new CHttpException(404, "Mailbox not found");
		$this->mailbox->pid = posix_getpid();
		
		if ($this->mailbox->type == MailBox::TypePop3)
		{
			$this->startPop();
		}
		else
		{
			$this->startImap();
		}
			
	}
	
	private function startPop()
	{
		
	}
	
	private function startImap()
	{
		try {
			switch ($this->mailbox->ssl) {
				case 'ssl':
					$ssl='SSL';
					break;
				case 'tls':
					$ssl='TLS';
					break;
				default:
					$ssl=false;
			}
			$mail=new EZend_Mail_Storage_Imap(
				array(
					'host'=>$this->mailbox->host,
					'user'=>$this->mailbox->username,
					'password'=>$this->mailbox->password,
					'ssl'=>$ssl
				)
			);

			while (true) {
				// Main loop
				$recent = $mail->searchRecent();
				echo sprintf("Total mails = %d\n", count($recent));
				foreach ($recent as $messageNum => $message) {
					echo sprintf("Found new email Num: [%s], ID: [%s] Subject: [%s]\n", $messageNum, $mail->getUniqueId($messageNum), $message->subject);
				}
				sleep(10);
				$mail->noop();
			}
			
		} catch (Exception $e) {
			$this->addLog('Unhandled exception: '.$e->getMessage());
		}
	}
}