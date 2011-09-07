<?php

class DaemonCommand extends CConsoleCommand
{
	private $mailbox_id;
	/**
	 * @var Mailbox
	 */
	private $mailbox;
	
	// In seconds
	const ImapPoolEvery=10;
	const Pop3PoolEvery=10;
	const ReconnectSpare=5;
	
	const MaxProcessAtATime=100;
	
	public function actionIndex($mailbox_id)
	{
		$this->mailbox_id=$mailbox_id;
		$this->refreshMailbox();
		
		$this->mailbox->pid = posix_getpid();
		$this->mailbox->save();
		echo sprintf("Opening mailbox [%s]\n", $this->mailbox->email);
		
		switch ($this->mailbox->type)
		{
			case Mailbox::TypePop3:
				$this->startPop();
				break;
			case Mailbox::TypeImap:
				$this->startImap();
				break;
			default:
				throw new Zend_Exception('Mailbox type not supported: '.strtoupper($this->mailbox->type));
		}
			
	}
	
	private function refreshMailbox()
	{
		$this->mailbox=Mailbox::model()->findByPk($this->mailbox_id);
		if (!($this->mailbox instanceof Mailbox))
			throw new CHttpException(404, "Mailbox not found");
	}
	
	private function deleteMessage(Zend_Mail_Storage_Abstract $storage, Zend_Mail_Message $mail) {
		static $buffer;
	}
	
	private function startPop()
	{
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
		// Main loop
		while (true) {
			// Prepare banned headers in array
			$banned_headers=Header::getAllHeaders();
			echo "Loop begins, connecting to server\n";
			// Store to-be-delete email numbers here
			$delete_nums=array();
			$update_ids=array();
			// Connect to POP server
			$mail=new EZend_Mail_Storage_Pop3(array(
				'host'=>$this->mailbox->host,
				'user'=>$this->mailbox->username,
				'password'=>$this->mailbox->user->decrypt(),
				'ssl'=>$ssl
			));
			// Mark start time
			$t_start=time();
			echo sprintf("Server connected. Found %d emails\n", $mail->countMessages());
			// Get list of emails with unique IDs
			$list=$mail->getUniqueId(null);
			$count=0;					
			// Loop thru emails
			foreach ($list as $id)
//			foreach ($mail as $num => $msg)
			{
//				$id=$mail->getUniqueId($num);
				// Is it recorded in DB?
				$message=MessagePop3::model()->find('mailbox_id=:mailbox_id and message_id=:message_id',array(':mailbox_id'=>$this->mailbox->id,':message_id'=>$id));
				if ($message instanceof MessagePop3)
				{
					echo sprintf("Message [%s] already processed, skipped\n", $message->message_id);
					$update_ids[]=$id;
					unset($message);
					continue; // already processed, skip it
				}
				$num=$mail->getNumberByUniqueId($id);
				$msg=$mail->getMessage($num);
				if ($msg instanceof Zend_Mail_Message)
				{
					$msg_headers=array_keys($msg->getHeaders());
					$intersects=array_intersect($banned_headers, $msg_headers);
					if (count($intersects))
					{
						echo sprintf("Message [%s] have banned headers (%s), will be deleted\n", $msg->subject, join(',', $intersects));
						// Mark email to be deleted
						$delete_nums[$num]=$msg->subject;
					}
					else
					{
						echo sprintf("Message [%s] doesn't have banned headers, will be recorded in DB\n", $msg->subject);
						$update_ids[]=$id;
					}
					unset($msg_headers);
					unset($intersects);
				}
				if (++$count>=self::MaxProcessAtATime)
				{
					break;
				}
			}
			// Delete emails
			foreach ($delete_nums as $num => $subject) {
				echo sprintf("Removing message %s [%s]\n", $num, $subject);
				$mail->removeMessage($num);
			}
			// Close connection
			$mail->close();
			// "Touch" existing emails
			$db=Yii::app()->db;
			$cmd=$db->createCommand('REPLACE INTO '.MessagePop3::model()->tableName().' (mailbox_id, message_id, last_touch) VALUES (:mailbox_id, :message_id, :last_touch)');
			$cmd->bindValue(':mailbox_id', $this->mailbox->id);
			$cmd->bindParam(':message_id', $msg_id);
			$cmd->bindValue(':last_touch', time());
			echo "Updating last touch\n";
			$t1=microtime(true);
			$trans=$db->beginTransaction();
			foreach ($update_ids as $msg_id)
			{
				$cmd->execute();
			}
			$trans->commit();
			$t2=microtime(true);
			echo sprintf("%d transactions committed in %.5f seconds\n", count($update_ids), $t2-$t1);
			unset($mail);
			
			// Sleep a little while
			$sleep_until=strtotime('+'.$how_long.' second', $start);
			if ($sleep_until<=time()+self::ReconnectSpare)
				$sleep_until=time()+self::Pop3PoolEvery;
			echo sprintf("Sleeping until %s\n", date('Y-m-d H:i:s', $sleep_until));
			time_sleep_until($sleep_until);
		}
	}
	
