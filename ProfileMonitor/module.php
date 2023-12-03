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
		$this->RegisterPropertyBoolean("All_Variable_Output", 0);
		$this->RegisterPropertyString("Profiles2Monitor", '[{"ProfileName":"~Battery","ProfileValue":true},{"ProfileName":"~Battery.Reversed","ProfileValue":false},{"ProfileName":"~Battery.100","ProfileValue":"25"}]');
		$this->RegisterPropertyString("IDs2Ignore","");
		$this->RegisterPropertyString("HTMLBoxAktorName","Gerät");
		$this->RegisterPropertyString("HTMLBoxNothingFound","Keine Komponenten gefunden");
		$this->RegisterPropertyString("HTMLBoxParentTranslation","Ursprungsobjekt");
		$this->RegisterPropertyString("HTMLBoxLocationTranslation","Ort im Objektbaum");
		$this->RegisterPropertyString("HTMLBoxLastUpdateTranslation","Aktualisiert");
		$this->RegisterPropertyBoolean("HTMLBoxID",true);
		$this->RegisterPropertyBoolean("HTMLBoxValue",false);
		$this->RegisterPropertyBoolean("HTMLBoxParent",false);
		$this->RegisterPropertyBoolean("HTMLBoxLocation",false);
		$this->RegisterPropertyBoolean("HTMLBoxLastUpdate",false);
		$this->RegisterPropertyInteger("HTMLBoxCellPadding", "10");
		$this->RegisterPropertyString("HTMLBoxTextColor","ffffff");
		$this->RegisterPropertyBoolean("EmailID",true);
		$this->RegisterPropertyBoolean("EmailValue",false);
		$this->RegisterPropertyBoolean("EmailParent",false);
		$this->RegisterPropertyBoolean("EmailLocation",false);
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
		$this->RegisterPropertyInteger("NotificationOpen",0);
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
		$this->MaintainVariable('Webfront_Message_Box', $this->Translate('Webfront Messagebox'), vtString, '~HTMLBox', $vpos++,$this->ReadPropertyBoolean('Webfront_HTML') == true);
		$this->MaintainVariable('Profile_Monitor_RAW', $this->Translate('Profile Monitor JSON'), vtString, '', $vpos++,$this->ReadPropertyBoolean('Variable_Output') == true);
		$this->MaintainVariable('Profile_Monitor_AllCheckedVariables', $this->Translate('Profile Monitor all checked variable JSON'), vtString, '', $vpos++,$this->ReadPropertyBoolean('All_Variable_Output') == true);
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
		$checked_variable_json = "";
		//$resultemail = '<html><body>';
		$resultemail = $this->ReadPropertyString("NotificationErrorText")." \n \n";
		$device_count = 0;

		$this->SendDebug("","", 0);
		$this->SendDebug("Profile Monitor","********** Checking **********", 0);
		//var_dump($Profiles);

		$VariableIDs = IPS_GetVariableList();
		foreach($VariableIDs as $VariableID) {
			if ($VariableID != $WarningVariableID) {
				if ($checked_variable_json == null) {
					$checked_variable_json .= "[";
				}

				$variableData = IPS_GetVariable($VariableID);
				$value = GetValue($VariableID);
				$profileName = IPS_VariableProfileExists($variableData['VariableProfile']) ? $variableData['VariableProfile'] : "";
				$profileName = (strlen($variableData['VariableCustomProfile']) > 0 && IPS_VariableProfileExists($variableData['VariableCustomProfile'])) ? $variableData['VariableCustomProfile'] : $profileName;
				$warning = false;
				if (strlen($profileName) > 0) {
					foreach ($Profiles as $pName => $pValue)
					{
						if ($profileName == $pName)	{
							$checked_variable_json .= $VariableID.',';
							//if (is_bool($pValue)) {
							if (IPS_GetVariable($VariableID)["VariableType"] == "0") {
								if ($value == $pValue) {
									if (json_decode($IDs2Ignore,true) != null) {
										$IgnoreIDArray = [];
										foreach (json_decode($IDs2Ignore,true) as $IgnoreID) {
											array_push($IgnoreIDArray, $IgnoreID["ID2Ignore"]);
										} 												
										if (!in_array($VariableID, $IgnoreIDArray)) { 
											$this->SendDebug("Warning Match","Variable: ".$VariableID." is giving a warning MATCHING and is not ignored.", 0);
											$warning = true;
										} 
										elseif (in_array($VariableID, $IgnoreIDArray)) { 
											$this->SendDebug("Warning Ignored","Variable: ".$VariableID." is giving a warning MATCHING and is IGNORED.", 0);
										} 								
									}
									else {
										//var_dump($VariableID);
										$this->SendDebug("Warning Match","Variable: ".$VariableID." is giving a warning MATCHING and is not ignored.", 0);
										$warning = true;
									}
								}
							}
							else {
								//if (IPS_GetVariable($VariableID)["VariableType"] == "1" OR IPS_GetVariable($VariableID)["VariableType"] == "2") {
									if ($value <= $pValue) {
										if (json_decode($IDs2Ignore,true) != null) {
											$IgnoreIDArray = [];
											foreach (json_decode($IDs2Ignore,true) as $IgnoreID) {
												array_push($IgnoreIDArray, $IgnoreID["ID2Ignore"]);
											} 												
											if (!in_array($VariableID, $IgnoreIDArray)) { 
												$this->SendDebug("Warning Match","Variable: ".$VariableID." is giving a warning being LESS OR EQUAL and is not ignored.", 0);
												$warning = true;
											} 
											elseif (in_array($VariableID, $IgnoreIDArray)) { 
												$this->SendDebug("Warning Ignored","Variable: ".$VariableID." is giving a warning being LESS OR EQUAL and is IGNORED.", 0);
											} 
										}
										else {
											//var_dump($VariableID);
											$this->SendDebug("Warning Match","Variable: ".$VariableID." is giving a warning being LESS OR EQUAL and is not ignored.", 0);
											$warning = true;
										}
									}
								//}
							}
							break;
						}
					}
				}

				if ($warning) {
					//$textColor = ($value < -100 ? '#B40404' : '#0B610B'); 
					//$color  = ' style="background-color:'.$this->ReadPropertyString("HTMLBoxBackgroundColor").'; color:'.$this->ReadPropertyString("HTMLBoxTextColor").';"'; 
					//$color2 = ' style="background-color:#080808; color:' . $textColor . ';"'; 

					$result .= '<tr><td>'.IPS_GetName($VariableID).'</td>';
					if ($this->ReadPropertyBoolean("HTMLBoxID")) {      
						$result .= '<td style="padding-left: '.$this->ReadPropertyInteger("HTMLBoxCellPadding").'px">'.$VariableID.'</td>'; // </br>
					}
					if ($this->ReadPropertyBoolean("HTMLBoxValue")) {
						$result .= '<td style="padding-left: '.$this->ReadPropertyInteger("HTMLBoxCellPadding").'px">'.GetValueFormatted($VariableID).'</td>';
					}
					if ($this->ReadPropertyBoolean("HTMLBoxParent")) {
						$result .= '<td style="padding-left: '.$this->ReadPropertyInteger("HTMLBoxCellPadding").'px">'.IPS_GetName(IPS_GetParent($VariableID)).'</td>'; // </br>
					}
					if ($this->ReadPropertyBoolean("HTMLBoxLocation")) {
						$result .= '<td style="padding-left: '.$this->ReadPropertyInteger("HTMLBoxCellPadding").'px">'.IPS_GetLocation($VariableID).'</td>'; // </br>
					}
					if ($this->ReadPropertyBoolean("HTMLBoxLastUpdate")) {
						$result .= '<td style="padding-left: '.$this->ReadPropertyInteger("HTMLBoxCellPadding").'px">'.date("Y-m-d H:i:s", IPS_GetVariable($VariableID)["VariableChanged"]).'</td>'; // </br>
					}

					if ($this->ReadPropertyBoolean("EmailValue")) {
						$resultemail .= " Name: ".IPS_GetName($VariableID);
					}
					if ($this->ReadPropertyBoolean("EmailParent")) {
						$resultemail .= " | Eltern Objekt: ".IPS_GetName(IPS_GetParent($VariableID));
					}
					if ($this->ReadPropertyBoolean("EmailID")) {      
						$resultemail .= " | ID: ".$VariableID;
					}	
					if ($this->ReadPropertyBoolean("EmailLocation")) {
						$resultemail .= " | Ort im Objektbaum: ".IPS_GetLocation($VariableID);
					}
					$resultemail .= " \n";

					//$resultemail .= IPS_GetName($VariableID)." ID: ".$VariableID." \n";
					$device_count++;

					if ($result_json == null) {
						$result_json .= "[";
					}

					$result_json .= $VariableID.',';
				}
			}
		}

		if ($result == "") {
			$this->SendDebug("Battery Monitor","No empty batteries have been found.", 0);
			SetValueBoolean($WarningVariableID, false);
			$this->SetValue('Devices_With_Empty_Battery', "0");
			//$HTMLBox      = '<table><tr><th><b>'.$this->ReadPropertyString("HTMLBoxAktorName").'</b></th></tr><tr><td>'.$this->ReadPropertyString("HTMLBoxNothingFound").'</td></tr></table>'; 
			$HTMLBox      = '<table><tr><th><b>'.$this->ReadPropertyString("HTMLBoxNothingFound").'</b></th></tr></table>'; 
			//var_dump($HTMLBox);
			if ($this->ReadPropertyBoolean('Webfront_HTML') == true) {
                $Webfront_Message_BoxID = $this->GetIDForIdent('Webfront_Message_Box');
				SetValueString($Webfront_Message_BoxID, $HTMLBox);
            }

			if ($this->ReadPropertyBoolean('Variable_Output') == true)	{
				$this->SetValue('Profile_Monitor_RAW', "[]");
			}
			if ($this->ReadPropertyBoolean('All_Variable_Output') == true)	{
				$checked_variable_json = rtrim($checked_variable_json, ',');
				$this->SetValue('Profile_Monitor_AllCheckedVariables', $checked_variable_json."]");
			}
		}
		else {
			$this->SendDebug("Battery Monitor","Devices with empty batteries have been detected.", 0);
			SetValueBoolean($WarningVariableID, true);
			$this->SetValue('Devices_With_Empty_Battery', $device_count);
			
			$HTMLBox = '<table><tr><td><b>'.$this->ReadPropertyString("HTMLBoxAktorName").'</b></td>';
			if ($this->ReadPropertyBoolean("HTMLBoxID")) {
				$HTMLBox .= '<td style="padding-left: '.$this->ReadPropertyInteger("HTMLBoxCellPadding").'px"><b>ID</b></td>';
			}

			if ($this->ReadPropertyBoolean("HTMLBoxValue")) {
				$HTMLBox .= '<td style="padding-left: '.$this->ReadPropertyInteger("HTMLBoxCellPadding").'px"><b>Value</b></td>';
			}

			if ($this->ReadPropertyBoolean("HTMLBoxParent")) {
				$HTMLBox .= '<td style="padding-left: '.$this->ReadPropertyInteger("HTMLBoxCellPadding").'px"><b>'.$this->ReadPropertyString("HTMLBoxParentTranslation").'</b></td>';
			}
			if ($this->ReadPropertyBoolean("HTMLBoxLocation")) {
				$HTMLBox .= '<td style="padding-left: '.$this->ReadPropertyInteger("HTMLBoxCellPadding").'px"><b>'.$this->ReadPropertyString("HTMLBoxLocationTranslation").'</b></td>';
			}
			if ($this->ReadPropertyBoolean("HTMLBoxLastUpdate")) {
				$HTMLBox .= '<td style="padding-left: '.$this->ReadPropertyInteger("HTMLBoxCellPadding").'px"><b>'.$this->ReadPropertyString("HTMLBoxLastUpdateTranslation").'</b></td>';
			}
			$HTMLBox .= '</tr>'.$result.'</table>';
			
			if ($this->ReadPropertyBoolean('Webfront_HTML') == true) {
				$Webfront_Message_BoxID = $this->GetIDForIdent('Webfront_Message_Box');
				SetValueString($Webfront_Message_BoxID, $HTMLBox);
			}

			if ($this->ReadPropertyBoolean('Variable_Output') == true)	{
				$result_json = rtrim($result_json, ',');
				$this->SetValue('Profile_Monitor_RAW', $result_json."]");
			}

			if ($this->ReadPropertyBoolean('All_Variable_Output') == true)	{
				$checked_variable_json = rtrim($checked_variable_json, ',');
				$this->SetValue('Profile_Monitor_AllCheckedVariables', $checked_variable_json."]");
			}


			if ($NotifyByEmail == true) {
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

			if ($NotifyByApp == true) {
				if ($result == "") {
					$this->SendDebug("App-Message","Will try to send app notification - All OK", 0);
					$this->SetBuffer("NotifierSubject",$this->ReadPropertyString("NotificationOKSubject"));
					$this->SetBuffer("NotifierMessage",$this->ReadPropertyString("NotificationOKTextApp"));
					$this->NotifyApp();
				}
				elseif ($result != "") {
					$this->SendDebug("App-Message","Will try to send app notification - Empty Batterie", 0);
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
		
		//if ($this->ReadPropertyInteger('NotificationOpen' !== 0)) {
		if (empty($this->ReadPropertyInteger('NotificationOpen'))) {
			$NotificationID = 0;
		} else {
			$NotificationID = $this->ReadPropertyInteger('NotificationOpen');
		}
		
		
		if ($WebfrontVariable != "") {
			$NotifierTitle = $this->GetBuffer("NotifierSubject");
			$NotifierMessage = $this->GetBuffer("NotifierMessage");
			if ($NotifierMessage == "") {
				$NotifierMessage = "Test Message";
			}
			if (IPS_GetInstanceListByModuleID('{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}') != NULL) {
				$WebFrontMobile = IPS_GetInstanceListByModuleID('{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}')[0];
				WFC_PushNotification($WebFrontMobile, $NotifierTitle, $NotifierMessage , "", 0);
			}
			if (IPS_GetInstanceListByModuleID('{B5B875BB-9B76-45FD-4E67-2607E45B3AC4}') != NULL) {
				$TileVisu = IPS_GetInstanceListByModuleID('{B5B875BB-9B76-45FD-4E67-2607E45B3AC4}')[0];
				VISU_PostNotification($TileVisu, $NotifierTitle, $NotifierMessage , "Info", $NotificationID);
			}	
			$this->SendDebug("Notifier","********** App Notifier **********", 0);
			$this->SendDebug("Notifier","Message: ".$NotifierMessage." was sent", 0);
		}
		else {
			echo $this->Translate('Webfront Instance is not configured');
		}
	}

	public function SetResetTimerInterval() {
		$Active = $this->ReadPropertyBoolean("Active");

		if ($Active == true) {
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
		else if ($Active == false) {
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
