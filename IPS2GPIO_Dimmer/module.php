<?
    // Klassendefinition
    class IPS2GPIO_Dimmer extends IPSModule 
    {
 
        // Der Konstruktor des Moduls
        // Überschreibt den Standard Kontruktor von IPS
        public function __construct($InstanceID) {
            // Diese Zeile nicht löschen
            parent::__construct($InstanceID);
 
            // Selbsterstellter Code
        }
 
        // Überschreibt die interne IPS_Create($id) Funktion
        public function Create() {
            // Diese Zeile nicht löschen.
            parent::Create();
 
        }
 
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() {
            // Diese Zeile nicht löschen
            parent::ApplyChanges();
        }
 
        /**
        * Die folgenden Funktionen stehen automatisch zur Verfügung, wenn das Modul über die "Module Control" eingefügt wurden.
        * Die Funktionen werden, mit dem selbst eingerichteten Prefix, in PHP und JSON-RPC wiefolgt zur Verfügung gestellt:
        *
        * ABC_MeineErsteEigeneFunktion($id);
        *
        */
        // Setzt einen Pin in einen bestimmten Modus
	public function Set_Mode($RPiIP, $RPiPort, $IPS_ID, $Command, $GPIO_Pin, $GPIO_Mode)
	{
   		list($result, $IPS_User, $IPS_Pass) = RemoteAccessData();
		$result = "";
   		$result = exec('sudo python '.IPS_GetKernelDir().'modules/SymconModules/IPS2GPIO/ips2gpio.py '.$RPiIP.' '.$RPiPort.' '.$IPS_User.' '.$IPS_Pass.' '.$IPS_ID.' '.$Command.' '.$GPIO_Pin.' '.$GPIO_Mode);
	return $result;
	}
	// Ermittelt den User und das Passwort für den Fernzugriff (nur RPi)
	private function RemoteAccessData()
	{
	   	$result = true;
	   	exec('sudo cat /root/.symcon', $ResultArray);
	   	If (strpos($ResultArray[0], "Licensee=") === false)
			{
			$result = false;
			}
		else
			{
	      		$User = substr(strstr($ResultArray[0], "="),1);
	      		}
		If (strpos($ResultArray[(count($ResultArray))-1], "Password=") === false)
			{
			$result = false;
			}
		else
			{
	      		$Pass = base64_decode(substr(strstr($ResultArray[(count($ResultArray))-1], "="),1));
	      		}
	return array($result, $User, $Pass);
	}
	
    }
?>



