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
			
			
			echo "IP-Address";
			
			
		}
		
		
	}
?>
