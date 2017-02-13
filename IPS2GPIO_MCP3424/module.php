<?
    // Klassendefinition
    class IPS2GPIO_MCP3424 extends IPSModule 
    {
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
		$this->RegisterPropertyInteger("Resolution_0", 0);
		$this->RegisterPropertyInteger("Resolution_1", 0);
		$this->RegisterPropertyInteger("Resolution_2", 0);
		$this->RegisterPropertyInteger("Resolution_3", 0);
		$this->RegisterPropertyInteger("Amplifier_0", 0);
		$this->RegisterPropertyInteger("Amplifier_1", 0);
		$this->RegisterPropertyInteger("Amplifier_2", 0);
		$this->RegisterPropertyInteger("Amplifier_3", 0);
		$this->RegisterPropertyBoolean("Active_0", true);
		$this->RegisterPropertyBoolean("Active_1", true);
		$this->RegisterPropertyBoolean("Active_2", true);
		$this->RegisterPropertyBoolean("Active_3", true);
		$this->RegisterPropertyBoolean("Logging_0", false);
		$this->RegisterPropertyBoolean("Logging_1", false);
		$this->RegisterPropertyBoolean("Logging_2", false);
		$this->RegisterPropertyBoolean("Logging_3", false);
		
            	$this->RegisterTimer("Messzyklus", 0, 'I2GAD2_Measurement($_IPS["TARGET"]);');
        }
 	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 200, "icon" => "error", "caption" => "Pin wird doppelt genutzt!");
		$arrayStatus[] = array("code" => 201, "icon" => "error", "caption" => "Pin ist an diesem Raspberry Pi Modell nicht vorhanden!"); 
		
		$arrayElements = array(); 
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv"); 
 		
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "104 dez. / 0x68h", "value" => 104);
		$arrayOptions[] = array("label" => "106 dez. / 0x6Ah", "value" => 106);
		$arrayOptions[] = array("label" => "108 dez. / 0x6Ch", "value" => 108);
		$arrayOptions[] = array("label" => "110 dez. / 0x6Eh", "value" => 110);
		$arrayElements[] = array("type" => "Select", "name" => "DeviceAddress", "caption" => "Device Adresse", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "Label", "label" => "I²C-Bus (Default ist 1)");
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "I²C-Bus 0", "value" => 0);
		$arrayOptions[] = array("label" => "I²C-Bus 1", "value" => 1);
		$arrayOptions[] = array("label" => "MUX I²C-Bus 0", "value" => 3);
		$arrayOptions[] = array("label" => "MUX I²C-Bus 1", "value" => 4);
		$arrayOptions[] = array("label" => "MUX I²C-Bus 2", "value" => 5);
		$arrayOptions[] = array("label" => "MUX I²C-Bus 3", "value" => 6);
		$arrayOptions[] = array("label" => "MUX I²C-Bus 4", "value" => 7);
		$arrayOptions[] = array("label" => "MUX I²C-Bus 5", "value" => 8);
		$arrayOptions[] = array("label" => "MUX I²C-Bus 6", "value" => 9);
		$arrayOptions[] = array("label" => "MUX I²C-Bus 7", "value" => 10);
		$arrayElements[] = array("type" => "Select", "name" => "DeviceBus", "caption" => "Device Bus", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "Label", "label" => "Wiederholungszyklus in Sekunden (0 -> aus, 15 sek -> Minimum)"); 
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Messzyklus", "caption" => "Messzyklus (sek)");
		
		
		$arrayOptionsResolution = array();
		$arrayOptionsResolution[] = array("label" => "12 Bit", "value" => 0);
		$arrayOptionsResolution[] = array("label" => "14 Bit", "value" => 1);
		$arrayOptionsResolution[] = array("label" => "16 Bit", "value" => 2);
		$arrayOptionsResolution[] = array("label" => "18 Bit", "value" => 3);
		
		$arrayOptionsAmplifier = array();
		$arrayOptionsAmplifier[] = array("label" => "1x", "value" => 0);
		$arrayOptionsAmplifier[] = array("label" => "2x", "value" => 1);
		$arrayOptionsAmplifier[] = array("label" => "4x", "value" => 2);
		$arrayOptionsAmplifier[] = array("label" => "8x", "value" => 3);
		
		
		for ($i = 0; $i <= 3; $i++) {
			$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
			$arrayElements[] = array("type" => "Label", "label" => "Optionen Kanal ".($i + 1));
			$arrayElements[] = array("type" => "Label", "label" => "Auflösung des Kanals wählen (Default 12 Bit)");
			$arrayElements[] = array("name" => "Active_".$i, "type" => "CheckBox",  "caption" => "Aktiv"); 
			$arrayElements[] = array("type" => "Select", "name" => "Resolution_".$i, "caption" => "Auflösung", "options" => $arrayOptionsResolution );
			$arrayElements[] = array("type" => "Label", "label" => "Verstärkung des Kanals wählen (Default 1x)");
			$arrayElements[] = array("type" => "Select", "name" => "Amplifier_".$i, "caption" => "Verstärkung", "options" => $arrayOptionsAmplifier );
			$arrayElements[] = array("name" => "Logging_".$i, "type" => "CheckBox",  "caption" => "Logging"); 
		}
				
		$arrayActions = array();
		$arrayActions[] = array("type" => "Label", "label" => "Diese Funktionen stehen erst nach Eingabe und Übernahme der erforderlichen Daten zur Verfügung!");
		
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	}       
	   
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
            	//Connect to available splitter or create a new one
	    	$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
	    	// Device Adresse prüfen
	    	If (($this->ReadPropertyInteger("DeviceAddress") < 0) OR ($this->ReadPropertyInteger("DeviceAddress") > 128)) {
	    		IPS_LogMessage("IPS2GPIO MCP3424","I2C-Device Adresse in einem nicht definierten Bereich!");  
	    	}
	    	// Profil anlegen
	    	$this->RegisterProfileFloat("mVolt.mV", "Electricity", "", " mV", -100000, +100000, 0.1, 3);
		
		//Status-Variablen anlegen
		$this->RegisterVariableFloat("Channel_1", "Kanal 1", "mVolt.mV", 10);
          	$this->DisableAction("Channel_1");
		IPS_SetHidden($this->GetIDForIdent("Channel_1"), false);
		
		$this->RegisterVariableFloat("Channel_2", "Kanal 2", "mVolt.mV", 20);
          	$this->DisableAction("Channel_2");
		IPS_SetHidden($this->GetIDForIdent("Channel_2"), false);
		
		$this->RegisterVariableFloat("Channel_3", "Kanal 3", "mVolt.mV", 30);
          	$this->DisableAction("Channel_3");
		IPS_SetHidden($this->GetIDForIdent("Channel_3"), false);
		
		$this->RegisterVariableFloat("Channel_4", "Kanal 4", "mVolt.mV", 40);
          	$this->DisableAction("Channel_4");
		IPS_SetHidden($this->GetIDForIdent("Channel_4"), false);
		
		$MeasurementData = array();
		$this->SetBuffer("MeasurementData", serialize($MeasurementData));
		
		If (IPS_GetKernelRunlevel() == 10103) {
			for ($i = 0; $i <= 3; $i++) {
				AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("Channel_".($i + 1)), $this->ReadPropertyBoolean("Logging_".$i));
			}
			IPS_ApplyChanges(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0]);
						
			//ReceiveData-Filter setzen
			$this->SetBuffer("DeviceIdent", (($this->ReadPropertyInteger("DeviceBus") << 7) + $this->ReadPropertyInteger("DeviceAddress")));
			$Filter = '((.*"Function":"get_used_i2c".*|.*"DeviceIdent":'.$this->GetBuffer("DeviceIdent").'.*)|.*"Function":"status".*)';
			//$this->SendDebug("IPS2GPIO", $Filter, 0);
			$this->SetReceiveDataFilter($Filter);
		
			
			If ($this->ReadPropertyBoolean("Open") == true) {
				$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
				$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
				// Erste Messdaten einlesen
				$this->Measurement();
				$this->SetStatus(102);
			}
			else {
				$this->SetTimerInterval("Messzyklus", 0);
				$this->SetStatus(104);
			}	
		}
		else {
			$this->SetTimerInterval("Messzyklus", 0);
		}
	}
	
	public function ReceiveData($JSONString) 
	{
	    	// Empfangene Daten vom Gateway/Splitter
	    	$data = json_decode($JSONString);
	 	switch ($data->Function) {
			 case "get_used_i2c":
			   	If ($this->ReadPropertyBoolean("Open") == true) {
					$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
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
			case "set_i2c_byte_block":
			   	If ($data->DeviceIdent == $this->GetBuffer("DeviceIdent")) {
			   		$this->SetBuffer("MeasurementData", $data->ByteArray);
			   	}
				// Test
				$MeasurementData = unserialize($this->GetBuffer("MeasurementData"));
				//IPS_LogMessage("IPS2GPIO MCP", "Anzahl Daten: ".count($MeasurementData));
				
				// Auslesen des Konfigurations-Registers
				$Configuration = $MeasurementData[count($MeasurementData)];
				$Amplifier = ($Configuration & 3);
				$Resolution = ($Configuration & 12) >> 2;
				$Channel = ($Configuration & 96) >> 5;
				$ReadyBit = ($Configuration & 128) >> 7;
				//IPS_LogMessage("IPS2GPIO MCP", "Anzahl Daten: ".count($MeasurementData)." Verst: ".$Amplifier." Aufl:: ".$Resolution." RDY:".$ReadyBit);
				If ($ReadyBit == false) {
					//IPS_LogMessage("IPS2GPIO MCP", "Channel: ".$Channel);
					switch ($Resolution) {
						case 0:	
							//IPS_LogMessage("IPS2GPIO MCP", "Auflösung 12 Bit");
							$SignBit = ($MeasurementData[1] & 8) >> 3;
							$Value = (($MeasurementData[1] & 15) << 8) | $MeasurementData[2];
							If ($SignBit == 0) {
								//$Value = (($MeasurementData[1] & 7) << 8) | $MeasurementData[2];
								$Value = $Value * 0.001;
							}
							else {
								//IPS_LogMessage("IPS2GPIO MCP", "Daten 1: ".$MeasurementData[1]." Daten 2: ".$MeasurementData[2]);
 								$Value = -(bitflip($Value)) * 0.001;
								//$Value = -$Value;
								//IPS_LogMessage("IPS2GPIO MCP", "Value: ".$Value);
								//$Value = $Value * 0.001;
							}
							break;
						case 1:
							//IPS_LogMessage("IPS2GPIO MCP", "Auflösung 14 Bit");
							$SignBit = ($MeasurementData[1] & 32) >> 5;
							$Value = (($MeasurementData[1] & 63) << 8) | $MeasurementData[2];
							If ($SignBit == 0) {
								//$Value = (($MeasurementData[1] & 31) << 8) | $MeasurementData[2];
								$Value = $Value * 0.00025;
							}
							else {
								$Value = -(bitflip($Value)) * 0.00025;
								//$Value = $Value - 1;
								//$Value = ~$Value * 0.00025;
							}
							break;
						case 2:	
							//IPS_LogMessage("IPS2GPIO MCP", "Auflösung 16 Bit");
							$SignBit = ($MeasurementData[1] & 128) >> 7;
							$Value = (($MeasurementData[1] & 255) << 8) | $MeasurementData[2];
							If ($SignBit == 0) {
								//$Value = (($MeasurementData[1] & 127) << 8) | $MeasurementData[2];
								$Value = $Value * (6.25 * pow(10,-5));
							}
							else {
								$Value = -(bitflip($Value)) * (6.25 * pow(10,-5));
								//$Value = $Value - 1;
								//$Value = ~$Value * (6.25 * pow(10,-5));
							}
							break;
						case 3:
							//IPS_LogMessage("IPS2GPIO MCP", "Auflösung 18 Bit");
							$SignBit = ($MeasurementData[1] & 2) >> 1;
							$Value = (($MeasurementData[1] & 2) << 16) | ($MeasurementData[2] << 8) | $MeasurementData[3];
							If ($SignBit == 0) {
								//$Value = (($MeasurementData[1] & 1) << 16) | ($MeasurementData[2] << 8) | $MeasurementData[3];  
								$Value = $Value * (1.5625 * pow(10,-5));
							}
							else {
								$Value = -(bitflip($Value)) * (1.5625 * pow(10,-5));
								//$Value = $Value - 1;
								//$Value = ~$Value * (1.5625 * pow(10,-5));
							}
							break;	
					}	
					SetValueFloat($this->GetIDForIdent("Channel_".($Channel + 1)), $Value * 1000);
				}
				
				for ($i = 1; $i <= count($MeasurementData); $i++) {
					//IPS_LogMessage("IPS2GPIO MCP", "Kanal: ".$Channel." Daten ".$i.": ".$MeasurementData[$i]);
				}
			   	break;
	 	}
 	}
	// Beginn der Funktionen
	// Führt eine Messung aus
	public function Measurement()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			// Messwerterfassung setzen
			$i = 0;
			for ($i = 0; $i <= 3; $i++) {
				If ($this->ReadPropertyBoolean("Active_".$i) == true) {
					$Configuration = ($i << 5) | (1 << 4) | ($this->ReadPropertyInteger("Resolution_".$i) << 2) | $this->ReadPropertyInteger("Amplifier_".$i);
					$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_write_byte_onhandle", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Value" => $Configuration)));
					IPS_Sleep(400);

					If ($this->ReadPropertyInteger("Resolution_".$i) <= 2) { 
						$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_bytes", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => $this->ReadPropertyInteger("DeviceAddress"), "Count" => 3)));
					}
					elseif ($this->ReadPropertyInteger("Resolution_".$i) == 3) {
						$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_bytes", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => $this->ReadPropertyInteger("DeviceAddress"), "Count" => 4)));
					}
				}
			}
		}
	}
	        
	private function bitflip($Value)
	{
	   	// Umwandlung in einen Binär-String
		$bin = decbin($Value);
	   	$not = "";
	   	// Umstellung der Binär-Strings
		for ($i = 0; $i < strlen($bin); $i++)
	   		{
	      		if($bin[$i] == 0) { $not .= '1'; }
	      		if($bin[$i] == 1) { $not .= '0'; }
	   	}
		// Rückgabe als Integer
	return bindec($not);
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

}
?>
