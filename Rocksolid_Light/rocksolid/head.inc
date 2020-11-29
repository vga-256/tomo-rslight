<html>
<head>
<title><?php echo htmlspecialchars($title); ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<META HTTP-EQUIV="Content-type" CONTENT="text/html; charset=<?=$www_charset?>">
<?php
include "config.inc.php";
if(isset($frames_on) && $frames_on === false) { 
  if (file_exists('../common/mods/header.php')) {
    include "../common/mods/header.php";
  } else {
    include "../common/header.php";
  }
}
if (file_exists('../common/mods/'.$style_css)) {
    echo '<link rel="stylesheet" type="text/css" href="../common/mods/'.$style_css.'">';
} else {
    echo '<link rel="stylesheet" type="text/css" href="../common/'.$style_css.'">';
}
?>
<hr>
</head>
<body textcolor="black" bgcolor="white">
