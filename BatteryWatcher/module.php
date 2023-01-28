<?php

declare(strict_types=1);

if (!defined('vtBoolean')) {
    define('vtBoolean', 0);
    define('vtInteger', 1);
    define('vtFloat', 2);
    define('vtString', 3);
    define('vtArray', 8);
    define('vtObject', 9);
}

class BatteryWatcher extends IPSModule {

	public function Create() {
		//Never delete this line!
		parent::Create();

		//Properties
		$this->RegisterPropertyBoolean("Active", 0);
		$this->RegisterPropertyInteger("Time_To_Check", "18");
		$this->RegisterPropertyBoolean("Webfront_HTML", 0);
		$this->RegisterPropertyString("NotificationOKSubject","Symcon - Batterie Monitor"); 
		$this->RegisterPropertyString("NotificationOKText","Keine leeren Batterien gefunden"); 
		$this->RegisterPropertyString("NotificationErrorSubject","Symcon - Batterie Monitor"); 
		$this->RegisterPropertyString("NotificationErrorText","Es wurde mindestens eine schwache Batterie gefunden \n Leere Batterien:"); 
		$this->RegisterPropertyBoolean("NotifyByEmail", 0);
		$this->RegisterPropertyBoolean("NotifyByApp", 0);
		//$this->RegisterPropertyInteger("NotificationType", "0");
		$this->RegisterPropertyInteger("EmailVariable", 0);

		$this->RegisterPropertyInteger("ExecutionHour","18");
		$this->RegisterPropertyInteger("ExecutionMinute","00");
		$this->RegisterPropertyInteger("ExecutionInterval","3");

		//$this->RegisterPropertyBoolean("NotifyByApp", 0);
		$this->RegisterPropertyString("HTML_Header_Event","Keine Module mit leerer Batterie");

		$this->RegisterVariableBoolean("Warning",$this->Translate('Warning'),'~Alert');
		$this->RegisterVariableInteger("Devices_With_Empty_Battery",$this->Translate('Device with empty battery'));


		//$this->EnableAction("Active");

		//Component sets timer, but default is OFF
		$this->RegisterTimer("Battery Monitor",0,"BW_Check(\$_IPS['TARGET']);");

	}

	public function ApplyChanges() {

		//Never delete this line!
		parent::ApplyChanges();

		$vpos = 10;
		$this->MaintainVariable('Webfront_Message_Box', $this->Translate('Webfront Messagebox'), vtString, '~HTMLBox', $vpos++,$this->ReadPropertyBoolean('Webfront_HTML') == 1);

		$ActiveID= @IPS_GetObjectIDByIdent('Active', $this->InstanceID);
		if (IPS_GetObject($ActiveID)['ObjectType'] == 2) {
			$this->RegisterMessage($ActiveID, VM_UPDATE);
		}

		$this->SetResetTimerInterval();

	}

	public function Check() {

		$Profiles = array("~Battery" => true, "~Battery.Reversed" => false, "~Battery.100" => 25);
		
		$NotifyByApp = $this->ReadPropertyBoolean("NotifyByApp");
		$NotifyByEmail = $this->ReadPropertyBoolean("NotifyByEmail");
		$WarningVariableID =  $this->GetIDForIdent('Warning');
		

		$result = "";
		$resultemail = $this->ReadPropertyString("NotificationErrorText")." \n \n";
		$device_count = 0;

		$VariableIDs = IPS_GetVariableList();
		foreach($VariableIDs as $VariableID)
		{
			if ($VariableID != $WarningVariableID)
			{
				$variableData = IPS_GetVariable($VariableID);
				$value = GetValue($VariableID);
				$profileName = IPS_VariableProfileExists($variableData['VariableProfile']) ? $variableData['VariableProfile'] : "";
				$profileName = (strlen($variableData['VariableCustomProfile']) > 0 && IPS_VariableProfileExists($variableData['VariableCustomProfile'])) ? $variableData['VariableCustomProfile'] : $profileName;

				$warning = false;
				if (strlen($profileName) > 0)
				{
					foreach ($Profiles as $pName => $pValue)
					{
						if ($profileName == $pName)
						{
							if (is_bool($pValue))
							{
								if ($value == $pValue) { $warning = true; }
							}
							else
							{
								if ($value <= $pValue) { $warning = true; }
							}
							break;
						}
					}
				}

				if ($warning)
				{
					//$result .= "<em>".IPS_GetLocation($VariableID)."</em>: ".GetValueFormatted($VariableID)."<br />";
					$textColor = ($value < -100 ? '#B40404' : '#0B610B'); 
					$color  = ' style="background-color:#080808; color:#ffffff;"'; 
					$color2 = ' style="background-color:#080808; color:' . $textColor . ';"'; 
					//$result .= '<tr><td' . $color . '>' . IPS_GetLocation($VariableID) . '</td><td align="center"' . $color2 . '> ' . ($value == true ? 'Low Bat' : 'OK') . ' </td></tr>'; // </br>
					//$result .= '<tr><td' . $color . '>' . IPS_GetLocation($VariableID); // </br>
					$result .= '<tr><td' . $color . '>'.IPS_GetName(IPS_GetParent($VariableID))." ID: ".$VariableID; // </br>
					$resultemail .= IPS_GetName(IPS_GetParent($VariableID))." ID: ".$VariableID." \n";
					$device_count++;
				}
			}
		}

		if ($result == "") {
			$this->SendDebug("Battery Monitor","No empty batteries have been found.", 0);
			SetValueBoolean($WarningVariableID, false);
			SetValueInteger($this->GetIDForIdent('Devices_With_Empty_Battery'),"0");
		}
		else {
			$this->SendDebug("Battery Monitor","Devices with empty batteries have been detected.", 0);
			SetValueBoolean($WarningVariableID, true);
			SetValueInteger($this->GetIDForIdent('Devices_With_Empty_Battery'),$device_count);
			$result      = '<table><tr><td><b>Device</b></td><td><b>Value</b></td></tr>' . $result .'</table>'; 

			if ($this->ReadPropertyBoolean('Webfront_HTML') == 1) 
			{
				$Webfront_Message_BoxID = $this->GetIDForIdent('Webfront_Message_Box');
				SetValueString($Webfront_Message_BoxID, $result);
			}

			if ($NotifyByEmail == 1) 
			{
				if ($result == "") 
				{
					$this->SendDebug("Email","Will try to send email - All OK", 0);
					$this->SetBuffer("NotifierSubject",$this->ReadPropertyString("NotificationOKSubject"));
					$this->SetBuffer("NotifierMessage",$this->ReadPropertyString("NotificationOKText"));
					$this->EmailApp();
				}
				elseif ($result != "") 
				{
					$this->SendDebug("Email","Will try to send email - Empty Batterie", 0);
					$this->SetBuffer("NotifierSubject",$this->ReadPropertyString("NotificationErrorSubject"));
					$this->SetBuffer("NotifierMessage",$resultemail);
					$this->EmailApp();
				}
			}

			if ($NotifyByApp == 1) 
			{
				if ($result == "") 
				{
					$this->SendDebug("Email","Will try to send email - All OK", 0);
					$this->SetBuffer("NotifierSubject",$this->ReadPropertyString("NotificationOKText"));
					$this->SetBuffer("NotifierMessage","Nix gefunden");
					$this->NotifyApp();
				}
				elseif ($result != "") 
				{
					$this->SendDebug("Email","Will try to send email - Empty Batterie", 0);
					$this->SetBuffer("NotifierSubject",$this->ReadPropertyString("NotificationErrorText"));
					$this->SetBuffer("NotifierMessage",$resultemail);
					$this->NotifyApp();
				}
			}

		}

	}

