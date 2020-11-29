<?php
include "head.inc";
?>
<body>
<table width=100% border="0" align="center" cellpadding="0" cellspacing="1">
<tr>
<form name="form1" method="get" action="result.php">
<td>
<table width="100%" border="0" cellpadding="3" cellspacing="1">
<tr>
<td colspan="3"><strong>Search recent messages in <?php echo $config_name; ?></strong><br />(searches last <?php echo $maxarticles; ?> articles per group)</td>
</tr>
<tr></tr>
<tr>
<td width="78"><strong>Search Terms</strong></td>
<td width="6">:</td>
<td width="294"><input name="terms" type="text" id="terms"></td>
</tr>
<tr></tr>
<tr>
<td><input type="radio" name="searchpoint" value="Subject" checked="checked"/>Subject</td>
<td><input type="radio" name="searchpoint" value="Poster"/>Poster</td>
<td><input type="radio" name="searchpoint" value="Message-ID"/>Message-ID</td>
</tr>
<tr>
<td><input name="command" type="hidden" id="command" value="Search" readonly="readonly"></td>
</tr>
<tr>
<td>&nbsp;</td>
<td>&nbsp;</td>
<td><input type="submit" name="Submit" value="Search"></td>
</tr>
<tr><td>
<td></td><td></td>
</td></tr>
</table>
</td>
</form>
</tr>
</table>
</body>
</html>
