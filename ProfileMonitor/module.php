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

class ProfileMonitor extends IPSModule {

	public function Create() {
		//Never delete this line!
		parent::Create();

		//Properties
		$this->RegisterPropertyBoolean("Active", 0);
		$this->RegisterPropertyInteger("Time_To_Check", "18");
		$this->RegisterPropertyInteger("TimerMethod", "0");
		$this->RegisterPropertyInteger("ExecutionMinuteMinute", "10");
		$this->RegisterPropertyBoolean("Webfront_HTML", 0);
		$this->RegisterPropertyBoolean("Variable_Output", 0);
		$this->RegisterPropertyString("Profiles2Monitor", '[{"ProfileName":"~Battery","ProfileValue":true},{"ProfileName":"~Battery.Reversed","ProfileValue":false},{"ProfileName":"~Battery.100","ProfileValue":"25"}]');
		$this->RegisterPropertyString("IDs2Ignore","");
		$this->RegisterPropertyString("HTMLBoxAktorName","Gerät");
		$this->RegisterPropertyString("HTMLBoxNothingFound","Keine Komponenten gefunden");
		$this->RegisterPropertyString("HTMLBoxParentTranslation","Ursprungsobjekt");
		$this->RegisterPropertyString("HTMLBoxLocationTranslation","Ort im Objektbaum");
		$this->RegisterPropertyBoolean("HTMLBoxID",true);
		$this->RegisterPropertyBoolean("HTMLBoxParent",false);
		$this->RegisterPropertyBoolean("HTMLBoxLocation",false);
		$this->RegisterPropertyString("HTMLBoxTextColor","ffffff");
		$this->RegisterPropertyString("HTMLBoxBackgroundColor","080808");
		$this->RegisterPropertyString("NotificationOKSubject","Symcon Batterie Monitor"); 
		$this->RegisterPropertyString("NotificationOKText","Keine leeren Batterien gefunden"); 
		$this->RegisterPropertyString("NotificationOKTextApp","Keine leeren Batterien gefunden"); 
		$this->RegisterPropertyString("NotificationErrorSubject","Symcon Batterie Monitor"); 
		$this->RegisterPropertyString("NotificationErrorText","Es wurde mindestens eine schwache Batterie gefunden \n Leere Batterien:"); 
		$this->RegisterPropertyString("NotificationErrorTextApp","Es wurde mindestens eine schwache Batterie gefunden");
		$this->RegisterPropertyBoolean("NotifyByEmail", 0);
		$this->RegisterPropertyBoolean("NotifyByApp", 0);
		$this->RegisterPropertyInteger("EmailVariable", 0);
		$this->RegisterPropertyInteger("WebfrontVariable", 0);
		$this->RegisterPropertyInteger("ExecutionHour","18");
		$this->RegisterPropertyInteger("ExecutionMinute","00");
		$this->RegisterPropertyInteger("ExecutionInterval","3");
		$this->RegisterPropertyString("HTML_Header_Event","Keine Module mit leerer Batterie");

		$this->RegisterVariableString("LastUpdate",$this->Translate('Last Update'));
		$this->RegisterVariableBoolean("Warning",$this->Translate('Warning'),'~Alert');
		$this->RegisterVariableBoolean("RemoteTrigger",$this->Translate('Remote Trigger'),'~Switch');
		$this->RegisterVariableInteger("Devices_With_Empty_Battery",$this->Translate('Device with empty battery'));
		$this->RegisterMessage(IPS_GetObjectIDByIdent("RemoteTrigger", $this->InstanceID), VM_UPDATE);

		$this->EnableAction("RemoteTrigger");	

		$this->RegisterTimer("Profile Monitor",0,"BW_Check(\$_IPS['TARGET']);");

	}

	public function ApplyChanges() {
		//Never delete this line!
		parent::ApplyChanges();

		$vpos = 10;
		$this->MaintainVariable('Webfront_Message_Box', $this->Translate('Webfront Messagebox'), vtString, '~HTMLBox', $vpos++,$this->ReadPropertyBoolean('Webfront_HTML') == 1);
		$this->MaintainVariable('Profile_Monitor_RAW', $this->Translate('Profile Monitor JSON'), vtString, '', $vpos++,$this->ReadPropertyBoolean('Variable_Output') == 1);
		$this->SetResetTimerInterval();

	}

