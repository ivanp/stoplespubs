<?php

class ProviderCommand extends CConsoleCommand
{
	const MozillaIspDbUrl='http://ispdb.mozillamessaging.com/';
	const MozillaIspDetailUrl='http://ispdb.mozillamessaging.com/details/';
	const MozillaIspXmlUrl='http://ispdb.mozillamessaging.com/export_xml/v1.1/';
	
	const PlaceHolderEmailAddress='%EMAILADDRESS%';
	const PlaceHolderEmailLocalPart='%EMAILLOCALPART%';
	
	const Retries=10;
	
	protected $placeholders=array(self::PlaceHolderEmailAddress,self::PlaceHolderEmailLocalPart);
	
	public function actionIndex()
	{		
		Yii::import('application.vendors.phpQuery.phpQuery');
		$xmlcache=array();
		
		$client=new Zend_Http_Client();
		$client->setConfig(array('timeout'=>60));
		$client->setUri(self::MozillaIspDbUrl);
		echo sprintf("Opening %s\n", self::MozillaIspDbUrl);
		//$response=$client->request('GET');
		//$body=$response->getBody();
		$body=file_get_contents('file:///home/ivan/ispdb_hp.txt');
		//echo strlen($body);exit;
		$doc=phpQuery::newDocumentHTML($body);
		$items=$doc->find('#foundsome > ul > li.domain');
		echo sprintf("Found %d items\n", $items->count());
		foreach ($items as $li) {
			$li=pq($li);
			$a=$li->find('a');
			$domain=$a->text();
			$href=$a->attr('href');
			list(,$id)=explode('/',$href);
			echo sprintf("Found domain [%s] ID [%d]\n", $domain, $id);
			if (empty($domain)) {
				echo "Domain is empty, skipping\n";
				continue;
			} elseif (preg_match('#\(dup\)#i', $domain)) {
				echo "Domain is duplicate, skipping\n";
				continue;
			}
			$provider=MailProvider::model()->findByPk($id);
			if ($provider instanceof MailProvider)
			{
				echo "Provider already in DB\n";
			}
			else
			{
				echo "Creating new provider\n";
				$provider=new MailProvider();
				$provider->id=$id;
			}
			
			// Get XML
			if (isset($xmlcache[$id]))
			{
				$xml=$xmlcache[$id];
			}
			else
			{
				$url=self::MozillaIspXmlUrl.$id;
				echo "Fetching XML: ".$url."\n";
				$retry=0;
				while (1)
				{
					try 
					{
						$client->setUri(self::MozillaIspXmlUrl.$id);
						$body=$client->request('GET')->getBody();
					}
					catch (Zend_Http_Client_Exception $e)
					{
						if ($retry++<self::Retries)
							continue;
						echo sprintf("Retried for %d times, skipping\n", self::Retries);
						continue 2;
					}
					break;
				}

				echo sprintf(" %d bytes\n", strlen($body));
				$xml=phpQuery::newDocumentXML($body);
				$xmlcache[$id]=$xml;
			}
				
			$xml=$xml->find('emailProvider');
			$name=$xml->find('displayName')->text();
			$server=$xml->find('incomingServer');
			$type=$server->attr('type');
			if ($type!='pop3' && $type!='imap')
			{
				echo sprintf("Using unsupported server type (%s), skipping\n", $type);
				if (!$provider->getIsNewRecord()) 
					$provider->delete();
				continue;
			}
			$hostname=$server->find('hostname')->text();
			$port=$server->find('port')->text();
			$socketType=$server->find('socketType')->text();
			$username=$server->find('username')->text();
			$auth=$server->find('authentication')->text();
			if ($auth!='plain')  
			{
				echo sprintf("Using unsupported authentication (%s), skipping\n", $auth);
				if (!$provider->getIsNewRecord()) 
					$provider->delete();
				continue;
			}
			if (!in_array($username, $this->placeholders))
			{
				echo sprintf("Using unsupported placeholders (%s), skipping\n", $username);
				if (!$provider->getIsNewRecord()) 
					$provider->delete();
				continue;
			}
			
			// Check attributes
			if ($provider->type!=$type)
			{
				echo "Type=$type\n";
				$provider->type=$type;
			}
			if ($provider->name!=$name) 
			{
				echo "Name=$name\n";
				$provider->name=$name;
			}
			if ($provider->hostname!=$hostname)
			{
				echo "Hostname=$hostname\n";
				$provider->hostname=$hostname;
			}
			if ($provider->port!=$port)
			{
				echo "Port=$port\n";
				$provider->port=$port;
			}
			if ($provider->username!=$username)
			{
				echo "Username=$username\n";
				$provider->username=$username;
			}
			switch($socketType) {
				default:
				case 'plain':
					$ssl='none';
					break;
				case 'SSL':
					$ssl='ssl';
					break;
				case 'STARTTLS':
					$ssl='tls';
					break;
			}
			if ($provider->ssl!=$ssl)
			{
				echo "SSL=$ssl\n";
				$provider->ssl=$ssl;
			}
			
			$provider->xml=$body;
			$provider->updated=time();
			if ($provider->save())
			{
				echo "Provider saved successfully\n";
			}
			else
			{
				die("ERROR SAVING PROVIDER");
			}
			
			$mail_domain=MailDomain::model()->findByPk($domain);
			if ($mail_domain===null)
			{
				$mail_domain=new MailDomain();
				$mail_domain->name=$domain;
				$mail_domain->mail_provider_id=$id;
				if ($mail_domain->save())
				{
					echo sprintf("Domain %s saved succesfully\n", $domain);
				}
				
			}
			

		}
	}
}