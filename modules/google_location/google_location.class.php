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
    DebMes($message, 'google_location'); 
}
    

function admin(&$out) {
 $this->getConfig();
 $directory_cookies=ROOT."cms/cached/google_location/";
 if (!file_exists($directory_cookies)) {
    mkdir($directory_cookies, 0777, true);}
 if ($this->view_mode=='update_settings') {
   global $timeout_update;
   $this->config['TIMEOUT_UPDATE']=$timeout_update;
   global $min_limit_speed;
   $this->config['MIN_LIMIT_SPEED']=$min_limit_speed;
   global $max_limit_speed;
   $this->config['MAX_LIMIT_SPEED']=$max_limit_speed;
   global $debug;
   $this->config['DEBUG']=$debug;
   if ($this->config['TIMEOUT_UPDATE'] == '')  $this->config['TIMEOUT_UPDATE'] = 1;
   if ($this->config['MIN_LIMIT_SPEED'] == '')  $this->config['MIN_LIMIT_SPEED'] = 0;
   if ($this->config['MAX_LIMIT_SPEED'] == '')  $this->config['MAX_LIMIT_SPEED'] = 200;
   $this->saveConfig();
   $this->redirect("?");
   return;
 }
 $out['TIMEOUT_UPDATE']=$this->config['TIMEOUT_UPDATE'];
 $out['LAST_UPDATE']=$this->config['LAST_UPDATE'];
 $out['MIN_LIMIT_SPEED']=$this->config['MIN_LIMIT_SPEED'];
 $out['MAX_LIMIT_SPEED']=$this->config['MAX_LIMIT_SPEED'];
 if ($out['LAST_UPDATE'] == '')
     $out['LAST_UPDATE'] = 1;
 if ($out['MIN_LIMIT_SPEED'] == '')
     $out['MIN_LIMIT_SPEED'] = 0;
 if ($out['MAX_LIMIT_SPEED'] == '')
     $out['MAX_LIMIT_SPEED'] = 200;
 $out['DEBUG']=$this->config['DEBUG'];
 if($this->view_mode == 'user_edit') {
  $this->edit_user($out, $this->id);
 }
 if($this->view_mode == 'user_delete') {
    $this->delete_user($this->id);
   $this->redirect("?");
   return;
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
    $this->redirect("?");
    return;
 }
 $locations = SQLSelect("select * from google_locations");
 for($i=0;$i<count($locations);$i++) {
     if (time() - strtotime($locations[$i]["LASTUPDATE"]) > 60*60)
        $locations[$i]['WARNING'] = '1';
 }
 $out['LOCATIONS'] = $locations;
 
 $cookies_files = [];//array_diff(scandir($directory_cookies), array('..', '.'));
 if($handle = opendir($directory_cookies)){
    while(false !== ($file = readdir($handle))) {
        if($file != "." && $file != "..")  {
            $cookies_files[] = array("NAME" => $file, "DATE"=>date("F d Y H:i:s", filectime($directory_cookies.$file)), "SIZE"=>$this->sizeFilter(filesize($directory_cookies.$file)), "ERROR"=>$this->config["ERROR_".$file]); 
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
public function sizeFilter( $bytes )
{
    $label = array( 'B', 'KB', 'MB', 'GB', 'TB', 'PB' );
    for( $i = 0; $bytes >= 1024 && $i < ( count( $label ) -1 ); $bytes /= 1024, $i++ );
    return( round( $bytes, 2 ) . " " . $label[$i] );
}

function edit_user(&$out, $id) {
    require(DIR_MODULES . $this->name . '/user_edit.inc.php');
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
    global $ajax;
    global $op;
    if ($ajax) {
        if (!headers_sent()) {
            header ("HTTP/1.0: 200 OK\n");
            header ('Content-Type: text/html; charset=utf-8');
        }
        
        if ($op=='getlocations') {
            $data=array();
            $markers=SQLSelect("SELECT * FROM google_locations");
            $total=count($markers);
            for($i=0;$i<$total;$i++) {
                $markers[$i]['HTML']="<b>".$markers[$i]['FULLNAME']."</b></br>";
                $markers[$i]['HTML'].=LANG_LATEST_UPDATES.": ".$markers[$i]['LASTUPDATE']."</br>";
                $markers[$i]['HTML'].=LANG_BATTERY_LEVEL.": ";
                if ($markers[$i]['CHARGING']==1)
                    $markers[$i]['HTML'].="<i class='glyphicon glyphicon-flash'> </i>";
                $markers[$i]['HTML'].=$markers[$i]['BATTLEVEL']."%";
                $data['LOCATIONS'][]=$markers[$i];
            }
            echo json_encode($data);
        }
        
        exit;
    }
}
 function processSubscription($event, $details='') {
  $this->getConfig();
  if ($event=='MINUTELY') {
      $timeout = $this->config['TIMEOUT_UPDATE'];
      if ($timeout == '0') return;
      if ($timeout == '') $timeout = 1;
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
    foreach ($locations as $location) {
        $rec = SQLSelectOne("select * from google_locations where ID_USER='".$location["id"]."'");
        if ($location['lat'] == 0 && $location['lon'] == 0)
            continue;
        
        if ($rec['ID']) {
            if ($rec['LASTUPDATE'] != date('Y-m-d H:i:s' ,(int)($location['timestamp']/1000)) && $rec["SENDTOGPS"]==1)
            {
                $location['speed'] = $this->getSpeed($rec, $location);
                if ($this->config['MIN_LIMIT_SPEED'] > $location['speed'])
                    $location['speed'] = 0;
                if ($this->config['MAX_LIMIT_SPEED'] > abs($location['speed'] - $rec['SPEED']))
                    $this->sendToGps($location);
                $rec['SPEED'] = $location['speed'];
            }
        }
        
        $rec['LASTUPDATE'] = date('Y-m-d H:i:s' ,(int)($location['timestamp']/1000));
        $rec['ADDRESS'] = $location['address'];
        $rec['LAT'] = $location['lat'];
        $rec['LON'] = $location['lon'];
        $rec['ACCURACY'] = $location['accuracy'];
        if ($location['battery']!=NULL)
            $rec['BATTLEVEL'] = $location['battery'];
        $rec['CHARGING'] = $location['charging'];
        if ($rec['ID']) {
            SQLUpdate('google_locations', $rec); // update
        } else {
            $rec['ID_USER'] = $location['id'];
            $rec['NAME'] = $location['name'];
            $rec['FULLNAME'] = $location['fullname'];
            $rec['IMAGE'] = $location['image'];
            $rec['SPEED'] = 0;
            $rec['ID']=SQLInsert('google_locations', $rec); // adding new record
        }
    }
    $this->config['LAST_UPDATE'] = date('Y-m-d H:i:s');
    $this->saveConfig();
 }
 public function getSpeed($last,$new)
 {
     $time_last = strtotime($last['LASTUPDATE']);
     $time_new = (int)($new['timestamp']/1000);
     $dist = $this->calculateTheDistance($last['LAT'],$last['LON'],$new['lat'],$new['lon']);
     $diff = $time_new - $time_last;
     return round($dist / $diff * 3.6 , 2); // km/h
;
 }
  /**
  * Calculate distance between two GPS coordinates
  * @param mixed $latA First coord latitude
  * @param mixed $lonA First coord longitude
  * @param mixed $latB Second coord latitude
  * @param mixed $lonB Second coord longitude
  * @return double
  */
 function calculateTheDistance($latA, $lonA, $latB, $lonB)
 {
   define('EARTH_RADIUS', 6372795);
   
   $lat1  = $latA * M_PI / 180;
   $lat2  = $latB * M_PI / 180;
   $long1 = $lonA * M_PI / 180;
   $long2 = $lonB * M_PI / 180;

   $cl1 = cos($lat1);
   $cl2 = cos($lat2);
   $sl1 = sin($lat1);
   $sl2 = sin($lat2);

   $delta  = $long2 - $long1;
   $cdelta = cos($delta);
   $sdelta = sin($delta);

   $y = sqrt(pow($cl2 * $sdelta, 2) + pow($cl1 * $sl2 - $sl1 * $cl2 * $cdelta, 2));
   $x = $sl1 * $sl2 + $cl1 * $cl2 * $cdelta;

   $ad = atan2($y, $x);
   
   $dist = round($ad * EARTH_RADIUS);

   return $dist;
 }

 public function sendToGps($location)
 {
    $req = BASE_URL."/gps.php?latitude=".$location['lat']."&longitude=".$location['lon']."&deviceid=".$location['id'].
    "&provider=google_location&accuracy=".$location['accuracy']."&address=".urlencode($location['address']);
    if($location['battery']>0){
        $req .= "&battlevel=".$location['battery']."&charging=".$location['charging'];}
    $req .= "&speed=".$location['speed'];
    $contents = getURLBackground($req,0); 
 }

 public function getLocation($cookie_file) {
    $path_parts = pathinfo($cookie_file);
    try {
        $result = $this->getLocationData($cookie_file);
	} catch (Exception $e) {
        $this->log($e);
        $this->config['ERROR_'.$path_parts['basename']]=$e->getMessage();
        return [];
	}
    $this->config['ERROR_'.$path_parts['basename']]='';
	$return = array();
    if (isset($result[9]))
    {
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
            'battery' => 0,
            'charging' => 0,
		);
    }
	if (isset($result[0]))
    {
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
    }
    $this->debug($return);
	return $return;
 }
 
 public function getLocationData($cookie_file) {
    $url = 'https://www.google.com/maps/rpc/locationsharing/read?authuser=0&hl='.SETTINGS_SITE_LANGUAGE.'&gl='.SETTINGS_SITE_LANGUAGE.'&pb=';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
    curl_setopt($ch, CURLOPT_VERBOSE, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
	 
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
    $this->debug($result);
    $result = json_decode($result, true);
    if (!isset($result[9]) && !isset($result[0])) {
	if(method_exists($this, 'sendnotification')) {
		$this->sendnotification('Проблема с cookie файлом!', 'danger');
	}
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
        SQLExec('DROP TABLE IF EXISTS google_locations');
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
 google_locations: SPEED float DEFAULT '0' NOT NULL
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
