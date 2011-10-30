<?php

class EProcess
{
	const TypeParent='parent';
	const TypeChild='child';
	
	const MessageSeparator="\n";
	const CommandPing="ping";
	const ResponseOk="ok";
	
	private $id;
	private $pid;
	private $type=self::TypeParent;
	
	private $ping_handler;
	
	/**
	 * Function to call in this process
	 * 
	 * @var callback
	 */
	private $callback;
	/**
	 * Arguments to callback
	 * 
	 * @var array
	 */
	private $args=array();
	
	/**
	 *
	 * @var resource Stores socket resource to connect to each other (parent<->client)
	 */
	private $socket;
	
	/**
	 * Last time check
	 * 
	 * int type 
	 */
	private $last_ping=0;
	private $last_response=0;
	
	
	public function __construct($id, $callback, $args=array()) 
	{
		$this->id=$id;
		$this->callback=$callback;
		$this->args=$args;
		$this->ping_handler=array($this,'ping');
	}
	
	protected function reset()
	{
		$this->last_ping=0;
		$this->last_response=0;
		$this->socket=null;
	}
	
	public function getId()
	{
		return $this->id;
	}
	
	public function getPid()
	{
		return $this->pid;
	}
	
	public function getSocket()
	{
		return $this->socket;
	}
	
	public function run()
	{
		if (isset($this->pid))
			@socket_close($this->socket);
		$this->reset();
		if (socket_create_pair(AF_UNIX, SOCK_STREAM, 0, $ary) === false) 
			throw new EProcessException(socket_strerror(socket_last_error()));
		$this->printf('Preparing to fork');
		$this->pid=pcntl_fork();
		$this->last_response=time();
		if($this->pid==-1)
		{
			throw new EProcessException('Cannot fork');
		}
		elseif ($this->pid)
		{
			$this->socket=$ary[0];
			socket_close($ary[1]);
			socket_set_nonblock($this->socket);
		}
		else 
		{
			// We don't need this anymore
			@unregister_tick_function(array(EProcessManager::getInstance(), 'ping'));
			$this->pid=posix_getpid();
			$this->type=self::TypeChild;
			$this->socket=$ary[1];
			socket_close($ary[0]);
			socket_set_nonblock($this->socket);
			register_tick_function($this->ping_handler);
			declare(ticks=1);
			try
			{
				call_user_func_array($this->callback, $this->args);
			}
			catch (Exception $e)
			{
				$this->printf("Unhandled exception '%s': %s",get_class($e),$e->getMessage());
			}
			unregister_tick_function($this->ping_handler);
			Yii::app()->end(); // end process
		}
	}
	
	public function ping()
	{
		$this->receiveAll();
		$now=time();
		if($this->type===self::TypeParent)
		{
			$this->printf('Sending ping to child');
			$this->send(self::CommandPing);
			$this->last_ping=$now;
			if(($this->last_response > 0) && (($now-$this->last_response) > EProcessManager::ParentPingTimeout))
			{
				return false;
			}
		}
		elseif($this->type===self::TypeChild)
		{
			if(($now-$this->last_ping) > EProcessManager::PingInterval)
			{
				$this->printf('Checking last ping');
				if(($this->last_response > 0) && (($now-$this->last_response) > EProcessManager::ChildPingTimeout))
				{
					$this->kill(); //suicidal
				}
				$this->last_ping=$now;
			}
		}
		return true;
	}
	
	protected function send($message)
	{
		@socket_write($this->socket,$message.self::MessageSeparator);
	}
	
	protected function receiveAll()
	{
		$raw=$this->socket_read_buffer();
		if(empty($raw))
			return;
		$messages=explode(self::MessageSeparator,$raw);
		if(is_array($messages))
		{
			foreach($messages as $msg)
			{
				$msg=trim($msg);
				if($this->type===self::TypeParent)
					$this->onParentMsg($msg);
				else
					$this->onChildMsg($msg);
			}
		}
	}
	
	protected function onParentMsg($message)
	{
		if($message===self::ResponseOk && $this->last_response!==time())
		{
			$this->printf('children responded with an ok');
			$this->last_response=time();
		}
	}
	
	protected function onChildMsg($message)
	{
		if($message===self::CommandPing && $this->last_response!==time())
		{
			$this->printf('parent sent ping, responding with an ok');
			$this->send(self::ResponseOk);
			$this->last_response=time();
		}
	}
	
	private function socket_read_buffer() 
	{
		$buf='';
		do {
			$chunk=@socket_read($this->socket, 4096);
			if(is_string($chunk) && $chunk !== '') {
				$buf.=$chunk;
				continue;
			}
			break;
		} while(true);
		return $buf;
	}
	
	public function kill()
	{
		@socket_close($this->socket);
		if($this->type===self::TypeParent)
		{
			posix_kill($this->pid,SIGKILL);
			// Preventing zombies
			pcntl_waitpid($this->pid, $status, WNOHANG);
			$this->printf('child killed');
		}
		elseif($this->type===self::TypeChild)
		{
			$this->printf('ending myself');
			Yii::app()->end();
		}
		$this->pid=null;
	}
	
	protected function printf($msg) {
		$args=func_get_args();
		$msg='[%s/%d] '.array_shift($args)."\n";
		array_unshift($args, $msg, $this->type, $this->pid);
		echo call_user_func_array('sprintf',$args);
	}
}

class EProcessManager
{
	const PingInterval=5; // try not to ping too much
	const ParentPingTimeout=60; // obviously must be greater than PingInterval
	const ChildPingTimeout=60;
	
	private $pool=array();
	
	private $pid;
	
	private $last_ping=0;
	
	static private $instance;
	
	protected function __construct()
	{
		$this->pid=posix_getpid();
		$this->last_ping=time();
	}
	
	/**
	 * @return EProcessManager
	 */
	static public function getInstance()
	{
		if(!isset(self::$instance))
			self::$instance=new EProcessManager();
		return self::$instance;
	}
	
	public function add($id,$callback,$args=array())
	{
		if(isset($this->pool[$id]))
		{
			if($this->pool[$id] instanceof EProcess)
				$this->kill($id);
		}
		$process=new EProcess($id,$callback,$args);
		$process->run();
		$this->printf("Started new process for ID#%d with PID %d", $id, $process->getPid());
		$this->pool[$id]=$process;
	}
	
	public function kill($id)
	{
		if(isset($this->pool[$id]))
		{
			$this->pool[$id]->kill();
			unset($this->pool[$id]);
		}
	}
	
	public function ping()
	{
		// Only parent process can run this!
		if($this->pid!==posix_getpid())
			return;
		// Don't ping too many!
		if((time()-$this->last_ping) < EProcessManager::PingInterval)
			return;
		// Is there any process to ping?
		if(empty($this->pool))
			return;
		$this->last_ping=time();
		foreach($this->pool as $id=>$process)
		{
			if(!$process->ping())
			{
				// Restart process
				$process->kill();
				$process->run();
			}
		}
	}
	
	public function printf($msg) {
		$args=func_get_args();
		$msg='[MANAGER/%d] '.array_shift($args)."\n";
		array_unshift($args, $msg, $this->pid);
		echo call_user_func_array('sprintf',$args);
	}
}


class EProcessException extends CException 
{
}