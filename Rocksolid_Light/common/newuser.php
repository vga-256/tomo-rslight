<?php
include "head.inc";
if (isset($_COOKIE["ts_limit"])) {
  echo "It appears you already have an active account<br/>";
  echo "More than one account may not be created in 30 days<br/>";
  echo '<br/><a href="/">Return to Home Page</a>';
} else {
?>
<table border="0" align="center" cellpadding="0" cellspacing="1">
<tr>
<form name="form1" method="post" action="rsusers.php">
<td>
<tr>
<td><strong>Register Username </strong></td>
</tr>
<tr>
<td>Username:</td>
<td><input name="username" type="text" id="username"></td>
</tr>
<tr>
<td>Email:</td>
<td><input name="user_email" type="text" id="user_email"></td>
</tr>
<tr>
<td>Password:</td>
<td><input name="password" type="password" id="password"></td>
</tr>
<tr>
<td>Re-enter Password:</td>
<td><input name="password2" type="password" id="password2"></td>
</tr>
<tr>
<td><input name="command" type="hidden" id="command" value="Create" readonly="readonly"></td>
</tr>
<tr>
<td>&nbsp;</td>
<td><input type="submit" name="Submit" value="Create"></td>
</tr>
<tr><td><a href="changepw.php">Change current password</a></td></tr>
<tr><td>
<td></td><td></td>
</td></tr>
</td>
</form>
</tr>
</table>
<?php
}
?>
</body>
</html>
