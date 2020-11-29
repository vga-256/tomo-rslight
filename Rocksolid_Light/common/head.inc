<html>
<head>
<title><?php echo htmlspecialchars($title); ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<META HTTP-EQUIV="Content-type" CONTENT="text/html; charset=<?=$www_charset?>">
<?php

include "config.inc.php";

if (file_exists('mods/header.php')) {
  include "mods/header.php";
} else {
  include "header.php";
}

if (file_exists('mods/'.$style_css)) {
    echo '<link rel="stylesheet" type="text/css" href="mods/'.$style_css.'">';
} else {
    echo '<link rel="stylesheet" type="text/css" href="'.$style_css.'">';
}
?>
<hr>
</head>
<body textcolor="black" bgcolor="white">
