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
		} else {
		$obj = json_decode($response,true);
		if( $obj["success"]!=1)
		{
		echo "GetZoneId Failed";
		die;
		}
		echo "Authentication Successfull";
		 foreach($zones as $zoneResult) {
    if($zoneResult['name']==$zone)
		$zoneId = $zoneResult['id'];
}
  if ($zoneId =="")
  {
  	echo "Zone not found. Zones: </br> "   ;
			 foreach($zones as $zoneResult) {
 		echo $zoneResult['name'] ." </br>" ;        }
  	die;
  }

		
		}
	}
	}
?>
