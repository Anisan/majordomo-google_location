<?php
$table_name='google_locations';
$rec=SQLSelectOne("SELECT * FROM $table_name WHERE ID='$id'");

if ($this->mode=='update') { 
  $ok=1;
  if ($this->tab=='') {
    global $name;
    $rec['NAME']=$name;
    global $fullname;
    $rec['FULLNAME']=$fullname;
    
    //UPDATING RECORD
    if ($ok) {
      if ($rec['ID']) {
          
        global $file;
        if ($file)
        {
            $dir=ROOT."cms/cached/google_location_img/";
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);}
 
            $file_avatar = $dir.$rec['ID_USER'];
            copy($file, $file_avatar);
            $rec['IMAGE']="/cms/cached/google_location_img/".$rec['ID_USER'];
        }
        
    
        SQLUpdate($table_name, $rec); // update
      }   
      $out['OK']=1;
    } else {
      $out['ERR']=1;
    }
  }
    $ok=1;
}
outHash($rec, $out);
  
?>
