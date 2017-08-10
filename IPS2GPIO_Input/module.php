<?
    // Klassendefinition
    class IPS2GPIO_Input extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
            	$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyInteger("Pin", -1);
            	$this->RegisterPropertyBoolean("ActionValue", true);
            	$this->RegisterPropertyInteger("GlitchFilter", 10);
	    	$this->RegisterPropertyInteger("PUL", 0);
            	$this->RegisterPropertyInteger("TriggerScript", 0);
            	$this->RegisterPropertyInteger("ToggleScript", 0);
 	    	$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
        }
	/*
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 200, "icon" => "error", "caption" => "Pin wird doppelt genutzt!");
		$arrayStatus[] = array("code" => 201, "icon" => "error", "caption" => "Pin ist an diesem Raspberry Pi Modell nicht vorhanden!"); 
		
		$arrayElements = array(); 
		$arrayElements[] = array("type" => "CheckBox", "name" => "Open", "caption" => "Aktiv");
 		$arrayElements[] = array("type" => "Label", "label" => "Angabe der GPIO-Nummer (Broadcom-Number)"); 
  		
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "ungesetzt", "value" => -1);
		for ($i = 0; $i <= 27; $i++) {
			$arrayOptions[] = array("label" => $i, "value" => $i);
		}
		$arrayElements[] = array("type" => "Select", "name" => "Pin", "caption" => "GPIO-Nr.", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "Label", "label" => "Der Trigger soll reagieren auf (aktiviert => True):");
		$arrayElements[] = array("type" => "CheckBox", "name" => "ActionValue", "caption" => "Aktions Wert");
		$arrayElements[] = array("type" => "Label", "label" => "Zur Software-Entprellung angeschlossener Taster/Schalter (0-300000ms)");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "GlitchFilter", "caption" => "Glitchfilter (ms)");	
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Setzen der internen Pull Up/Down Widerstände"); 
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "Kein", "value" => 0);
		$arrayOptions[] = array("label" => "Pull-Down", "value" => 1);
		$arrayOptions[] = array("label" => "Pull-Up", "value" => 2);		
		$arrayElements[] = array("type" => "Select", "name" => "PUL", "caption" => "Widerstand setzen", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Skriptausführung als Reaktion auf:"); 
		$arrayElements[] = array("type" => "SelectScript", "name" => "TriggerScript", "caption" => "Triggerimpuls");
		$arrayElements[] = array("type" => "SelectScript", "name" => "ToggleScript", "caption" => "Toggle Status"); 

		$arrayActions = array();
		If (($this->ReadPropertyInteger("Pin") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
					}
		else {
			$arrayActions[] = array("type" => "Label", "label" => "Diese Funktionen stehen erst nach Eingabe und Übernahme der erforderlichen Daten zur Verfügung!");
		}
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	}       
	*/
	    
       // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
                // Diese Zeile nicht löschen
                parent::ApplyChanges();
  	   
	        //Status-Variablen anlegen
	        $this->RegisterVariableBoolean("Status", "Status", "~Switch", 10);
                $this->DisableAction("Status");
                $this->RegisterVariableBoolean("Toggle", "Toggle", "~Switch", 20);
                $this->DisableAction("Toggle");
                $this->RegisterVariableBoolean("Trigger", "Trigger", "~Switch", 30);
                $this->DisableAction("Trigger");
            
                //ReceiveData-Filter setzen
		$Filter = '(.*"Function":"get_usedpin".*|.*"Pin":'.$this->ReadPropertyInteger("Pin").'.*)';
		$this->SetReceiveDataFilter($Filter);
		
		If (IPS_GetKernelRunlevel() == 10103) {
			If (($this->ReadPropertyInteger("Pin") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
				$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
									  "Pin" => $this->ReadPropertyInteger("Pin"), "InstanceID" => $this->InstanceID, "Modus" => 0, "Notify" => true, "GlitchFilter" => $this->ReadPropertyInteger("GlitchFilter"), "Resistance" => $this->ReadPropertyInteger("PUL"))));
				$this->SetStatus(102);
			}
			else {
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
			   		// Trigger kurzzeitig setzen
			   		If ($data->Value == $this->ReadPropertyBoolean("ActionValue") ) {
			   			SetValueBoolean($this->GetIDForIdent("Trigger"), true);
			   			If ($this->ReadPropertyInteger("TriggerScript") > 0) {
			   				IPS_RunScript($this->ReadPropertyInteger("TriggerScript"));
			   			}
			   			SetValueBoolean($this->GetIDForIdent("Trigger"), false);
			   		}
			   		// Toggle-Variable
			   		If ((GetValueBoolean($this->GetIDForIdent("Status")) == false) and ($data->Value == true)) {
			   			SetValueBoolean($this->GetIDForIdent("Toggle"), !GetValueBoolean($this->GetIDForIdent("Toggle")));
			   			If ($this->ReadPropertyInteger("ToggleScript") > 0) {
			   				IPS_RunScript($this->ReadPropertyInteger("ToggleScript"));
			   			}
			   		}
			   		// Status setzen
			   		SetValueBoolean($this->GetIDForIdent("Status"), $data->Value);
			   	}
			   	break;
			   case "get_usedpin":
			   	If ($this->ReadPropertyBoolean("Open") == true) {
					$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
									  "Pin" => $this->ReadPropertyInteger("Pin"), "InstanceID" => $this->InstanceID, "Modus" => 0, "Notify" => true, "GlitchFilter" => $this->ReadPropertyInteger("GlitchFilter"), "Resistance" => $this->ReadPropertyString("PUL"))));
				}
				break;
			   case "status":
			   	If ($data->Pin == $this->ReadPropertyInteger("Pin")) {
			   		$this->SetStatus($data->Status);
			   	}
			   	break;
			   case "freepin":
			   	// Funktion zum erstellen dynamischer Pulldown-Menüs
			   	break;
	 	}
 	}
	// Beginn der Funktionen
	

}
?>
