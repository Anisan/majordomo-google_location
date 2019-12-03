<?php
/**
* Default language file for Guestbook module
*
*/
$dictionary=array(
/* general */
"GL_ABOUT" => "About",
"GL_COOKIES" => "Cookies",
"GL_SETTING_TIMEOUT" => "Timeout update (min)",
"GL_LIMIT_SPEED_MIN" => "Min limit speed (zeroing)",
"GL_LIMIT_SPEED_MAX" => "Max delta speed (not send GPS)",
"GL_DEBUG" => "Debug",
"GL_SENDTOGPS" => "Send to GPS module",
"GL_FULLNAME" => "Fullname",
/* end module names */
);

foreach ($dictionary as $k=>$v) {
 if (!defined('LANG_'.$k)) {
  define('LANG_'.$k, $v);
 }
}

?>