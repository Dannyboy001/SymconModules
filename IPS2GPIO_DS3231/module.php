<?
    // Klassendefinition
    class IPS2GPIO_DS3231 extends IPSModule 
    {
	public function Destroy() 
	{
		//Never delete this line!
		parent::Destroy();
		$this->SetTimerInterval("Messzyklus", 0);
	}
	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
		// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
 	    	$this->RegisterPropertyInteger("DeviceAddress", 104);
		$this->RegisterPropertyInteger("DeviceBus", 1);
		$this->RegisterPropertyInteger("Messzyklus", 60);
		$this->RegisterTimer("Messzyklus", 0, 'I2GDS3231_GetRTC($_IPS["TARGET"]);');
		
		$this->RegisterVariableInteger("RTC_Timestamp", "RTC Zeitstempel", "~UnixTimestamp", 10);
		$this->DisableAction("RTC_Timestamp");
		IPS_SetHidden($this->GetIDForIdent("RTC_Timestamp"), false);
		
		$this->RegisterVariableFloat("RTC_Temperature", "RTC Temperatur", "~Temperature", 20);
		$this->DisableAction("RTC_Temperature");
		IPS_SetHidden($this->GetIDForIdent("RTC_Temperature"), false);
        }
 	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 200, "icon" => "error", "caption" => "Pin wird doppelt genutzt!");
		$arrayStatus[] = array("code" => 201, "icon" => "error", "caption" => "Pin ist an diesem Raspberry Pi Modell nicht vorhanden!"); 
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "I²C-Kommunikationfehler!");
		
		$arrayElements = array(); 
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv"); 
 		
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "104 dez. / 0x68h", "value" => 104);
		
		$arrayElements[] = array("type" => "Select", "name" => "DeviceAddress", "caption" => "Device Adresse", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "Label", "label" => "I²C-Bus (Default ist 1)");
		
		$arrayOptions = array();
		$DevicePorts = array();
		$DevicePorts = unserialize($this->Get_I2C_Ports());
		foreach($DevicePorts AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		$arrayElements[] = array("type" => "Select", "name" => "DeviceBus", "caption" => "Device Bus", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________"); 
		
		$arrayElements[] = array("type" => "Label", "label" => "Wiederholungszyklus in Sekunden (0 -> aus) (optional)");
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Messzyklus", "caption" => "Sekunden");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");  
		
		$arrayActions = array();
		$arrayActions[] = array("type" => "Label", "label" => "Diese Funktionen stehen erst nach Eingabe und Übernahme der erforderlichen Daten zur Verfügung!");
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	}       
	   
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();

			
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {					
			// Logging setzen
			
			//ReceiveData-Filter setzen
			$this->SetBuffer("DeviceIdent", (($this->ReadPropertyInteger("DeviceBus") << 7) + $this->ReadPropertyInteger("DeviceAddress")));
			$Filter = '((.*"Function":"get_used_i2c".*|.*"DeviceIdent":'.$this->GetBuffer("DeviceIdent").'.*)|.*"Function":"status".*)';
			$this->SetReceiveDataFilter($Filter);
			
			If ($this->ReadPropertyBoolean("Open") == true) {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
								
				
				If ($Result == true) {
					$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
					$this->Setup();
				}
			}
			else {
				$this->SetTimerInterval("Messzyklus", 0);
				$this->SetStatus(104);
			}	
		}
		else {
		}
	}
	
	public function ReceiveData($JSONString) 
	{
	    	// Empfangene Daten vom Gateway/Splitter
	    	$data = json_decode($JSONString);
	 	switch ($data->Function) {
			case "notify":
			   	If ($data->Pin == $this->ReadPropertyInteger("Pin")) {
					If (($data->Value == 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
						$this->SendDebug("Interrupt", "Wert: ".(int)$data->Value, 0);
						SetValueInteger($this->GetIDForIdent("LastInterrupt"), time() );
						$this->GetCounterByInterrupt();
					}
					elseIf (($data->Value == 1) AND ($this->ReadPropertyBoolean("Open") == true)) {
						$this->SendDebug("Interrupt", "Wert: ".(int)$data->Value, 0);
						//$this->GetCounter();
					}
			   	}
			   	break; 
			
			case "get_used_i2c":
			   	If ($this->ReadPropertyBoolean("Open") == true) {
					$this->ApplyChanges();
				}
				break;
			 case "status":
			   	If ($data->HardwareRev <= 3) {
				   	If (($data->Pin == 0) OR ($data->Pin == 1)) {
				   		$this->SetStatus($data->Status);		
				   	}
			   	}
				else if ($data->HardwareRev > 3) {
					If (($data->Pin == 2) OR ($data->Pin == 3)) {
				   		$this->SetStatus($data->Status);
				   	}
				}
			   	break;  
	 	}
 	}
	
	// Beginn der Funktionen
 	public function GetRTC()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("SetRTC", "Ausfuehrung", 0);
			
			
			$Sec = $this->CommandClientSocket(pack("L*", 61, $this->GetBuffer("RTC_Handle"), 0, 0), 16);
			$Sec = str_pad(dechex($Sec & 127), 2 ,'0', STR_PAD_LEFT);
			$Min = $this->CommandClientSocket(pack("L*", 61, $this->GetBuffer("RTC_Handle"), 1, 0), 16);
			$Min = str_pad(dechex($Min & 127), 2 ,'0', STR_PAD_LEFT);
			$Hour = $this->CommandClientSocket(pack("L*", 61, $this->GetBuffer("RTC_Handle"), 2, 0), 16);
			If(($Hour & 64) > 0) {
				// 12 Stunden Anzeige
				If (($Hour & 32) > 0) {
					$AMPM = "PM";
				}
				else {
					$AMPM = "AM";
				}
				$Hour = $AMPM." ".str_pad(dechex($Hour & 31), 2 ,'0', STR_PAD_LEFT);
			}
			else {
				// 24 Stunden Anzeige
				$Hour = str_pad(dechex($Hour & 63), 2 ,'0', STR_PAD_LEFT);
			}
			$Date = $this->CommandClientSocket(pack("L*", 61, $this->GetBuffer("RTC_Handle"), 4, 0), 16);
			$Date = str_pad(dechex($Date & 63), 2 ,'0', STR_PAD_LEFT);
			$Month = $this->CommandClientSocket(pack("L*", 61, $this->GetBuffer("RTC_Handle"), 5, 0), 16);
			$Century = ($Month >> 7) & 1;
			$Month = str_pad(dechex($Month & 31), 2 ,'0', STR_PAD_LEFT);
			$Year = $this->CommandClientSocket(pack("L*", 61, $this->GetBuffer("RTC_Handle"), 6, 0), 16);
			$Year = str_pad(dechex($Year & 255), 2 ,'0', STR_PAD_LEFT);
			If ($Century == 1) {
				$Year = $Year + 2000;
			}
			else {
				$Year = $Year + 1900;	
			}
			$Timestamp = mktime(intval($Hour), intval($Min), intval($Sec), intval($Month), intval($Date), intval($Year));
			SetValueInteger($this->GetIDForIdent("RTC_Timestamp"), $Timestamp);
			$MSBofTemp = $this->CommandClientSocket(pack("L*", 61, $this->GetBuffer("RTC_Handle"), 17, 0), 16);
			$LSBofTemp = $this->CommandClientSocket(pack("L*", 61, $this->GetBuffer("RTC_Handle"), 18, 0), 16);
			$MSBofTemp = ($MSBofTemp & 127);
			//$Temp = ($MSBofTemp << 2) | ($LSBofTemp >> 6);
			$LSBofTemp = ($LSBofTemp >> 6) * 0.25;
			$Temp = $MSBofTemp + $LSBofTemp;
			//IPS_LogMessage("GeCoS_IO getRTC_Data", $Temp);
			SetValueFloat($this->GetIDForIdent("RTC_Temperature"), $Temp);
		}
	}
	
	public function SetRTC()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("SetRTC", "Ausfuehrung", 0);
			$DateArray = array();
			$DataArray = array(date("s"), date("i"), date("H"), date("d"), date("m") | 128, date("y"));
			
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_DS3231_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => 0x00, 
											  "Parameter" => serialize($DataArray) )));
			If (!$Result) {
				$this->SendDebug("SetRTC", "Setzen der Zeit fehlerhaft!", 0);
				$this->SetStatus(202);
			}
			else {
				$this->SetStatus(102);
				$this->GetRTC_Data();
				break;
			}	
			
			/*
			// Sekunden
			$Sec = date("s");
			$this->CommandClientSocket(pack("L*", 62, $this->GetBuffer("RTC_Handle"), 0, 4, hexdec($Sec)), 16);
			$Min = date("i");
			$this->CommandClientSocket(pack("L*", 62, $this->GetBuffer("RTC_Handle"), 1, 4, hexdec($Min)), 16);
			$Hour = date("H");
			$this->CommandClientSocket(pack("L*", 62, $this->GetBuffer("RTC_Handle"), 2, 4, hexdec($Hour)), 16);
			$Date = date("d");
			$this->CommandClientSocket(pack("L*", 62, $this->GetBuffer("RTC_Handle"), 4, 4, hexdec($Date)), 16);
			$Month = date("m");
			$this->CommandClientSocket(pack("L*", 62, $this->GetBuffer("RTC_Handle"), 5, 4, (hexdec($Month) | 128) ), 16);
			$Year = date("y");
			$this->CommandClientSocket(pack("L*", 62, $this->GetBuffer("RTC_Handle"), 6, 4, hexdec($Year)), 16);
			$this->GetRTC_Data();
			*/
		}
	} 
	    
	private function Get_I2C_Ports()
	{
		If ($this->HasActiveParent() == true) {
			$I2C_Ports = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_get_ports")));
		}
		else {
			$DevicePorts = array();
			$DevicePorts[0] = "I²C-Bus 0";
			$DevicePorts[1] = "I²C-Bus 1";
			for ($i = 3; $i <= 10; $i++) {
				$DevicePorts[$i] = "MUX I²C-Bus ".($i - 3);
			}
			$I2C_Ports = serialize($DevicePorts);
		}
	return $I2C_Ports;
	}
	
	private function Get_GPIO()
	{
		If ($this->HasActiveParent() == true) {
			$GPIO = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_GPIO")));
		}
		else {
			$AllGPIO = array();
			$AllGPIO[-1] = "undefiniert";
			for ($i = 2; $i <= 27; $i++) {
				$AllGPIO[$i] = "GPIO".(sprintf("%'.02d", $i));
			}
			$GPIO = serialize($AllGPIO);
		}
	return $GPIO;
	}
	 	    
	private function RegisterProfileFloat($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits)
	{
	        if (!IPS_VariableProfileExists($Name))
	        {
	            IPS_CreateVariableProfile($Name, 2);
	        }
	        else
	        {
	            $profile = IPS_GetVariableProfile($Name);
	            if ($profile['ProfileType'] != 2)
	                throw new Exception("Variable profile type does not match for profile " . $Name);
	        }
	        IPS_SetVariableProfileIcon($Name, $Icon);
	        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
	        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
	        IPS_SetVariableProfileDigits($Name, $Digits);
	}
	    
	private function RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize)
	{
	        if (!IPS_VariableProfileExists($Name))
	        {
	            IPS_CreateVariableProfile($Name, 1);
	        }
	        else
	        {
	            $profile = IPS_GetVariableProfile($Name);
	            if ($profile['ProfileType'] != 1)
	                throw new Exception("Variable profile type does not match for profile " . $Name);
	        }
	        IPS_SetVariableProfileIcon($Name, $Icon);
	        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
	        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);        
	}
	
	private function HasActiveParent()
    	{
		$Instance = @IPS_GetInstance($this->InstanceID);
		if ($Instance['ConnectionID'] > 0)
		{
			$Parent = IPS_GetInstance($Instance['ConnectionID']);
			if ($Parent['InstanceStatus'] == 102)
			return true;
		}
        return false;
    	}  
}
?>