	public function Check() {

		SetValueString($this->GetIDForIdent("LastUpdate"), date("Y-m-d H:i:s"));
		
		$NotifyByApp = $this->ReadPropertyBoolean("NotifyByApp");
		$NotifyByEmail = $this->ReadPropertyBoolean("NotifyByEmail");
		$WarningVariableID =  $this->GetIDForIdent('Warning');
		$Profiles2Monitor = $this->ReadPropertyString("Profiles2Monitor");
		$IDs2Ignore = $this->ReadPropertyString("IDs2Ignore");

		$result_array = array();
		foreach(json_decode($Profiles2Monitor,true) as $sub_array) {
			$result_array[$sub_array["ProfileName"]] = $sub_array["ProfileValue"];
		}

		$Profiles = $result_array;

		$result = "";
		$result_json = "";
		$resultemail = $this->ReadPropertyString("NotificationErrorText")." \n \n";
		$device_count = 0;

		$VariableIDs = IPS_GetVariableList();
		foreach($VariableIDs as $VariableID) {
			if ($VariableID != $WarningVariableID) {
				$variableData = IPS_GetVariable($VariableID);
				$value = GetValue($VariableID);
				$profileName = IPS_VariableProfileExists($variableData['VariableProfile']) ? $variableData['VariableProfile'] : "";
				$profileName = (strlen($variableData['VariableCustomProfile']) > 0 && IPS_VariableProfileExists($variableData['VariableCustomProfile'])) ? $variableData['VariableCustomProfile'] : $profileName;

				$warning = false;
				if (strlen($profileName) > 0) {
					foreach ($Profiles as $pName => $pValue)
					{
						if ($profileName == $pName)	{
							if (is_bool($pValue)) {
								if ($value == $pValue) {
									
									if (json_decode($IDs2Ignore,true) != null) {
										foreach (json_decode($IDs2Ignore,true) as $IgnoreID) {
											$IgnoreID = $IgnoreID["ID2Ignore"];
											if ($VariableID != $IgnoreID) { 
												$warning = true;
											} 
										}
									}
									else {
										//var_dump($VariableID);
										$warning = true;
									}
								}
							}
							else {
								if ($value <= $pValue) {
									//foreach (json_decode($IDs2Ignore,true) as $IgnoreID) {
									if (json_decode($IDs2Ignore,true) != null) {
										foreach (json_decode($IDs2Ignore,true) as $IgnoreID) {
											$IgnoreID = $IgnoreID["ID2Ignore"];
											if ($VariableID != $IgnoreID) { 
												$warning = true;
											} 
										}
									}
									else {
										//var_dump($VariableID);
										$warning = true;
									}
								}
							}
							break;
						}
					}
				}

				if ($warning) {
					//$result .= "<em>".IPS_GetLocation($VariableID)."</em>: ".GetValueFormatted($VariableID)."<br />";
					$textColor = ($value < -100 ? '#B40404' : '#0B610B'); 
					$color  = ' style="background-color:'.$this->ReadPropertyString("HTMLBoxBackgroundColor").'; color:'.$this->ReadPropertyString("HTMLBoxTextColor").';"'; 
					$color2 = ' style="background-color:#080808; color:' . $textColor . ';"'; 
					//$result .= '<tr><td' . $color . '>' . IPS_GetLocation($VariableID) . '</td><td align="center"' . $color2 . '> ' . ($value == true ? 'Low Bat' : 'OK') . ' </td></tr>'; // </br>
					//$result .= '<tr><td' . $color . '>' . IPS_GetLocation($VariableID); // </br>
					
					//$result .= '<tr><td' . $color . '>'.IPS_GetName($VariableID).'</td><td' . $color . '>'.$VariableID.'</td>'; // </br> HTMLBoxParent
					
					$result .= '<tr><td' . $color . '>'.IPS_GetName($VariableID).'</td>';
					if ($this->ReadPropertyBoolean("HTMLBoxID")) {
						$result .= '<td' . $color . '>'.$VariableID.'</td>'; // </br>
					}
					if ($this->ReadPropertyBoolean("HTMLBoxParent")) {
						$result .= '<td' . $color . '>'.IPS_GetName(IPS_GetParent($VariableID)).'</td>'; // </br>
					}
					if ($this->ReadPropertyBoolean("HTMLBoxLocation")) {
						$result .= '<td' . $color . '>'.IPS_GetLocation($VariableID).'</td>'; // </br>
					}

					$resultemail .= IPS_GetName($VariableID)." ID: ".$VariableID." \n";
					$device_count++;

					if ($result_json == null) {
						$result_json .= "{";
					}

					$result_json .= '"'.IPS_GetName($VariableID).'": "'.$VariableID.'",';
				}
			}
		}

		if ($result == "") {
			$this->SendDebug("Battery Monitor","No empty batteries have been found.", 0);
			SetValueBoolean($WarningVariableID, false);
			SetValueInteger($this->GetIDForIdent('Devices_With_Empty_Battery'),"0");
			$HTMLBox      = '<table><tr><th><b>'.$this->ReadPropertyString("HTMLBoxAktorName").'</b></th></tr><tr><td>'.$this->ReadPropertyString("HTMLBoxNothingFound").'</td></tr></table>'; 
			$Webfront_Message_BoxID = $this->GetIDForIdent('Webfront_Message_Box');

			if ($this->ReadPropertyBoolean('Webfront_HTML') == 1) {
				SetValueString($Webfront_Message_BoxID, $result);
			}

			if ($this->ReadPropertyBoolean('Variable_Output') == 1)	{
				$Profile_Monitor_RAW = $this->GetIDForIdent('Profile_Monitor_RAW');
				SetValueString($Profile_Monitor_RAW, "{}");
			}
		}
		else {
			$this->SendDebug("Battery Monitor","Devices with empty batteries have been detected.", 0);
			SetValueBoolean($WarningVariableID, true);
			SetValueInteger($this->GetIDForIdent('Devices_With_Empty_Battery'),$device_count);
			
			$HTMLBox = '<table><tr><td><b>'.$this->ReadPropertyString("HTMLBoxAktorName").'</b></td>';
			if ($this->ReadPropertyBoolean("HTMLBoxID")) {
				$HTMLBox .= '<td><b>ID</b></td>'; // </br>
			}
			if ($this->ReadPropertyBoolean("HTMLBoxParent")) {
				$HTMLBox .= '<td><b>'.$this->ReadPropertyString("HTMLBoxParentTranslation").'</b></td>'; // </br>
			}
			if ($this->ReadPropertyBoolean("HTMLBoxLocation")) {
				$HTMLBox .= '<td><b>'.$this->ReadPropertyString("HTMLBoxLocationTranslation").'</b></td>'; // </br>
			}
			$HTMLBox .= '</tr>'.$result.'</table>';
			
			if ($this->ReadPropertyBoolean('Webfront_HTML') == 1) {
				$Webfront_Message_BoxID = $this->GetIDForIdent('Webfront_Message_Box');
				SetValueString($Webfront_Message_BoxID, $HTMLBox);
			}

			if ($this->ReadPropertyBoolean('Variable_Output') == 1)	{
				$Profile_Monitor_RAW = $this->GetIDForIdent('Profile_Monitor_RAW');

				$result_json = rtrim($result_json, ',');
				SetValueString($Profile_Monitor_RAW, $result_json."}");
			}


			if ($NotifyByEmail == 1) {
				if ($result == "") {
					$this->SendDebug("Email","Will try to send email - All OK", 0);
					$this->SetBuffer("NotifierSubject",$this->ReadPropertyString("NotificationOKSubject"));
					$this->SetBuffer("NotifierMessage",$this->ReadPropertyString("NotificationOKText"));
					$this->EmailApp();
				}
				elseif ($result != "") {
					$this->SendDebug("Email","Will try to send email - Empty Batterie", 0);
					$this->SetBuffer("NotifierSubject",$this->ReadPropertyString("NotificationErrorSubject"));
					$this->SetBuffer("NotifierMessage",$resultemail);
					$this->EmailApp();
				}
			}

			if ($NotifyByApp == 1) {
				if ($result == "") {
					$this->SendDebug("Email","Will try to send email - All OK", 0);
					$this->SetBuffer("NotifierSubject",$this->ReadPropertyString("NotificationOKSubject"));
					$this->SetBuffer("NotifierMessage",$this->ReadPropertyString("NotificationOKTextApp"));
					$this->NotifyApp();
				}
				elseif ($result != "") {
					$this->SendDebug("Email","Will try to send email - Empty Batterie", 0);
					$this->SetBuffer("NotifierSubject",$this->ReadPropertyString("NotificationErrorSubject"));
					$this->SetBuffer("NotifierMessage",$this->ReadPropertyString("NotificationErrorTextApp"));
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
		$WebfrontVariable = $this->ReadPropertyInteger("WebfrontVariable");
		
		if ($WebfrontVariable != "") {
			$NotifierTitle = $this->GetBuffer("NotifierSubject");
			$NotifierMessage = $this->GetBuffer("NotifierMessage");
			if ($NotifierMessage == "") {
				$NotifierMessage = "Test Message";
			}
			$WebFrontMobile = IPS_GetInstanceListByModuleID('{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}')[0];
			$this->SendDebug("Notifier","********** App Notifier **********", 0);
			$this->SendDebug("Notifier","Message: ".$NotifierMessage." was sent", 0);
			WFC_PushNotification($WebFrontMobile, $NotifierTitle, $NotifierMessage , "", 0);
		}
		else {
			echo $this->Translate('Webfront Instance is not configured');
		}
	}

	public function SetResetTimerInterval() {
		$Active = $this->ReadPropertyBoolean("Active");

		if ($Active == 1) {
			$this->SetStatus(102);
			if ($this->ReadPropertyInteger("TimerMethod") == 0) {
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
				$this->SetTimerInterval('Profile Monitor', $Timer);
			}
			else if ($this->ReadPropertyInteger("TimerMethod") == 1) {
				$Minute = $this->ReadPropertyInteger("ExecutionMinuteMinute");
				$Timer = $Minute * 60000;
				$this->SetTimerInterval('Profile Monitor', $Timer);
			}
		}
		else if ($Active == 0) {
			$this->SetStatus(104);
			$this->SetTimerInterval('Profile Monitor', 0);
		}
	} 

	public function MessageSink($TimeStamp, $SenderID, $Message, $Data)	{
		//$this->SendDebug("Sender",$SenderID." ".$Message." ".$Data, 0);
		if ($SenderID == $this->GetIDForIdent('RemoteTrigger') AND GetValue($SenderID) == true) {
			SetValueBoolean($this->GetIDForIdent('RemoteTrigger'),false);
			$this->Check();
		}
	}
	
	public function RequestAction($Ident, $Value) {
		$this->SetValue($Ident, $Value);
	}

}