	private function startImap()
	{
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
		$mail=new EZend_Mail_Storage_Imap(array(
			'host'=>$this->mailbox->host,
			'user'=>$this->mailbox->username,
			'password'=>$this->mailbox->user->password,
			'ssl'=>$ssl
		));
		
		// Checking folders
//		$folders = new RecursiveIteratorIterator($mail->getFolders(), RecursiveIteratorIterator::SELF_FIRST);
//		echo "Folders: \n";
//		foreach ($folders as $localName => $folder) {
//			if ($folder->isSelectable()) {
//				echo sprintf("- %s (%s)\n", $localName, $folder);
//			}
//		}
//		exit;
		//$mail->getServerCapability();
		
//		var_dump($mail->getCurrentFolder());exit;
		
//		var_dump($ids);exit;
		
		$check=true;
		// Created/Updated IDs here
		$update_ids=array();
		// Deleted Nums here
		$delete_nums=array();

		// Main loop
		while (true) {
			// Timer start
			$t_start=time();
			// Prepare banned headers in array
			$banned_headers=Header::getAllHeaders();
			
			// Go through all the emails on the first time
			if ($check) 
			{
				// Get list of current email
				$list=$mail->getUniqueId(null);
				echo sprintf("Total mails = %d\n", count($list));
				//for (reset($list);list($num,$id)=each($list);array_shift($list))
				$count=0;
				foreach ($list as $num=>$id)
				{
					unset($list[$num]);
					$id=(int)$id; // it's an integer
					if (MessageImap::model()->count('mailbox_id=:mailbox_id and message_id=:message_id',array(':mailbox_id'=>$this->mailbox->id,':message_id'=>$id)))
					{
						echo sprintf("Message [%d] already processed, skipped\n", $id);
						$update_ids[]=$id;
						continue; // already processed, skip it
					}
					// Fetch e-mail
					try 
					{
						$msg=$mail->getMessage($num);
						if ($msg instanceof Zend_Mail_Message)
						{
							$subject=isset($msg->subject) ? $msg->subject : '';
							echo sprintf("Found new email Num: [%s], ID: [%d] Subject: [%s]\n", $num, $id, $subject);
							$msg_headers=array_keys($msg->getHeaders());
							$intersects=array_intersect($banned_headers, $msg_headers);
							if (count($intersects))
							{
								echo sprintf("Message [%s] have banned headers (%s), will be deleted\n", $subject, join(',', $intersects));
								// Mark email to be deleted
								$delete_nums[$num]=$subject;
							}
							else
							{
								echo sprintf("Message [%s] doesn't have banned headers, will be recorded in DB\n", $subject);
								$update_ids[]=$id;
							}
							unset($msg_headers);
							unset($intersects);
						}
					}
					catch (Zend_Mail_Protocol_Exception $e)
					{
					}
					if (++$count>=self::MaxProcessAtATime)
					{
						break;
					}
				}
				
				if (empty($list)) {
					$check=false;
				}
			}
			
			// Delete emails
			if (count($delete_nums)) 
			{
				switch ($this->mailbox->imap_action) {
					case Mailbox::ImapActionMove :
						foreach ($delete_nums as $num => $subject) {
							echo sprintf("Moving message %s [%s] to %s\n", $num, $subject, $this->mailbox->imap_move_folder);
							//$mail->moveMessage($num, $this->mailbox->imap_move_folder);
							$mail->moveMsgExt($num, $this->mailbox->imap_move_folder);
						}
						break;
					case Mailbox::ImapActionMark :
					case Mailbox::ImapActionDelete :
						foreach ($delete_nums as $num => $subject) {
							echo sprintf("Marking message %s [%s]\n", $num, $subject);
		//					$mail->removeMessage($num);
							try {
								$mail->setFlags($num, array(Zend_Mail_Storage::FLAG_DELETED));
							} catch (Zend_Mail_Protocol_Exception $e) {
							}
						}
						break;
				}
						
				if ($this->mailbox->imap_action == Mailbox::ImapActionDelete || 
						$this->mailbox->imap_action == Mailbox::ImapActionMove) {
					echo "Expunging...\n";
					$mail->expunge();
				}
				
				$delete_nums=array();
			}
			// "Touch" existing emails
			if (count($update_ids)) 
			{
				$db=Yii::app()->db;
				$cmd=$db->createCommand('REPLACE INTO '.MessageImap::model()->tableName().' (mailbox_id, message_id, last_touch) VALUES (:mailbox_id, :message_id, :last_touch)');
				$cmd->bindValue(':mailbox_id', $this->mailbox->id, PDO::PARAM_INT);
				$cmd->bindParam(':message_id', $msg_id, PDO::PARAM_INT);
				$cmd->bindValue(':last_touch', time(), PDO::PARAM_INT);
				echo "Updating last touch\n";
				$t1=microtime(true);
				$trans=$db->beginTransaction();
				foreach ($update_ids as $msg_id)
				{
					$cmd->execute();
				}
				$trans->commit();
				$t2=microtime(true);
				echo sprintf("%d transactions committed in %.5f seconds\n", count($update_ids), $t2-$t1);
				$update_ids=array();
			}
			
			$sleep_until=strtotime('+'.self::ImapPoolEvery.' second', $t_start);
			if ($sleep_until>time()+self::ReconnectSpare) {
				printf("Sleeping for %d seconds\n", $sleep_until-time());
				@time_sleep_until($sleep_until);
			} else {
				echo "Cannot sleep, we are running out of time\n";
			}
			
			echo "Performing NOOP\n";
			$resps = $mail->noop();
			// Parse response
			if (is_array($resps) && count($resps)) {
				echo sprintf("Got response: %s\n", var_export($resps, true));
				$check=true;
			}
//			$ids=$mail->getUniqueId();
//			var_dump($response, $ids);
//			if (is_array($response)) {
//				$recent = $mail->searchRecent();
//				var_dump($recent);
//			}
		}
			
	}
}