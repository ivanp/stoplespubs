<?php

class ProcessObject
{
	public $pid;
	public $socket;
	
}

class DaemonCommand extends CConsoleCommand
{
	private $user_id;
	/**
	 * @var User
	 */
	private $user;
	/**
	 * @var Mailbox
	 */
	private $mailbox;
	
	const ManagerMaxProcess=100;
	
	// In seconds
	const ImapPoolEvery=60;
	const Pop3PoolEvery=60;
	const ReconnectSpare=5;
	
	const IntervalSpare=5;
	private $intervals=array(60,120,180,240,300);
	private $current_interval=60;
	
	private $socket;
	
	const MaxProcessAtATime=1000;
	
	/**
	 * @var EProcessManager
	 */
	private $manager;
	
	public function processCheckUsers($users)
	{
		foreach($users as $user)
		{
			if($user instanceof User);
			$process=$this->manager->getProcess($user->id);
			if(!($process instanceof EProcess))
			{
				// create new process
				$process=new EProcess();
			}
			$process->checkHeartBeat();
		}
	}
	
	public function actionindex()
	{
		// Use direct PDO connection to save resources
		$connection=Yii::app()->db;
		
		$manager=EProcessManager::getInstance();
		register_tick_function(array($manager,'ping'));
		declare(ticks=1);
		
		$db_err_count=0;
		
		$prev_user_ids=array();
		// Main loop
		while(true)
		{
			try
			{
				$t_start=time();
				if(!isset($command))
				{
					$command=$connection->createCommand('SELECT t1.id FROM user t1 JOIN mailbox t2 ON t1.id = t2.user_id WHERE t1.activeto > :now');
					$command->bindParam(':now',$t_start,PDO::PARAM_INT);
				}
				$user_ids=$command->queryAll(false);
				$current_user_ids=array();
				if (is_array($user_ids))
				{
					foreach($user_ids as $row)
					{
						$user_id=$row[0];
						if(isset($prev_user_ids[$user_id]))
						{
							// Process still exists, carry on
							unset($prev_user_ids[$user_id]);
						}
						else // Add new process
						{
							// Close connection before creating any child to prevent duplicate connections
							if($connection->getActive())
								$connection->setActive(false);
							$manager->add($user_id, array($this,'runUser'), array($user_id));
						}
						$current_user_ids[$user_id]=$user_id;
					}
				}

				// Deleted/disabled users
				if(count($prev_user_ids))
				{
					foreach($prev_user_ids as $user_id)
						$manager->kill($user_id);
				}

				$prev_user_ids=$current_user_ids;

				$sleep_until=strtotime('+'.$this->current_interval.' second', $t_start);
				if ($sleep_until>time()+self::IntervalSpare) {
					printf("Sleeping for %d seconds\n", $sleep_until-time());
					while(time()<$sleep_until) {
						//sleep(5);
						@time_sleep_until(time()+5);
					}
					//@time_sleep_until($sleep_until);
					$this->_decIntervals();
				} else {
					echo "Cannot sleep, we are running out of time\n";
					$this->_incIntervals();
				}
			
			} 
			catch (CDbException $e)
			{
				echo sprintf("Got database exception '%s': %s\n", get_class($e), $e->getMessage());
				$connection->setActive(false);
				echo "Sleeping for 5 seconds\n";
				@time_sleep_until(time()+5);
				$connection->setActive(true);
				unset($command);
			}
		}
	}
	
	private function _incIntervals()
	{
		$max_interval=end($this->intervals);
		// already at max?
		if ($this->current_interval==$max_interval)
			return;
		$key=array_search($this->current_interval,$this->intervals);
		if($key===false) //something's wrong :(
		{
			// set to max interval
			$this->current_interval=$max_interval;
			return;
		}
		$this->current_interval=$this->intervals[++$key];
	}
	
	private function _decIntervals()
	{
		$min_interval=reset($this->intervals);
		if($this->current_interval==$min_interval)
			return;
		$key=array_search($this->current_interval,$this->intervals);
		if($key===false)
		{
			$this->current_interval=$min_interval;
			return;
		}
		$this->current_interval=$this->intervals[--$key];
	}
	
