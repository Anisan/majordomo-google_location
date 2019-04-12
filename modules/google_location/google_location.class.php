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
 $directory_cookies=ROOT."cms/cached/google_location/";
 if (!file_exists($directory_cookies)) {
    mkdir($directory_cookies, 0777, true);}
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
 if($this->view_mode == 'user_delete') {
    $this->delete_user($this->id);
 }
 if ($this->view_mode=='upload_cookie') {

   global $file;
   global $file_name;
   copy($file, $directory_cookies.$file_name);
   $this->redirect("?");
   return;
 } 
 if($this->view_mode == 'delete_cookie') {
     global $name;
     $this->debug('delete cookie '.$name);
     unlink($directory_cookies.$name);

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
 
 $cookies_files = [];//array_diff(scandir($directory_cookies), array('..', '.'));
 if($handle = opendir($directory_cookies)){
    while(false !== ($file = readdir($handle))) {
        if($file != "." && $file != "..")  {
            $cookies_files[] = array("NAME" => $file, "DATE"=>date("F d Y H:i:s", filectime($directory_cookies.$file)), "SIZE"=>filesize($directory_cookies.$file)); 
        }
    }
    closedir( $handle );
 }
 if ($cookies_files)
 {
    $this->debug($cookies_files);
    $out['COOKIES_FILES'] = $cookies_files;
 }
 
}

function delete_user($id) {
        $rec = SQLSelectOne("SELECT * FROM google_locations WHERE ID='$id'");
        SQLExec("DELETE FROM google_locations WHERE ID='" . $rec['ID'] . "'");
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
    $locations = [];
    $directory_cookies=ROOT."cms/cached/google_location/";
    $cookies = array_diff(scandir($directory_cookies), array('..', '.'));
    foreach ($cookies as $cookie)
    {
        $locations = array_merge($locations,$this->getLocation($directory_cookies.$cookie));
    }
    $this->debug($locations);
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

 public function getLocation($cookie_file) {
    try {
        $result = $this->getLocationData($cookie_file);
	} catch (Exception $e) {
		//$this->google_connect();
		//$result = $this->google_callLocationUrl();
        $this->log($e);
        return [];
	}
	$return = array();
    $path_parts = pathinfo($cookie_file);
    $return[] = array(
			'id' => crc32($cookie_file),
			'name' => $path_parts['filename'],
			'fullname' => $path_parts['filename'],
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
 
 public function getLocationData($cookie_file) {
    $url = 'https://www.google.com/maps/preview/locationsharing/read?authuser=0&hl='.SETTINGS_SITE_LANGUAGE.'&gl='.SETTINGS_SITE_LANGUAGE.'&pb=';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
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
