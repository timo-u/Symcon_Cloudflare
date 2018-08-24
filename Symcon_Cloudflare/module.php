<?

	class Cloudflare extends IPSModule {
		
		public function Create()
		{
			//Never delete this line!
			parent::Create();
			
			$this->RegisterPropertyString("APIKey", "");
			$this->RegisterPropertyString("MailAddress", "");
			$this->RegisterPropertyString("Domain", "");
			$this->RegisterPropertyString("RecordName", "");
			$this->RegisterPropertyInteger("RecordType", 0);

		}

		public function ApplyChanges() {
			//Never delete this line!
			parent::ApplyChanges();
			
			
		}

		
		public function GetIpAddress() {
			
			try
			{

			$urlRequest = "http://ip-api.com/json/?fields=query,status";
			$handle = file_get_contents($urlRequest);
			$obj = json_decode($handle,true);
			if($obj['status'] == "success")
				{
				return  $obj['query'];

				}
			else
				return "";

			}
			catch (Exception $e) {
				echo 'Exception abgefangen: ',  $e->getMessage(), "\n";
				return "";
			}
			
			
			
		}
		
		public function UpdateRecord() {
		
		$ids =  $this->Authenticate();
		
		$zoneId = $ids["zoneId"];
		$recordId  = $ids["recordId"];
		$enableProxy = "false";		// Aktiviert den Proxy-Service von Cloudflare für diesen DNS-Eintrag
		$ttl = 120;                 // TTl des Eintrags in Sekunden (mindestens 120)
		$type = "A";   			// Record-Typ IPV4 => "A" IPv6 => "AAAA"
		
			$curl = curl_init();
			curl_setopt_array($curl, array(
			CURLOPT_URL => "https://api.cloudflare.com/client/v4/zones/".$zoneId."/dns_records/".$recordId ,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "PUT",
			CURLOPT_POSTFIELDS => "{\n\"id\":\"".$recordId."\",\n\"type\":\"".$type."\",\n\"name\":\"".$dnsRecord."\",\n\"content\":\"".$ip."\",\n\"proxiable\":true,\n\"proxied\":".$enableProxy.",\n\"ttl\":".$ttl.",\n\"locked\":false,\n\"zone_id\":\"".$zoneId."\",\n\"zone_name\":\"".$zone."\"\n}",

			CURLOPT_HTTPHEADER => array(
				"cache-control: no-cache",
				"content-type: application/json",
				"x-auth-email: ".$this->ReadPropertyString("MailAddress"),
				"x-auth-key: ".$this->ReadPropertyString("APIKey")
				),
			));

			$response = curl_exec($curl);
			$err = curl_error($curl);

			curl_close($curl);

			if ($err) {
				echo "cURL Error #:" . $err;
				die;
			} 
			$obj = json_decode($response);
			
			if( $obj->{'success'}==1)
			echo "Update von ".$dnsRecord." auf ".$ip. " erfolgreich";
			
			
		}
		
		public function Authenticate() {
			
		
		$curl = curl_init();
																		
		curl_setopt_array($curl, array(
		CURLOPT_URL => "https://api.cloudflare.com/client/v4/zones",
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "GET",
		CURLOPT_HTTPHEADER => array(
		"cache-control: no-cache",
		"x-auth-email: ".$this->ReadPropertyString("MailAddress"),
		"x-auth-key: ".$this->ReadPropertyString("APIKey")
		),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);
		
		if ($err) {
		echo "cURL Error #:" . $err;
		die;
		}
		
		$obj = json_decode($response,true);
		if( $obj["success"]!=1)
		{
		echo "Authentication Failed"."\n\r";
		$this->SetStatus(201);
		return;
		}
		
		echo "Authentication Successfull"."\n\r";
		$this->SetStatus(102);
	
		// Get Zoen ID
		$zones =($obj['result']);
		$zoneId ="";
		
		foreach($zones as $zoneResult) {
		if($zoneResult['name']==$this->ReadPropertyString("Domain"))
		$zoneId = $zoneResult['id'];
		}
		if ($zoneId =="")
		{
		echo "Zone (Domain) not found."."\n\r". "Available Zones:"."\n\r"   ;
			 foreach($zones as $zoneResult) {
 		echo $zoneResult['name'] ."\n\r" ;        }
		return;
		}
		
		echo "Zone ID => ". $zoneId."\n\r";
		
		
		// Get Record ID
		$curl = curl_init();

		curl_setopt_array($curl, array(
		CURLOPT_URL => "https://api.cloudflare.com/client/v4/zones/".$zoneId."/dns_records",
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "GET",
		CURLOPT_HTTPHEADER => array(
		"cache-control: no-cache",
		"x-auth-email: ".$this->ReadPropertyString("MailAddress"),
		"x-auth-key: ".$this->ReadPropertyString("APIKey")
		),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		echo "cURL Error #:" . $err;
		die;
		} 

		$obj = json_decode($response,true);
		if( $obj["success"]!=1)
		{
		echo "GetRecordID Failed"."\n\r";
		die;
		}
		
		$zones =($obj['result']);
		$recordId ="";
		foreach($zones as $zoneResult) {
			if($zoneResult['name'] == $this->ReadPropertyString("RecordName"))
				$recordId = $zoneResult['id'];
			}
		if ($recordId =="")    {
		echo "Record not found."."\n\r"." Available Records:"."\n\r"   ;
			 foreach($zones as $zoneResult) {
		echo $zoneResult['name']."\n\r"      ;}
		die;
		}
		echo "Record ID => ". $recordId."\n\r";
		return  [
			"zoneId" => $zoneId,
			"recordId" => $recordId,
		];
	}
	}
?>