	public function runUser($user_id)
	{
		Yii::app()->db->setActive(false);
		Yii::app()->db->setPersistent(true);
		Yii::app()->db->setActive(true);
		
		$this->user_id=$user_id;
		$this->refreshMailbox();
		
		$this->mailbox->pid = posix_getpid();
		$this->mailbox->save();
		echo sprintf("Opening mailbox [%s]\n", $this->user->email);
		
//		if(!$this->mailbox->active)
//		{
//			echo "Mailbox inactive\n";
//			return;
//		}
		
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
		$this->user=User::model()->findByPk($this->user_id);
		if(!($this->user instanceof User))
			throw new CHttpException(404, "User not found");
		$this->mailbox=$this->user->mailbox;
		if (!($this->mailbox instanceof Mailbox))
			throw new CHttpException(404, "Mailbox not found");
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
				'password'=>$this->user->decrypt(),
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
						$delete_nums[$num]=$msg;
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
			if(count($delete_nums))
			{
				foreach ($delete_nums as $num => $msg) 
				{
					$this->deleteMessage($msg);
					echo sprintf("Removing message %s [%s]\n", $num, $msg->subject);
					$mail->removeMessage($num);
				}
			}
				
			// Close connection immediately
			$mail->close();
			// "Touch" existing emails
			$db=Yii::app()->db;
			$cmd=$db->createCommand('INSERT INTO '.MessagePop3::model()->tableName().' (mailbox_id, message_id, last_touch) VALUES (:mailbox_id, :message_id, :last_touch)');
			$cmd->bindValue(':mailbox_id', $this->mailbox->id);
			$cmd->bindValue(':last_touch', time());
			$cmd->bindParam(':message_id', $msg_id);
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
			$sleep_until=strtotime('+'.self::Pop3PoolEvery.' second', $t_start);
			if ($sleep_until>time()+self::ReconnectSpare) {
				printf("Sleeping for %d seconds\n", $sleep_until-time());
//				@time_sleep_until($sleep_until);
				while(time()<$sleep_until) 
				{
					@time_sleep_until(time()+2);
				}
			} else {
				echo "Cannot sleep, we are running out of time\n";
			}
			
//			$sleep_until=strtotime('+'.$how_long.' second', $start);
//			if ($sleep_until<=time()+self::ReconnectSpare)
//				$sleep_until=time()+self::Pop3PoolEvery;
//			echo sprintf("Sleeping until %s\n", date('Y-m-d H:i:s', $sleep_until));
//			time_sleep_until($sleep_until);
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
			'password'=>$this->user->decrypt(),
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
//						echo sprintf("Message [%d] already processed, skipped\n", $id);
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
								$delete_nums[$num]=$msg;
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
						foreach ($delete_nums as $num => $msg) 
						{
							$this->deleteMessage($msg);
							echo sprintf("Moving message %s [%s] to %s\n", $num, $msg->subject, $this->mailbox->imap_move_folder);
							$mail->moveMessage($num, $this->mailbox->imap_move_folder);
//							$mail->moveMsgExt($num, $this->mailbox->imap_move_folder);
						}
						break;
					case Mailbox::ImapActionMark :
					case Mailbox::ImapActionDelete :
						foreach ($delete_nums as $num => $msg) 
						{
							$this->deleteMessage($msg);
							echo sprintf("Marking message %s [%s]\n", $num, $msg->subject);
							$mail->removeMessage($num);
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
//				@time_sleep_until($sleep_until);
				while(time()<$sleep_until) {
					@time_sleep_until(time()+2);
				}
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
	
	private function deleteMessage(Zend_Mail_Message $message)
	{
		static $cmd;
		if(!isset($cmd))
		{
			$cmd=Yii::app()->db->createCommand('INSERT INTO '.MailDelete::model()->tableName().' (user_id, time, `from`) VALUES (:user_id, :time, :from)');
		}
		$cmd->bindValue(':user_id',$this->user_id,PDO::PARAM_INT);
		$cmd->bindValue(':time',time(),PDO::PARAM_INT);
		$cmd->bindValue(':from',$message->from,PDO::PARAM_STR);
		$cmd->execute();
	}
}