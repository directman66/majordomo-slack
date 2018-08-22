<?php
/**
* notifyevents
* @package project
* @author Wizard <sannikovdi@yandex.ru>
* @copyright http://majordomo.smartliving.ru/ (c)
* @version 0.1 (wizard, 13:03:10 [Mar 13, 2016])
*/
//
//
class slack extends module {
/**
*
* Module class constructor
*
* @access private
*/
function slack() {
  $this->name="slack";
  $this->title="slack.com";
  $this->module_category="<#LANG_SECTION_APPLICATIONS#>";
  $this->checkInstalled();
}
/**
* saveParams
*
* Saving module parameters
*
* @access public
*/
function saveParams($data=0) {
 $p=array();
 if (IsSet($this->id)) {
  $p["id"]=$this->id;
 }
 if (IsSet($this->view_mode)) {
  $p["view_mode"]=$this->view_mode;
 }
 if (IsSet($this->edit_mode)) {
  $p["edit_mode"]=$this->edit_mode;
 }
 if (IsSet($this->tab)) {
  $p["tab"]=$this->tab;
 }
 return parent::saveParams($p);
}
/**
* getParams
*
* Getting module parameters from query string
*
* @access public
*/
function getParams() {
  global $id;
  global $mode;
  global $view_mode;
  global $edit_mode;
  global $tab;
  if (isset($id)) {
   $this->id=$id;
  }
  if (isset($mode)) {
   $this->mode=$mode;
  }
  if (isset($view_mode)) {
   $this->view_mode=$view_mode;
  }
  if (isset($edit_mode)) {
   $this->edit_mode=$edit_mode;
  }
  if (isset($tab)) {
   $this->tab=$tab;
  }
}
/**
* Run
*
* Description
*
* @access public
*/
function run() {
 global $session;
  $out=array();
  if ($this->action=='admin') {
   $this->admin($out);
  } else {
   $this->usual($out);
  }
  if (IsSet($this->owner->action)) {
   $out['PARENT_ACTION']=$this->owner->action;
  }
  if (IsSet($this->owner->name)) {
   $out['PARENT_NAME']=$this->owner->name;
  }
  $out['VIEW_MODE']=$this->view_mode;
  $out['EDIT_MODE']=$this->edit_mode;
  $out['MODE']=$this->mode;
  $out['ACTION']=$this->action;
  $out['TAB']=$this->tab;
  $this->data=$out;
  $this->checkSettings();


  $p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
  $this->result=$p->result;
}
/**
* BackEnd
*
* Module backend
*
* @access public
*/
function admin(&$out) {
 $this->getConfig();
 $out['ACCESS_KEY']=$this->config['ACCESS_KEY'];
 $out['DISABLED']=$this->config['DISABLED'];
 $out['SLACK_TEST']=SETTINGS_SLACK_APIURL;	

 if ($this->view_mode=='update_settings') {
   global $access_key;
//   $this->config['ACCESS_KEY']=$access_key;
 	global $speaker;
//   $this->config['SPEAKER']=$speaker;
	global $emotion;
//   $this->config['EMOTION']=$emotion;
   global $disabled;
//   $this->config['DISABLED']=$disabled;
   $this->saveConfig();
   $this->redirect("?ok=1");
 }

 if ($_GET['ok']) {
  $out['OK']=1;
 }
 

}

/**
* FrontEnd
*
* Module frontend
*
* @access public
*/
function usual(&$out) {
 $this->admin($out);
}
function checkSettings() {

 // Здесь задаются нужные нам параметры - пример взят из календаря, как раз есть текстбокс и радиобуттон 
  $settings=array(
   array(
    'NAME'=>'SLACK_APIURL', 
    'TITLE'=>'Incoming Webhook API Url: (*)', 
    'TYPE'=>'text',
    'DEFAULT'=>'https://hooks.slack.com/services/xxxx/'
    ),
   array(	  
'NAME'=>'SLACK_MSGLEVEL', 
    'TITLE'=>'MSGLEVEL', 
    'TYPE'=>'text',
    'DEFAULT'=>'0'
    ),	  

   array(
    'NAME'=>'SLACK_ENABLE', 
    'TITLE'=>'Enable',
    'TYPE'=>'yesno',
    'DEFAULT'=>'1'
    )


   );


   foreach($settings as $k=>$v) {
    $rec=SQLSelectOne("SELECT ID FROM settings WHERE NAME='".$v['NAME']."'");
    if (!$rec['ID']) {
     $rec['NAME']=$v['NAME'];
     $rec['VALUE']=$v['DEFAULT'];
     $rec['DEFAULTVALUE']=$v['DEFAULT'];
     $rec['TITLE']=$v['TITLE'];
     $rec['TYPE']=$v['TYPE'];
     $rec['DATA']=$v['DATA'];
     $rec['ID']=SQLInsert('settings', $rec);
     Define('SETTINGS_'.$rec['NAME'], $v['DEFAULT']);
    }
   }

 	
 
	
	
}
	
