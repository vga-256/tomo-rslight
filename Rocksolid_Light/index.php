<?php
session_start();
$_SESSION['isframed'] = 1;

$CONFIG = include('/etc/rslight/rslight.inc.php');

if (isset($_REQUEST['content'])) { 
    $CONFIG['default_content']=$_REQUEST['content'];
}

if (isset($_REQUEST['menu'])) {
    $default_menu=$_REQUEST['menu'];
} 

if(isset($frames_on) && $frames_on === true) {
?>

<html>
	<head>
		<title><?php echo $CONFIG['rslight_title'] ?></title>
		<META HTTP-EQUIV="Content-type" CONTENT="text/html; charset=utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php
if (file_exists('common/mods/'.$style_css)) {
    echo '<link rel="stylesheet" type="text/css" href="common/mods/'.$style_css.'">';
} else {
    echo '<link rel="stylesheet" type="text/css" href="common/'.$style_css.'">';
}
?>
	</head>
	<body>
		<div class='page'>
		<div class='section header'>
<?php

  if (file_exists('common/mods/header.php')) {
    echo '<iframe name="header" src="common/mods/header.php" class="np_frame_header" width=100% height=100%></iframe>';
  } else {
    echo '<iframe name="header" src="common/header.php" class="np_frame_header" width=100% height=100%></iframe>';
  }
?>
		</div>
		<div class='section menu'> 
		<iframe name="menu" src="<?php echo $default_menu;?>" class='np_frame_menu' width=100% height=100%></iframe>
		</div>
		<div class='section content'>
		<iframe name="content" src="<?php echo $CONFIG['default_content'];?>" class='np_frame_content' width=100% height=100%></iframe>
		</div>
		</div>
	</body>
<?php
} else {
  header('Location: '.$CONFIG['default_content']);
}
?>
</html>
