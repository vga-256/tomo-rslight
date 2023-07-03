<?php
// This script reloads groups on the fly, without shutting down the server.
include "config.inc.php";
include "newsportal.php";

// Reload all groups
function reload_groups()
{
  $menulist = file($config_dir."menu.conf", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

   # Rebuild reate group list and copy to spooldir
    $fp1=$spooldir."/".$config_name."/groups.txt";
    unlink($fp1);
    touch($fp1);
      foreach($menulist as $menu) {
       if(($menu[0] == '#') || trim($menu) == "") {
         continue;
       }
       $menuitem=explode(':', $menu);
       if($menuitem[2] == '1') {
        $in_gl = file($config_dir.$menuitem[0]."/groups.txt");
        foreach($in_gl as $ok_group) {
          if(($ok_group[0] == ':') || (trim($ok_group) == "")) {
            continue;
          }
          $ok_group = preg_split("/( |\t)/", trim($ok_group), 2);
          file_put_contents($fp1, $ok_group[0]."\r\n", FILE_APPEND);
        }
       }
    }

 reset($menulist);
 foreach($menulist as $menu) {
   if(($menu[0] == '#') || (trim($menu) == "")) {
     continue;
   }
   $menuitem=explode(':', $menu);
   chdir("../".$menuitem[0]);
 # Refresh spool
   if(isset($spoolnews) && ($spoolnews == true)) {
     exec($CONFIG['php_exec']." ".$config_dir."/scripts/spoolnews.php");
     echo "Refreshed spoolnews\n";
   }
 }
}
?>
