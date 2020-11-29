<html>
<head>
  <title>captcha.php testing</title>
  <style>
html {
   border-left: 30px solid #222222;
}
input,textarea {
   background: #333333;
   color: #dddddd;
   border: 2px solid #555555;
}
.captcha {
   border: 2px dashed: #331111;
   background: #110000;
   padding: 5px;
}
form {
   border: 1px dashed #551111;
   padding: 2px;
}
  </style>
</head>
<body bgcolor="#000000" text="#ffffff">
<h1>CAPTCHA Test</h1>
<?php
  define("CAPTCHA_INVERSE", 1);
  include "captcha.php";
?>

<form action="_test.1.php" >
(we have an example input &lt;form&gt; here)<br>
<textarea cols=50 rows=3><?php
  $res = captcha::check();
  if (isset($res)) {
     if ($res) {
        $msg = "SUCCESS!!";
     }
     else {
        $msg = "FAILED.";
     }
     for ($n=0; $n<5; $n++) {
        echo "$msg\n";
     }
  }
?></textarea>
<br>
<br>
<?php
  echo captcha::form();
?>
<br>
<input type="submit" value="Test &amp; Save">
</form>

<br>
<br>

This is just an example. It's function-less form, which appears over
and over again. You'd typically also want to provide a more senseful
error message, if captcha::test() fails.

<br>
<br>

</body>
</html>
