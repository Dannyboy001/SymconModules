<?
class IPS2SingleRoomControl extends IPSModule
{
    	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		
		$this->RegisterPropertyBoolean("Open", false);
	    	$this->RegisterPropertyInteger("ActualTemperatureID", 0);
		$this->RegisterPropertyFloat("KP", 0.0);
		$this->RegisterPropertyFloat("KD", 0.0);
		$this->RegisterPropertyFloat("KI", 0.0);
		$this->RegisterPropertyInteger("Messzyklus", 120);
		$this->RegisterTimer("Messzyklus", 0, 'IPS2SRC_Measurement($_IPS["TARGET"]);');
		$this->RegisterPropertyInteger("PositionElementMax", 100);
		$this->RegisterTimer("PWM", 0, 'IPS2SRC_PWM($_IPS["TARGET"]);');
		$this->RegisterPropertyInteger("MinSwitchTime", 5);
		
	}

	public function ApplyChanges()
	{
		//Never delete this line!
		parent::ApplyChanges();
		
		$this->RegisterVariableFloat("ActualTemperature", "Ist-Temperatur", "~Temperature", 10);
		$this->DisableAction("ActualTemperature");
		$this->RegisterVariableFloat("SetpointTemperature", "Soll-Temperatur", "~Temperature", 20);
		$this->EnableAction("SetpointTemperature");
		$this->RegisterVariableBoolean("OperatingMode", "Betriebsart Automatik", "~Switch", 30);
		$this->EnableAction("OperatingMode");
		$this->RegisterVariableInteger("PositionElement", "Stellelement", "~Intensity.100", 40);
		$this->DisableAction("PositionElement");
		$this->RegisterVariableBoolean("PWM_Mode", "PWM-Status", "~Switch", 40);
		$this->EnableAction("OperatingMode");
		$this->RegisterVariableFloat("SumDeviation", "Summe Regelabweichungen", "~Temperature", 50);
		$this->DisableAction("SumDeviation");
		IPS_SetHidden($this->GetIDForIdent("SumDeviation"), true);
		$this->RegisterVariableFloat("ActualDeviation", "Aktuelle Regelabweichung", "~Temperature", 60);
		$this->DisableAction("ActualDeviation");
		IPS_SetHidden($this->GetIDForIdent("ActualDeviation"), true);
		
		
		
		$this->SetBuffer("LastTrigger", time() - 60);
		
		$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
		
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->Measurement();
			$this->SetStatus(102);
		}
		else {
			$this->SetStatus(104);
		}
		
	}
	
	public function RequestAction($Ident, $Value) 
	{
  		switch($Ident) {
	        case "SetpointTemperature":
	            	$this->Measurement();
	            	//Neuen Wert in die Statusvariable schreiben
	            	SetValueFloat($this->GetIDForIdent($Ident), $Value);
	            	break;
	        case "OperatingMode":
	            	$this->Measurement();
	            	//Neuen Wert in die Statusvariable schreiben
	            	SetValueBoolean($this->GetIDForIdent($Ident), $Value);
			If ($Value == true) {
				$this->DisableAction("SetpointTemperature");
			}
			else {
				$this->EnableAction("SetpointTemperature");
			}
	            	break;
	        default:
	            throw new Exception("Invalid Ident");
	    	}
	}
	
	public function Measurement()
	{
		SetValueFloat($this->GetIDForIdent("ActualTemperature"), GetValueFloat($this->ReadPropertyInteger("ActualTemperatureID")) );
		
		//Ta = Rechenschrittweite (Abtastzeit)
		$Ta = Round( (time() - (int)$this->GetBuffer("LastTrigger")) / 60, 0);
		//Schutzmechanismus falls Skript innerhalb einer Minute zweimal ausgeführt wird
		$Ta = Max($Ta, 1);
		
		// Die vorherige Regelabweichung ermitteln
		$ealt = GetValueFloat($this->GetIDForIdent("ActualDeviation")); 
		
		//Aktuelle Regelabweichung bestimmen
		$e = GetValueFloat($this->GetIDForIdent("SetpointTemperature")) - GetValueFloat($this->GetIDForIdent("ActualTemperature"));
		
		// Vorherige Regelabweichung durch jetzige ersetzen 
		SetValueFloat($this->GetIDForIdent("ActualDeviation"), $e);
		
		//Die Summe aller vorherigen Regelabweichungen bestimmen
		If (((GetValueInteger($this->GetIDForIdent("PositionElement")) == 0) and ($e < 0)) OR ((GetValueInteger($this->GetIDForIdent("PositionElement")) == 100) and ($e > 0))) {
			// Die Negativ-Werte sollen nicht weiter aufsummiert werden, wenn der Stellmotor schon auf 0 ist bzw. Die Positiv-Werte sollen nicht weiter aufsummiert werden, wenn der Stellmotor schon auf 100 ist
			$esum = GetValueFloat($this->GetIDForIdent("SumDeviation"));
		}
		else {
			$esum = GetValueFloat($this->GetIDForIdent("SumDeviation")) + $e;
		   	SetValueFloat($this->GetIDForIdent("SumDeviation"), $esum);
		}
			    
		$PositionElement = $this->PID($this->ReadPropertyFloat("KP"), $this->ReadPropertyFloat("KI"), $this->ReadPropertyFloat("KD"), $e, $esum, $ealt, $Ta);
		SetValueInteger($this->GetIDForIdent("PositionElement"), $PositionElement);
		
		// Minimale Schaltänderungszeit in Sekunden
		//$PWMzyklus = time() - (int)$this->GetBuffer("LastTrigger");
		$PWMzyklus = $Ta * 60;
		$PWMmin = $this->ReadPropertyInteger("MinSwitchTime"); 
		
		// Errechnen der On-Zeit
		$PWMontime = $PWMzyklus / 100 * $PositionElement;
		// Schutzmechnismus damit die Minimum-Einschaltzeit eingehalten wird
		If (($PWMontime > 0) and ($PWMontime < $PWMmin)) {
		   $PWMontime = $PWMmin;
		   }
	   	// Schutzmechnismus damit die Minimum-Ausschaltzeit eingehalten wird
		If (($PWMzyklus - $PWMontime) < $PWMmin) {
		   $PWMontime = $PWMzyklus;
		   }
		// Schreiben und setzen
		If ($PWMontime> 0) {
			SetValueBoolean($this->GetIDForIdent("PWM_Mode"), true);
			$this->SetTimerInterval("PWM", (int)$PWMontime * 1000);
		   }
		else {
			SetValueBoolean($this->GetIDForIdent("PWM_Mode"), false);
			}
		
		
		
		
		$this->SetBuffer("LastTrigger", time());
	}
	
	public function PWM()
	{
		SetValueBoolean($this->GetIDForIdent("PWM_Mode"), false);
		$this->SetTimerInterval("PWM", 0);
	}
	
	// Berechnet nächsten Stellwert der Aktoren
	private function PID($Kp, $Ki, $Kd, $e, $esum, $ealt, $Ta)
	{
		//e = aktuelle Reglerabweichung -> Soll-Ist
		//ealt = vorherige Reglerabweichung
		//esum = die Summe aller bisherigen Abweichungen e
		//y = Antwort -> muss im Bereich zwischen 0-100 sein
		//esum = esum + e
		//y = Kp * e + Ki * Ta * esum + Kd * (e – ealt)/Ta
		//ealt = e
		//Kp = Verstärkungsfaktor Proportionalregler
		//Ki = Verstärkungsfaktor Integralregler
		//Kd = Verstärkungsfaktor Differenzialregler

		// Die Berechnung des neuen Regelwertes
		$y = ($Kp * $e + $Ki * $Ta * $esum + $Kd * ($e - $ealt) / $Ta);

	   	// Dieses ist eine Begrenzung des Stellventils, da die Heizkörper sonst sehr heiß werden
		$y = min(max($y, 0), $this->ReadPropertyInteger("PositionElementMax"));
		$Stellwert = $y;

	return $Stellwert;
	}
	
	//*************************************************************************************************************
	// Liest die aktuellen Sollwertdaten aus der csv-Datei ein
	private function TemperaturSollwert($filename)
		{
		$fp = fopen(IPS_GetKernelDir()."parameter/".$filename.".csv", "r");

		$zeilen = array();

		while(!feof($fp))
			{
		   $zeilen[] = fgetcsv  ( $fp  , 4096 , ";" , "\"" );
			}
		fclose($fp);

		// Zeile festlegen durch Auswahl Werktag, Wochenende bzw. Feiertag und Abwesenheit
		If (GetValueBoolean(43287 /*[Startseite\Anwesenheit]*/ ) == true)
			{
			If (GetValueBoolean(50254 /*[Sonstige Daten\Variablen\Sonstige Variablen\Werktag]*/ ) == true)
				$raw = 1;
			elseif ((GetValueBoolean(36415 /*[Sonstige Daten\Variablen\Sonstige Variablen\Wochenende]*/ ) == true) OR (GetValueBoolean(28915 /*[Sonstige Daten\Variablen\Sonstige Variablen\Feiertag]*/ ) == true))
			$raw = 2;
			}
		else
			{
		   $raw = 1; // muss normalerweise 3 sein!!
		   }

		// Spalte festlegen durch die aktuelle Uhrzeit (Durchlauf jeweils zu jeder Viertelstunde erforderlich)
		$column = (((GetValueInteger(26431 /*[Sonstige Daten\Variablen\Sonstige Variablen\Aktuelle Stunde]*/ ) * 4) + 2) + (GetValueInteger(50008 /*[Sonstige Daten\Variablen\Sonstige Variablen\Aktuelle Minute]*/ ) / 15) - 1);

		$result = $zeilen[$raw][$column];

	Return $result;
	}
	
}

?>