 function processSubscription($event, &$details) {
  $this->getConfig();

	$disabled=SETTINGS_SLACK_ENABLE;
	$mgslevel=SETTINGS_SLACK_MSGLEVEL;
	  if ((int)$mgslevel >= (int)getGlobal('minMsgLevel') && $event=='SAY' && $disabled && !$details['ignoreVoice']) {
//	  if ( $event=='SAY' && $disabled && !$details['ignoreVoice']) {
//	   $message=$mgslevel.':'.getGlobal('minMsgLevel').':'.$details['message'];
	   $message=$details['message'];

$url = SETTINGS_SLACK_APIURL;
$text=$message;

 define('SLACK_WEBHOOK', $url); // это не забудьте поменять на свое
  $message = array('payload' => json_encode(array('text' => $text)));
  $c = curl_init(SLACK_WEBHOOK);
  curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($c, CURLOPT_POST, true);
  curl_setopt($c, CURLOPT_POSTFIELDS, $message);
  curl_exec($c);
  curl_close($c);
 }

}

 function sendMessageToAll($message) {

$url = SETTINGS_SLACK_APIURL;
$text=$message;
 define('SLACK_WEBHOOK', $url); // это не забудьте поменять на свое
  $message = array('payload' => json_encode(array('text' => $text)));
  $c = curl_init(SLACK_WEBHOOK);
  curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($c, CURLOPT_POST, true);
  curl_setopt($c, CURLOPT_POSTFIELDS, $message);
  curl_exec($c);
  curl_close($c);

}

 function sendImageToAll($path, $text) {

$url = SETTINGS_SLACK_APIURL;
 define('SLACK_WEBHOOK', $url); // это не забудьте поменять на свое
//  $message = array('payload' => json_encode(array('text' => $text,   "image_url"=> $path)));
/*
$json='"attachments": [
        {
            "fallback": "Required plain-text summary of the attachment.",
            "color": "#36a64f",
            "pretext": "Optional text that appears above the attachment block",
            "author_name": "Bobby Tables",
            "author_link": "http://flickr.com/bobby/",
            "author_icon": "http://flickr.com/icons/bobby.jpg",
            "title": "Slack API Documentation",
            "title_link": "https://api.slack.com/",
            "text": "Optional text that appears within the attachment",
            "fields": [
                {
                    "title": "Priority",
                    "value": "High",
                    "short": false
                }
            ],
            "image_url": '.$path.',
            "thumb_url": "http://example.com/path/to/thumb.png",
            "footer": "Slack API",
            "footer_icon": "https://platform.slack-edge.com/img/default_application_icon.png",
            "ts": 123456789
        }
    ]  ';
// $message = array('payload' => $json);
 $message = $json;


$json='{
    "attachments": [
        {
            "fallback": "Required plain-text summary of the attachment.",
            "text": "Optional text that appears within the attachment",
            "image_url": '.$path.'
        }
    ]
}';

$message = array('payload' => $json);
*/

$message = '
{
    "text": "'.$text.'",
    "attachments": [
        {
            "fallback": "Required plain-text summary of the attachment.",
            "text": "Optional text that appears within the attachment",
            "image_url": "'.$path.'"
        }
    ]
}';
//sg('test.json2',$message);

  $c = curl_init(SLACK_WEBHOOK);
  curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($c, CURLOPT_POST, true);
  curl_setopt($c, CURLOPT_POSTFIELDS, $message);
  curl_exec($c);
  curl_close($c);

}

 function sendButtonToAll($path, $message) {

$url = SETTINGS_SLACK_APIURL;
$text=$message;
 define('SLACK_WEBHOOK', $url); // это не забудьте поменять на свое

$message = '
{
    "text": "Robert DeSoto added a new task",
    "attachments": [
        {
            "fallback": "Plan a vacation",
            "author_name": "Owner: rdesoto",
            "title": "Plan a vacation",
            "text": "Ive been working too hard, its time for a break.",
            "actions": [
                {
                    "name": "action",
                    "type": "button",
                    "text": "Complete this task",
                    "style": "",
                    "value": "complete"
                },
                {
                    "name": "tags_list",
                    "type": "select",
                    "text": "Add a tag...",
                    "data_source": "static",
                    "options": [
                        {
                            "text": "Launch Blocking",
                            "value": "launch-blocking"
                        },
                        {
                            "text": "Enhancement",
                            "value": "enhancement"
                        },
                        {
                            "text": "Bug",
                            "value": "bug"
                        }
                    ]
                }
            ]
        }
    ]
}';

  $c = curl_init(SLACK_WEBHOOK);
  curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($c, CURLOPT_POST, true);
  curl_setopt($c, CURLOPT_POSTFIELDS, $message);
  curl_exec($c);
  curl_close($c);

}



/**
* Install
*
* Module installation routine
*
* @access private
*/
 function install($data='') {
  subscribeToEvent($this->name, 'SAY', '', 10);
  parent::install();
 }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgTWFyIDEzLCAyMDE2IHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
