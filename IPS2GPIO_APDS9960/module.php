<?
    // Klassendefinition
    class IPS2GPIO_APDS9960 extends IPSModule 
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
 	    	$this->RegisterPropertyInteger("DeviceAddress", 57);
		$this->RegisterPropertyInteger("DeviceBus", 1);
		$this->RegisterPropertyInteger("Messzyklus", 60);
		$this->RegisterTimer("Messzyklus", 0, 'I2GPCF8583_Setup($_IPS["TARGET"]);');
		$this->RegisterPropertyInteger("Pin", -1);
		$this->SetBuffer("PreviousPin", -1);
		
	
		
	
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
		$arrayOptions[] = array("label" => "57 dez. / 0x39h", "value" => 57);
		
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
		$arrayElements[] = array("type" => "Label", "label" => "Angabe der GPIO-Nummer (Broadcom-Number) für den Interrupt (optional)"); 
		$arrayOptions = array();
		$GPIO = array();
		$GPIO = unserialize($this->Get_GPIO());
		If ($this->ReadPropertyInteger("Pin") >= 0 ) {
			$GPIO[$this->ReadPropertyInteger("Pin")] = "GPIO".(sprintf("%'.02d", $this->ReadPropertyInteger("Pin")));
		}
		ksort($GPIO);
		foreach($GPIO AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		$arrayElements[] = array("type" => "Select", "name" => "Pin", "caption" => "GPIO-Nr.", "options" => $arrayOptions );

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
			
		
		//Status-Variablen anlegen
             	$this->RegisterVariableInteger("ChipID", "Chip ID", "", 5);
		$this->DisableAction("ChipID");
		IPS_SetHidden($this->GetIDForIdent("ChipID"), true);
		
		
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {					
			If (intval($this->GetBuffer("PreviousPin")) <> $this->ReadPropertyInteger("Pin")) {
				$this->SendDebug("ApplyChanges", "Pin-Wechsel - Vorheriger Pin: ".$this->GetBuffer("PreviousPin")." Jetziger Pin: ".$this->ReadPropertyInteger("Pin"), 0);
			}		  
			
			//ReceiveData-Filter setzen
			$this->SetBuffer("DeviceIdent", (($this->ReadPropertyInteger("DeviceBus") << 7) + $this->ReadPropertyInteger("DeviceAddress")));
			$Filter = '((.*"Function":"get_used_i2c".*|.*"DeviceIdent":'.$this->GetBuffer("DeviceIdent").'.*)|(.*"Function":"status".*|.*"Pin":'.$this->ReadPropertyInteger("Pin").'.*))';
			$this->SetReceiveDataFilter($Filter);
			
			If ($this->ReadPropertyBoolean("Open") == true) {
				If ($this->ReadPropertyInteger("Pin") >= 0) {
					$ResultPin = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
										  "Pin" => $this->ReadPropertyInteger("Pin"), "PreviousPin" => $this->GetBuffer("PreviousPin"), "InstanceID" => $this->InstanceID, "Modus" => 0, "Notify" => true, "GlitchFilter" => 5, "Resistance" => 2)));	
				}
				else {
					$ResultPin = true;
				}
				$this->SetBuffer("PreviousPin", $this->ReadPropertyInteger("Pin"));
				
				$ResultI2C = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
								
				If (($ResultI2C == true) AND ($ResultPin == true)) {
					$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
					$this->Setup();
				}
			}
			else {
				$this->SetTimerInterval("Messzyklus", 0);
				$this->SetStatus(104);
			}	
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
						$this->SendDebug("Interrupt", "Wert: ".(int)$data->Value." -> Counter auslesen", 0);
						SetValueInteger($this->GetIDForIdent("LastInterrupt"), time() );
						//$this->GetCounterByInterrupt();
					}
					elseIf (($data->Value == 1) AND ($this->ReadPropertyBoolean("Open") == true)) {
						$this->SendDebug("Interrupt", "Wert: ".(int)$data->Value." -> keine Aktion", 0);
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
 	private function Setup()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Setup", "Ausfuehrung", 0);
			// Ermittlung der Device ID
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_APDS9960_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0x92, "Count" => 1)));
			If ($Result < 0) {
				$this->SendDebug("Setup", "Ermittlung der DeviceID fehlerhaft!", 0);
				$this->SetStatus(202);
				$this->SetTimerInterval("Messzyklus", 0);
				return;
			}
			else {
				If (($Result == 0xAB) OR ($Result == 0x9C)) {
					SetValueInteger($this->GetIDForIdent("ChipID"), $Result);
				}
				else {
					$this->SendDebug("Setup", "Laut Chip ID ist es kein zulaessiger ADPS9960!", 0);
				}
				$this->SetStatus(102);
			}
			
			$Result = $this->SetMode( 7, 0);
			If ($Result == false) {
				$this->SetStatus(202);
				$this->SetTimerInterval("Messzyklus", 0);
			}
			
			// ATIME setzen
			$Bitmask = 219;
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_APDS9960_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0x81, "Value" => $Bitmask)));
			If (!$Result) {
				$this->SendDebug("Setup", "Setzen ATIME fehlerhaft!", 0);
				$this->SetStatus(202);
				$this->SetTimerInterval("Messzyklus", 0);
				return;
			}
			else {
				$this->SetStatus(102);
			}   	
			
			// WTIME setzen
			$Bitmask = 246;
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_APDS9960_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0x83, "Value" => $Bitmask)));
			If (!$Result) {
				$this->SendDebug("Setup", "Setzen WTIME fehlerhaft!", 0);
				$this->SetStatus(202);
				$this->SetTimerInterval("Messzyklus", 0);
				return;
			}
			else {
				$this->SetStatus(102);
			}   
			
			// PPULSE setzen
			$Bitmask = 0x87;
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_APDS9960_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0x8E, "Value" => $Bitmask)));
			If (!$Result) {
				$this->SendDebug("Setup", "Setzen PPULSE fehlerhaft!", 0);
				$this->SetStatus(202);
				$this->SetTimerInterval("Messzyklus", 0);
				return;
			}
			else {
				$this->SetStatus(102);
			}   
			
			// POFFSET_UR setzen
			$Bitmask = 0;
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_APDS9960_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0x9D, "Value" => $Bitmask)));
			If (!$Result) {
				$this->SendDebug("Setup", "Setzen POFFSET_UR fehlerhaft!", 0);
				$this->SetStatus(202);
				$this->SetTimerInterval("Messzyklus", 0);
				return;
			}
			else {
				$this->SetStatus(102);
			}   
			
			// POFFSET_DL setzen
			$Bitmask = 0;
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_APDS9960_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0x9E, "Value" => $Bitmask)));
			If (!$Result) {
				$this->SendDebug("Setup", "Setzen POFFSET_DL fehlerhaft!", 0);
				$this->SetStatus(202);
				$this->SetTimerInterval("Messzyklus", 0);
				return;
			}
			else {
				$this->SetStatus(102);
			}  
			
			// CONFIG1 setzen
			$Bitmask = 0x60;
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_APDS9960_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0x8D, "Value" => $Bitmask)));
			If (!$Result) {
				$this->SendDebug("Setup", "Setzen CONFIG1 fehlerhaft!", 0);
				$this->SetStatus(202);
				$this->SetTimerInterval("Messzyklus", 0);
				return;
			}
			else {
				$this->SetStatus(102);
			}  
			
		}
	}
	    
	private function GetMode()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetMode", "Ausfuehrung", 0);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_APDS9960_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0x80, "Count" => 1)));
			If ($Result < 0) {
				$this->SendDebug("Setup", "Ermittlung des Status fehlerhaft!", 0);
				$this->SetStatus(202);
				$this->SetTimerInterval("Messzyklus", 0);
			}
			else {
				$this->SetStatus(102);
			}
		}
	return $Result;
	}

	private function SetMode(Int $Mode, Int $Enable)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("SetMode", "Ausfuehrung", 0);
			/* Read current ENABLE register */
			$Bitmask = $this->GetMode();
			If ($Bitmask < 0) {
				return false;
			}
			/* Change bit(s) in ENABLE register */
			$Enable = $Enable & 0x01;
			
			If ($Mode >= 0) AND ($Mode <= 6) {
				If ($Enable) {
					$Bitmask = $Bitmask | (1 << $Mode);
				}
				else {
					$Bitmask = $Bitmask | ~(1 << $Mode);
				}
			}
			elseif ($Mode == 7) {
				If ($Enable) {
					$Bitmask = 0x7F;
				}
				else {
					$Bitmask = 0x00;
				}
			}
					
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_APDS9960_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0x80, "Value" => $Bitmask)));
			If (!$Result) {
				$this->SendDebug("Setup", "Setzen der Enable Status fehlerhaft!", 0);
				$this->SetStatus(202);
				$this->SetTimerInterval("Messzyklus", 0);
				return false;
			}
			else {
				$this->SetStatus(102);
				return true;
			}   		
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
