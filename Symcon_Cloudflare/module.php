<?php

    class Cloudflare extends IPSModule
    {
        public function Create()
        {
            //Never delete this line!
            parent::Create();

            $this->RegisterPropertyString('APIKey', '');
            $this->RegisterPropertyString('MailAddress', '');
            $this->RegisterPropertyString('Domain', '');
            $this->RegisterPropertyString('RecordName', '');
            $this->RegisterPropertyInteger('RecordType', 0);
            $this->RegisterPropertyBoolean('EnableProxy', false);
            $this->RegisterPropertyInteger('TTL', 120);
            $this->RegisterPropertyInteger('CheckIPInterval', 60);

            $this->RegisterVariableString('IP', 'current IP');
			
            $this->RegisterTimer('UpdateRecord', $this->ReadPropertyInteger('CheckIPInterval') * 1000, 'CF_AutomaticUpdateRecord($_IPS[\'TARGET\']);');
        }

        public function ApplyChanges()
        {
            //Never delete this line!
            parent::ApplyChanges();
            $this->SetTimerInterval('UpdateRecord', $this->ReadPropertyInteger('CheckIPInterval') * 1000);
			$this->Maintain();
        }
		private function Maintain()
		{
			$this->MaintainVariable('IP', $this->Translate('current IP'), 3, '', 100,true);
		}

        public function GetIpAddress()
        {
            try {
                $urlRequest = 'http://ip-api.com/json/?fields=query,status';
                $handle = file_get_contents($urlRequest);
                if ($handle == false) {
                    return '';
                }
				$this->SendDebug('GetIpAddress()', 'Response: '.$handle, 0);
                $obj = json_decode($handle, true);
                if ($obj['status'] == 'success') {
					$this->SendDebug('GetIpAddress()', 'Response query: '.$obj['query'], 0);
                    return  $obj['query'];
                } else {
                    return '';
                }
            } catch (Exception $e) {
				$this->SendDebug('GetIpAddress()', 'Exception: '.$e->getMessage(), 0);
                return '';
            }
        }

        public function GetIpAddressV6()
        {
            try {
                $urlRequest = 'http://v6.ipv6-test.com/api/myip.php?json';
                $handle = file_get_contents($urlRequest);
                if ($handle == false) {
                    return '';
                }
				$this->SendDebug('GetIpAddressV6()', 'Response: '.$handle, 0);
                $obj = json_decode($handle, true);
                return  $obj['address'];
            } catch (Exception $e) {
                $this->SendDebug('GetIpAddress()', 'Exception: '.$e->getMessage(), 0);
                return '';
            }
        }

        public function AutomaticUpdateRecord()
        {
            $this->UpdateRecord(false);
        }

        

        public function UpdateRecord(bool $debug)
        {
            $ids = $this->Authenticate($debug);

			if($ids == null)
			{
				$this->SendDebug('UpdateRecord()', 'Zoneid/Record-ID is null' . $ip, 0);
				return false; 
			}

            $zoneId = $ids['zoneId'];
            $recordId = $ids['recordId'];
            $dnsRecord = $this->ReadPropertyString('RecordName');
            $zone = $this->ReadPropertyString('Domain');

            if ($this->ReadPropertyBoolean('EnableProxy')) {// Aktiviert den Proxy-Service von Cloudflare für diesen DNS-Eintrag
                $enableProxy = 'true';
            } else {
                $enableProxy = 'false';
            }

            $ttl = $this->ReadPropertyInteger('TTL');                 // TTl des Eintrags in Sekunden (mindestens 120)

            if ($this->ReadPropertyInteger('RecordType') == 1) {
                $type = 'AAAA';
                $ip = $this->GetIpAddressV6();
            } else {
                $type = 'A';
                $ip = $this->GetIpAddress();
            }

            if ($ip == '') {
                die;
            }

            $oldIp = $this->GetValue('IP');
            $this->SetValue('IP', $ip);
            if ($ip == $oldIp) {
                if (!$debug) {
                    return;
                }
            } else {
				
				$this->SendDebug('UpdateRecord()', 'IP-Address Changed: ' . $oldIp . ' => ' . $ip, 0);
            }



            $curl = curl_init();
            curl_setopt_array($curl, [
            CURLOPT_URL            => 'https://api.cloudflare.com/client/v4/zones/' . $zoneId . '/dns_records/' . $recordId,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'PUT',
            CURLOPT_POSTFIELDS     => "{\n\"id\":\"" . $recordId . "\",\n\"type\":\"" . $type . "\",\n\"name\":\"" . $dnsRecord . "\",\n\"content\":\"" . $ip . "\",\n\"proxiable\":true,\n\"proxied\":" . $enableProxy . ",\n\"ttl\":" . $ttl . ",\n\"locked\":false,\n\"zone_id\":\"" . $zoneId . "\",\n\"zone_name\":\"" . $zone . "\"\n}",

            CURLOPT_HTTPHEADER => [
                'cache-control: no-cache',
                'content-type: application/json',
                'x-auth-email: ' . $this->ReadPropertyString('MailAddress'),
                'x-auth-key: ' . $this->ReadPropertyString('APIKey')
                ],
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                echo 'cURL Error #:' . $err;
                die;
            }
            $obj = json_decode($response);

            if ($obj->{'success'} == 1) {
                if ($debug) {
                    echo 'DNS-Update ' . $dnsRecord . ' => ' . $ip . ' successfull';
                }
				$this->SendDebug('UpdateRecord()', 'Update ' . $dnsRecord . ' => ' . $ip . ' successfull', 0);
                $this->SetStatus(102);
            } else {
                $this->SetStatus(202);
                echo 'DNS Update failed' . "\n\r";
				$this->SendDebug('UpdateRecord()', 'DNS Update failed', 0);
            }
        }

        public function AuthenticateButton()
        {
            $this->Authenticate(true);
        }

        public function UpdateRecordButton()
        {
            $this->UpdateRecord(true);
        }

        public function Authenticate(bool $debug)
        {
            $curl = curl_init();

            curl_setopt_array($curl, [
        CURLOPT_URL            => 'https://api.cloudflare.com/client/v4/zones',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => '',
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => 'GET',
        CURLOPT_HTTPHEADER     => [
        'cache-control: no-cache',
        'x-auth-email: ' . $this->ReadPropertyString('MailAddress'),
        'x-auth-key: ' . $this->ReadPropertyString('APIKey')
        ],
        ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                echo 'cURL Error #:' . $err;
                die;
            }
			$this->SendDebug('Authenticate()', 'Zones Response: '.$response, 0);
		
            $obj = json_decode($response, true);
            if ($obj['success'] != 1) {
                echo 'Authentication Failed' . "\n\r";
				$this->SendDebug('Authenticate()', 'Authentication Failed', 0);
                $this->SetStatus(201);
                return;
            }

            if ($debug) {
                echo 'Authentication Successfull' . "\n\r";
				$this->SendDebug('Authenticate()', 'Authentication Successfull', 0);
            }
            $this->SetStatus(102);

            // Get Zoen ID
            $zones = ($obj['result']);
            $zoneId = '';

            foreach ($zones as $zoneResult) {
                if ($zoneResult['name'] == $this->ReadPropertyString('Domain')) {
                    $zoneId = $zoneResult['id'];
                }
            }
            if ($zoneId == '') {
                echo 'Zone (Domain) not found.' . "\n\r" . 'Available Zones:' . "\n\r";
                foreach ($zones as $zoneResult) {
                    echo $zoneResult['name'] . "\n\r";
                }
                return;
            }

            if ($debug) {
                echo 'Zone ID => ' . $zoneId . "\n\r";
				
            }

            // Get Record ID
            $curl = curl_init();

            curl_setopt_array($curl, [
        CURLOPT_URL            => 'https://api.cloudflare.com/client/v4/zones/' . $zoneId . '/dns_records?per_page=50&page=1',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => '',
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => 'GET',
        CURLOPT_HTTPHEADER     => [
        'cache-control: no-cache',
        'x-auth-email: ' . $this->ReadPropertyString('MailAddress'),
        'x-auth-key: ' . $this->ReadPropertyString('APIKey')
        ],
        ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                echo 'cURL Error #:' . $err;
                return;
            }

            $obj = json_decode($response, true);
            if ($obj['success'] != 1) {
                echo 'GetRecordID Failed' . "\n\r";
                return;
            }

			$this->SendDebug('Authenticate()', 'Records Response: '.$response, 0);

            $zones = ($obj['result']);
            $recordId = '';
            foreach ($zones as $zoneResult) {
                if ($zoneResult['name'] == $this->ReadPropertyString('RecordName')) {
                    $recordId = $zoneResult['id'];
                }
            }
            if ($recordId == '') {
                echo 'Record not found.' . "\n\r" . ' Available Records:' . "\n\r";
                foreach ($zones as $zoneResult) {
                    echo $zoneResult['name'] . "\n\r";
                }
                return;
            }
            if ($debug) {
                echo 'Record ID => ' . $recordId . "\n\r";
            }
            return  [
            'zoneId'   => $zoneId,
            'recordId' => $recordId,
        ];
        }
    }
