<?php
/**
* Default language file for Guestbook module
*
*/
$dictionary=array(
/* general */
"GL_ABOUT" => "О модуле",
"GL_COOKIES" => "Файлы cookies",
"GL_SETTING_TIMEOUT" => "Период обновления (мин)",
"GL_LIMIT_SPEED_MIN" => "Минимальная фиксируемая скорость (обнуление)",
"GL_LIMIT_SPEED_MAX" => "Максимальное изменение скорости для отправки в GPS модуль",
"GL_DEBUG" => "Подробное логирование",
"GL_SENDTOGPS" => "Отправка в GPS tracker",
"GL_FULLNAME" => "Полное имя",
/* end module names */
);

foreach ($dictionary as $k=>$v) {
 if (!defined('LANG_'.$k)) {
  define('LANG_'.$k, $v);
 }
}

?>