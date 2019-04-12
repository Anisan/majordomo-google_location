<?php
/**
* Google Location 
* @package project
* @author Wizard <sergejey@gmail.com>
* @copyright http://majordomo.smartliving.ru/ (c)
* @version 0.1 (wizard, 07:04:47 [Apr 10, 2019])
*/
//
//
class google_location extends module {
/**
* google_location
*
* Module class constructor
*
* @access private
*/
function __construct() {
  $this->name="google_location";
  $this->title="Google Location";
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
function saveParams($data=1) {
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
  $this->data=$out;
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
function debug($content) {
    if($this->config['DEBUG'])
        $this->log(print_r($content,true));
}

function log($message) {
    //echo $message . "\n";
    // DEBUG MESSAGE LOG
    if(!is_dir(ROOT . 'debmes')) {
        mkdir(ROOT . 'debmes', 0777);
    }
    $today_file = ROOT . 'cms/debmes/log_' . date('Y-m-d') . '-google_location.php.txt';
    $data = date("H:i:s")." " . $message . "\n";
    file_put_contents($today_file, $data, FILE_APPEND | LOCK_EX);
}
    

function admin(&$out) {
 $this->getConfig();
 $out['COOKIE_FILE']=$this->config['COOKIE_FILE'];
 $out['TIMEOUT_UPDATE']=$this->config['TIMEOUT_UPDATE'];
 $out['LAST_UPDATE']=$this->config['LAST_UPDATE'];
 $out['DEBUG']=$this->config['DEBUG'];
 if ($this->view_mode=='update_settings') {
   global $cookie_file;
   $this->config['COOKIE_FILE']=$cookie_file;
   global $timeout_update;
   $this->config['TIMEOUT_UPDATE']=$timeout_update;
   global $debug;
   $this->config['DEBUG']=$debug;
   $this->saveConfig();
   $this->redirect("?");
   return;
 }
 if ($this->view_mode=='update_location') {
   $this->updateLocation();
   $this->redirect("?");
   return;
 }
 if ($this->view_mode=='send_switch') {
    global $id;
    $rec = SQLSelectOne("select * from google_locations where ID_USER='".$id."'");
    if ($rec['ID']) {
        if ($rec['SENDTOGPS']==1)
            $rec['SENDTOGPS']=0;
        else
            $rec['SENDTOGPS']=1;
        SQLUpdate('google_locations', $rec); // update
    }
 }
 $locations = SQLSelect("select * from google_locations");
 $out['LOCATIONS'] = $locations;
 
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
 function processSubscription($event, $details='') {
 $this->getConfig();
  if ($event=='MINUTELY') {
      $timeout = $this->config['TIMEOUT_UPDATE'];
      $m=date('i',time());
      if ($m % $timeout == 0)
        $this->updateLocation();
  }
 }
 public function updateLocation() {
    $locations = $this->getLocation();
    foreach ($locations as $location) {
        $rec = SQLSelectOne("select * from google_locations where ID_USER='".$location["id"]."'");
        if ($rec['ID']) {
            if ($rec['LASTUPDATE'] != date('Y-m-d H:i:s' ,(int)($location['timestamp']/1000)) && $rec["SENDTOGPS"]==1)
            {
                $this->sendToGps($location);
            }
        }
        $rec['NAME'] = $location['name'];
        $rec['FULLNAME'] = $location['fullname'];
        $rec['IMAGE'] = $location['image'];
        $rec['LASTUPDATE'] = date('Y-m-d H:i:s' ,(int)($location['timestamp']/1000));
        $rec['ADDRESS'] = $location['address'];
        $rec['LAT'] = $location['lat'];
        $rec['LON'] = $location['lon'];
        $rec['ACCURACY'] = $location['accuracy'];
        $rec['BATTLEVEL'] = $location['battery'];
        $rec['CHARGING'] = $location['charging'];
        if ($rec['ID']) {
            SQLUpdate('google_locations', $rec); // update
        } else {
            $rec['ID_USER'] = $location['id'];
            $rec['ID']=SQLInsert('google_locations', $rec); // adding new record
        }

            
    }
    $this->config['LAST_UPDATE'] = date('Y-m-d H:i:s');
    $this->saveConfig();
 }
 
 public function sendToGps($location)
 {
    $req = BASE_URL."/gps.php?latitude=".$location['lat']."&longitude=".$location['lon']."&deviceid=".$location['id'].
    "&provider=google_location&battlevel=".$location['battery']."&charging=".$location['charging']."&accuracy=".$location['accuracy'];
    $contents = getURLBackground($req,0); 
 }

 public function getLocation() {
    try {
		$result = $this->getLocationData();
	} catch (Exception $e) {
		//$this->google_connect();
		//$result = $this->google_callLocationUrl();
        $this->log($e);
        return;
	}
	$return = array();
    $return[] = array(
			'id' => 'self_account',
			'name' => 'Google account',
			'fullname' => 'Google account',
			'image' => '',
			'address' => $result[9][1][4],
			'timestamp' => $result[9][1][2],
			'lat' => $result[9][1][1][2],
            'lon' => $result[9][1][1][1],
			'accuracy' => $result[9][1][3],
		);
	$result = $result[0];
	foreach ($result as $user) {
		$return[] = array(
			'id' => $user[6][0],
			'name' => $user[6][3],
			'fullname' => $user[6][2],
			'image' => $user[0][1],
			'address' => $user[1][4],
			'timestamp' => $user[1][2],
			'lat' => $user[1][1][2],
            'lon' => $user[1][1][1],
			'accuracy' => $user[1][3],
			'battery' => $user[13][1],
            'charging' => $user[13][0],
		);
	}
    $this->debug($return);
	return $return;
 }
 
 public function getLocationData() {
    $this->getConfig();
    $url = 'https://www.google.com/maps/preview/locationsharing/read?authuser=0&hl='.SETTINGS_SITE_LANGUAGE.'&gl='.SETTINGS_SITE_LANGUAGE.'&pb=';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $this->config['COOKIE_FILE']);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $this->config['COOKIE_FILE']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    $headers = $this->get_headers_from_curl_response($response);
    $this->debug('Location data : Connection successful, reponse : '. $info['http_code']);
    if (empty($info['http_code']) || $info['http_code'] != 200) {
        throw new Exception('Error connection : '. $info['http_code'] . ' => ' . json_encode($headers));
    }
    $this->debug($result);
    $result = substr($response, $info['header_size'] + 4);
    if (!$this->is_json($result)) {
        throw new Exception('Not valid json result : ' . $result);
    }
    $result = json_decode($result, true);
    if (!isset($result[9])) {
        throw new Exception('Error json data : ' . json_encode($result));
    }
    return $result;
}

private function is_json($string) {
 json_decode($string, true);
 if (json_last_error() != JSON_ERROR_NONE) {
    $error = json_last_error_msg();
    throw new \LogicException(sprintf("Failed to parse json string '%s', error: '%s'", $string, $error));
 }
 return true;
}

public function get_headers_from_curl_response($response) {
	$headers = array();
	$header_text = substr($response, 0, strpos($response, "\r\n\r\n"));
	foreach (explode("\r\n", $header_text) as $i => $line) {
		if ($i === 0) {
			$headers['http_code'] = $line;
		} else {
			list($key, $value) = explode(': ', $line);
			$headers[$key] = $value;
		}
	}
	return $headers;
}
/**
* Install
*
* Module installation routine
*
* @access private
*/
 function install($data='') {
  subscribeToEvent($this->name, 'MINUTELY');
  parent::install();
 }
  /**
     * Uninstall
     *
     * Module uninstall routine
     *
     * @access public
     */
 function uninstall() {
        unsubscribeFromEvent($this->name, 'MINUTELY'); 
        parent::uninstall();
    }
    /**
     * dbInstall
     *
     * Database installation routine
     *
     * @access private
     */
function dbInstall($data) {
        $data = <<<EOD
 google_locations: ID int(10) unsigned NOT NULL auto_increment
 google_locations: ID_USER varchar(255) NOT NULL DEFAULT ''
 google_locations: NAME varchar(255) NOT NULL DEFAULT ''
 google_locations: FULLNAME varchar(255) NOT NULL DEFAULT ''
 google_locations: IMAGE varchar(255)  NOT NULL DEFAULT ''
 google_locations: LASTUPDATE datetime
 google_locations: ADDRESS varchar(512)  NOT NULL DEFAULT ''
 google_locations: LAT float DEFAULT '0' NOT NULL
 google_locations: LON float DEFAULT '0' NOT NULL
 google_locations: ACCURACY float DEFAULT '0' NOT NULL
 google_locations: BATTLEVEL int(3) NOT NULL DEFAULT '0'
 google_locations: CHARGING int(3) NOT NULL DEFAULT '0'
 google_locations: SENDTOGPS int(3) NOT NULL DEFAULT '0'

EOD;
        parent::dbInstall($data);
    }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgQXByIDEwLCAyMDE5IHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