	public function EmailApp() {
		$EmailVariable = $this->ReadPropertyInteger("EmailVariable");
		if ($EmailVariable != "") {
			$NotifierSubject = $this->GetBuffer("NotifierSubject");
			$NotifierMessage = $this->GetBuffer("NotifierMessage");
			$EmailTitle = $NotifierSubject;
			if ($NotifierMessage == "") {
				$NotifierMessage = "Test Message";
			}
			$this->SendDebug("Email","********** Email **********", 0);
			$this->SendDebug("Email","Message: ".$NotifierMessage." was sent", 0);
			SMTP_SendMail($EmailVariable, $EmailTitle, $NotifierMessage);
		}
		else {
			echo $this->Translate('Email Instance is not configured');
		}
	}

	public function NotifyApp() {
		$NotifierTitle = $this->GetBuffer("NotifierSubject");
		//$NotifierMessage = $this->GetBuffer("NotifierMessage");
		$NotifierMessage = "Check webfront for details";
		if ($NotifierMessage == "") {
			$NotifierMessage = "Test Message";
		}
		$WebFrontMobile = IPS_GetInstanceListByModuleID('{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}')[0];
		$this->SendDebug("Notifier","********** App Notifier **********", 0);
		$this->SendDebug("Notifier","Message: ".$NotifierMessage." was sent", 0);
		WFC_PushNotification($WebFrontMobile, $NotifierTitle, $NotifierMessage , "", 0);
	}

	public function SetResetTimerInterval() {
		$Active = $this->ReadPropertyBoolean("Active");

		if ($Active == 1) {
			$Hour = $this->ReadPropertyInteger("ExecutionHour");
			$Minute = $this->ReadPropertyInteger("ExecutionMinute");
			$ExecutionInterval = $this->ReadPropertyInteger("ExecutionInterval");
			$NewTime = $Hour.":".$Minute;
			$now = new DateTime();
			$target = new DateTime();
			if ($NewTime < date("H:i")) {
				$target->modify('+1 day');
			}
			if ($ExecutionInterval == 1) {
				$target->modify('+'.$ExecutionInterval.' day');
			}
			if ($ExecutionInterval > 1) {
				$target->modify('+'.$ExecutionInterval.' days');
			}
			$target->setTime($Hour, $Minute, 0);
			$diff = $target->getTimestamp() - $now->getTimestamp();
			$Timer = $diff * 1000;
			$this->SetTimerInterval('Battery Monitor', $Timer);
		}
		else if ($Active == 0) {
			$this->SetTimerInterval('Battery Monitor', 0);
		}
	} 

	/*

	public function MessageSink($TimeStamp, $SenderID, $Message, $Data)	{

		//$this->SendDebug("Sender",$SenderID." ".$Message." ".$Data, 0);

		$IP = $this->ReadPropertyString("IP");
		$UnreachCounter = $this->GetBuffer("UnreachCounter");

		if ($SenderID == $this->GetIDForIdent('Active')) {

			$SenderValue = GetValue($SenderID);
			if ($SenderValue == 1) {
				$this->SendDebug("System","Module activated", 0);
				$TimerMS = $this->ReadPropertyInteger("Timer") * 1000;
				$this->SetTimerInterval("WLAN BBQ Thermometer",$TimerMS);
				$this->SetBuffer("UnreachCounter",0);
				$this->GetReadings();
			}
			else {
				$this->SetTimerInterval("WLAN BBQ Thermometer", "0");
				$this->ArchiveCleaning();
				$this->UnsetValuesAtShutdown();
				$this->SendDebug("System","Switching module off", 0);
			}
		}
		else {

		}


	}
	
	public function RequestAction($Ident, $Value) {

		$this->SetValue($Ident, $Value);

	}

	*/

}