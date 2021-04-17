<?php
/*  rslight NNTP<->HTTP Gateway
 *  Version: 0.6.x
 *  Download: https://news.novabbs.com/getrslight
 *
 *  Based on Newsportal by Florian Amrhein
 *
 *  E-Mail: retroguy@novabbs.com
 *  Web: https://news.novabbs.com
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

session_start();
include "config.inc.php";
$CONFIG = include($config_file);

@$fieldnamedecrypt=$_REQUEST['fielddecrypt'];
//@$newsgroups=$_REQUEST["newsgroups"];
//@$group=$_REQUEST["group"];
@$type=$_REQUEST["type"];
@$subject=stripslashes($_REQUEST[md5($fieldnamedecrypt."subject")]);
@$name=$_REQUEST[md5($fieldnamedecrypt."name")];
@$email=$_REQUEST[md5($fieldnamedecrypt."email")];
@$body=stripslashes($_REQUEST[md5($fieldnamedecrypt."body")]);
@$abspeichern=$_REQUEST["abspeichern"];
@$references=$_REQUEST["references"];
@$id=$_REQUEST["id"];
if (!isset($group)) $group=$newsgroups;

include "auth.inc";
if($post_captcha)
  include "lib/captcha/captcha.php";

// Save name in cookies
if (($setcookies==true) && (isset($abspeichern)) && ($abspeichern=="ja")) {
  setcookie("cookie_name",stripslashes($name),time()+(3600*24*90));
} 
if ((isset($post_server)) && ($post_server!=""))
  $server=$post_server;
if ((isset($post_port)) && ($post_port!=""))
  $port=$post_port;

include $file_newsportal;

  $findsection = get_section_by_group($_REQUEST["group"]);
  if(trim($findsection) !== $config_name) {
    $newurl = preg_replace("|/$config_name/|", "/$findsection/", $_SERVER['REQUEST_URI']);
    header("Location: $newurl");
    die();
  }

include "head.inc";
global $synchro_user,$synchro_pass;
// check to which groups the user is allowed to post to
$thisgroup=_rawurldecode($_REQUEST['group']);
if($testgroup) {
  $newsgroups=testgroups($thisgroup);
} else {
  $newsgroups=$thisgroup;
}
  echo '<h1 class="np_thread_headline">';
  echo '<a href="'.$file_index.'" target='.$frame['menu'].'>'.basename(getcwd()).'</a> / ';
  echo '<a href="'.$file_thread.'?group='.rawurlencode($thisgroup).'" target='.$frame["content"].'>'.htmlspecialchars(group_display_name($thisgroup)).'</a>';
  if(isset($type) && $type == 'post') {
    echo ' / '.$subject.'</h1>';
  } else {
    echo '</h1>';
  }
  echo '<table cellpadding="0" cellspacing="0" width="100%" class="np_buttonbar"><tr>';
// View Latest button
  if (isset($overboard) && ($overboard == true)) {
    echo '<td>';
    echo '<form action="overboard.php">';
    echo '<input type="hidden" name="thisgroup" value="'._rawurlencode($thisgroup).'"/>';
    echo '<button class="np_button_link" type="submit">'.$text_thread["button_latest"].'</button>';
    echo '</form>';
    echo '</td>';
  }
 if (!$CONFIG['readonly'] &&
      (!function_exists("npreg_group_has_write_access") ||
       npreg_group_has_write_access($thisgroup)))
 {
// New Thread button
    echo '<td>';
    echo '<form action="'.$file_post.'">';
    echo '<input type="hidden" name="group" value="'.urlencode($thisgroup).'"/>';
    echo '<button class="np_button_link" type="submit">'.$text_thread["button_write"].'</button>';
    echo '</form>';
    echo '</td>';
 }
 // Pages
    echo '<td class="np_pages" width="100%" align="right">';
    echo articleflat_pageselect($thisgroup,$id,count($subthread),$first);
    echo '</td></tr></table>';
// has the user write-rights on the newsgroups?
if((function_exists("npreg_group_has_read_access") &&
    !npreg_group_has_read_access($newsgroups)) ||
   (function_exists("npreg_group_has_write_access") &&
    !npreg_group_has_write_access($newsgroups))) {
  die("access denied");
}

// Load name from cookies
if ($setcookies) {
  if ((isset($_COOKIE["cookie_name"])) && (!isset($name)))
    $name=$_COOKIE["cookie_name"];
//  if ((isset($_COOKIE["cookie_email"])) && (!isset($email)))
//    $email=$_COOKIE["cookie_email"];
}

// Load name and email from the registration system, if available
if(function_exists("npreg_get_name")) {
  $name=npreg_get_name();
}

if(function_exists("npreg_get_email")) {
  $email=npreg_get_email();
  $form_noemail=true;
}

if(!strcmp($name, $CONFIG['anonusername']) && (isset($CONFIG['anonuser']))) {
  $userpass = $CONFIG['anonuserpass'];
  $email=$name.$CONFIG['email_tail'];
} else {
  $userpass = $email;
  $request="email";
  $get_email = get_user_config($name,$request);
  if($get_email === FALSE) {
    $email=$name.$CONFIG['email_tail'];
  } else {
    $email=trim($get_email);
  }
}

if (isset($CONFIG['synchronet']) && ($CONFIG['synchronet'] == true)) {
  $synchro_user=$name;
  $synchro_pass=$userpass;
} 

if($name=="")
  $name=$_SERVER['REMOTE_USER'];

if((!isset($references)) || ($references=="")) {
  $references=false;
}

if (!isset($type)) {
  $type="new";
}

if ($type=="new") {
  $subject="";
  $bodyzeile="";
  $show=1;
}

// Is there a new article to post to the newsserver?
if ($type=="post") {
  $show=0;
  if (!$CONFIG['synchronet']) {
    if (check_bbs_auth(trim($name), $userpass)==FALSE) {
      $type="retry";
      $error=$text_error["auth_error"];
    }
  }
  // error handling
  if (trim($body)=="") {
    $type="retry";
    $error=$text_post["missing_message"];
  }
  if ((trim($email)=="") && (!isset($anonym_address))) {
    $type="retry";
    $error=$text_post["missing_email"];
  }
  if (($email) && (!validate_email(trim($email)))) {
    $type="retry";
    $error=$text_post["error_wrong_email"];
  }
  if (trim($name)=="") {
    $type="retry";
    $error=$text_post["missing_name"];
  }
  if (trim($subject)=="") {
    $type="retry";
    $error=$text_post["missing_subject"];
  }

  // captcha-check
  if(($post_captcha) && (captcha::check()==false)) {
    $type="retry";
    $error=$text_post["captchafail"];
  }
              

  if ($type=="post") {
    if (!$CONFIG['readonly']) {
      // post article to the newsserver
      if($references)
        $references_array=explode(" ",$references);
      else
        $references_array=false;
      if(($email=="") && (isset($anonym_address)))
        $nemail=$anonym_address;
      else
        $nemail=$email;
        $rate_limit = get_user_config($name, 'rate_limit');
        if(($rate_limit !== FALSE) && ($rate_limit > 0)) {
          $CONFIG['rate_limit'] = $rate_limit;
        }
	if($CONFIG['rate_limit'] == true && strcmp($name, $CONFIG['anonusername'])) {
            $postsremaining = check_rate_limit($name);
	    if($postsremaining < 1) {
	      $wait = check_rate_limit($name,0,1);
	      echo 'You have reached the limit of '.$CONFIG['rate_limit'].' posts per hour.<br />Please wait '.round($wait).' minutes before posting again.';
	      $returngroup=explode(',',$newsgroups);
	      echo '<p><a href="'.$file_thread.'?group='.urlencode($returngroup[0]).'">'.$text_post["button_back"].'</a> '.$text_post["button_back2"].' '.group_display_name($returngroup[0]).'</p>';
	      return;	
	    }
        }
	if(isset($_FILES["photo"]) && $_FILES["photo"]["error"] == 0) { 
// There is an attachment to handle 
		$message=message_post_with_attachment(quoted_printable_encode($subject),
                 $nemail." (".quoted_printable_encode($name).")",
                 $newsgroups,$references_array,addslashes($body),$_POST['encryptthis'],$_POST['encryptto']);
	} else {
	  $message=message_post(quoted_printable_encode($subject),
                 $nemail." (".quoted_printable_encode($name).")",
                 $newsgroups,$references_array,addslashes($body),$_POST['encryptthis'],$_POST['encryptto']);
	}
	  // Article sent without errors, or duplicate?
      if ((substr($message,0,3)=="240") ||
          (substr($message,0,7)=="441 435")) {
	  echo '<h1 class="np_post_headline"><'.$text_post["message_posted"].'></h1>';
	  echo '<p>'.$text_post["message_posted2"].'</p>';
// This returns to multiple groups if crossposting, which does not work. FIXME
// Try to return to just the first group in the list
     $returngroup=explode(',',$newsgroups); 
     if(isset($CONFIG['auto_return']) && ($CONFIG['auto_return'] == true)) {
  	echo '<meta http-equiv="refresh" content="0;url='.$file_thread.'?group='.urlencode($returngroup[0]).'"';
     }
     if($CONFIG['rate_limit'] == true && strcmp($name, $CONFIG['anonusername'])) {
       $postsremaining = check_rate_limit($name,1);
       echo 'You have '.$postsremaining.' posts remaining of '.$CONFIG['rate_limit'].' posts per hour.<br />';
       if($postsremaining < 1) {
	 $wait = check_rate_limit($name,0,1);
         echo 'Please wait '.round($wait).' minutes before posting again.<br />';
       }
     }
//     echo '<p><a href="'.$file_thread.'?group='.urlencode($returngroup[0]).'">'.$text_post["button_back"].'</a> '.$text_post["button_back2"].' '.group_display_name($returngroup[0]).'</p>';
     echo '<p><a href="'.$_SESSION['return_page'].'">Back to Previous Page</a></p>';
      } else {
        // article not accepted by the newsserver
        $type="retry";
        $error=$text_post["error_newsserver"]."<br><pre>$message</pre>";
      }
    } else {
      echo $text_post["error_readonly"];
    }
  }
}

// A reply of an other article.
if ($type=="reply") {
  $message=message_read($id,0,$newsgroups);
  $head=$message->header;

  $body=explode("\n",$message->body[0]);
  nntp_close($ns);
  if ($head->name != "") {
    $bodyzeile=$head->name;
  } else {
    $bodyzeile=$head->from;
  }

// For Synchronet use
  $fromname=$bodyzeile;

  $bodyzeile=$text_post["wrote_prefix"].$bodyzeile.
             $text_post["wrote_suffix"]."\n\n";
  for ($i=0; $i<=count($body)-1; $i++) {
    if((isset($cutsignature)) && ($cutsignature==true) &&
       ($body[$i]=='-- '))
      break;
    if (trim($body[$i])!="") {
      if($body[$i][0]=='>')
        $bodyzeile.=">".$body[$i]."\n";
      else
        $bodyzeile.="> ".$body[$i]."\n";
    } else {
      $bodyzeile.="\n";
    }
  }
  $subject=$head->subject;
  if (isset($head->followup) && ($head->followup != "")) {
    $newsgroups=$head->followup;
  } else {
    if($testgroup) {
      $newsgroups=testgroups($head->newsgroups);
    } else {
      $newsgroups=$head->newsgroups;
    }
  }
  splitSubject($subject);
  $subject="Re: ".$subject;
  // Cut off old parts of a subject
  // for example: 'foo (was: bar)' becomes 'foo'.
  $subject=preg_replace('/(\(wa[sr]: .*\))$/i','',$subject);
  $show=1;
  $references=false;
  if (isset($head->references[0])) {
    for ($i=0; $i<=count($head->references)-1; $i++) {
      $references .= $head->references[$i]." ";
    }
  }
  $references .= $head->id;
}

if ($type=="retry") {
  $show=1;
  $bodyzeile=$body;
}

if ($show==1) {


if ($newsgroups == "") {
  echo $text_post["followup_not_allowed"];
  echo " ".$newsgroups;
} else {
  // show post form
  $fieldencrypt=md5(rand(1,10000000));

  echo '<h1 class="np_post_headline">'.$text_post["group_head"].group_display_name($newsgroups)
    .$text_post["group_tail"].'</h1>';

  if (isset($error)) echo "<p>$error</p>"; ?>

<form action="<?php echo $file_post?>" method="post" name="postform" enctype="multipart/form-data">

<div class="np_post_header">
<table>
<tr><td align="right"><b><?php echo $text_header["subject"] ?></b></td>
<td><input class="post" type="text" name="<?php echo md5($fieldencrypt."subject")?>" value="<?php
echo htmlspecialchars($subject);?>" size="40" maxlength="80"></td></tr>
<tr><td align="right"><b><?php echo $text_post["name"]?></b></td>
 <td align="left">
 <?php
 if ( !isset($name) && $CONFIG['anonuser'])
    $name = $CONFIG['anonusername'];
 if($form_noname===true) {
   echo htmlspecialchars($name);
 } else {
   echo '<input class="post" type="text" name="'.md5($fieldencrypt."name").'"';
   if (isset($name)) echo 'value="'.
    htmlspecialchars(stripslashes($name)).'"';
   echo 'size="40" maxlength="40">';
   if($CONFIG['anonuser'])
     echo '&nbsp;or "'.$CONFIG['anonusername'].'" with no password';
 }
 ?>
 </td></tr>
 <tr><td align="right"><b><?php echo $text_post["password"]?></b></td>
 <td align="left">
 <?php
   echo '<input class="post" type="password" name="'.md5($fieldencrypt."email").'"';
//   if (isset($email)) echo 'value="'.htmlspecialchars(stripslashes($email)).'"';
   echo 'size="40" maxlength="40">';
 ?>
 </td</tr>

<?php
// May we post encrypted messages to this group?
    if(check_encryption_groups($newsgroups)) { ?>
 <tr><td align="left">
   <input type="checkbox" name="encryptthis" value="encrypt">
   <b>Encrypt to:</b>
 </td>
 <td><input type="text" name="encryptto" value="<?php echo $fromname;?>"></td>
</tr>
<?php 
  }
?>
</table>
</div>

<div class="np_post_body">
<table>
<tr><td><b><?php echo $text_post["message"];?></b><br>
<textarea class="postbody" id="postbody" name="<?php echo md5($fieldencrypt."body")?>" wrap="soft"><?php
if ((isset($bodyzeile)) && ($post_autoquote))
  echo htmlspecialchars($bodyzeile);
if(is_string($body))
  echo htmlspecialchars($body);
?>
</textarea></td></tr>
<tr><td>

<?php if(!$post_autoquote) { ?>
<input type="hidden" id="hidebody" value="<?php
if (isset($bodyzeile)) echo htmlspecialchars(stripslashes($bodyzeile)); ?>">

<script language="JavaScript">
<!--
function quoten() {
  document.getElementById("postbody").value=document.getElementById("hidebody").value;
  document.getElementById("hidebody").value="";
}
//-->
</script>

<?php } ?>

<input type="submit"  value="<?php echo $text_post["button_post"];?>">
<?php if ($setcookies==true) { ?>
&nbsp;<input tabindex="100" type="Button" name="quote" value="<?php echo $text_post["quote"]?>" onclick="quoten(); this.style.visibility= 'hidden';">
&nbsp;<input type="checkbox" name="abspeichern" value="ja" checked>
<?php echo $text_post["remember"];?>
<?php } ?>
&nbsp;<input type="file" name="photo" id="fileSelect" value="fileSelect" accept="image/*,audio/*,text/*,application/pdf">
</td>
</tr>

<?php if($post_captcha) {
  echo '<tr><td>';
  echo captcha::form($text_post["captchainfo1"],$text_post["captchainfo2"]);
  echo '</td></tr>';
} ?>
  

</table>
</div>
<input type="hidden" name="type" value="post">
<input type="hidden" name="newsgroups" value="<?php echo htmlspecialchars($newsgroups); ?>">
<input type="hidden" name="references" value="<?php echo htmlentities($references); ?>">
<input type="hidden" name="group" value="<?php echo htmlspecialchars($newsgroups); ?>">
<input type="hidden" name="fielddecrypt" value="<?php echo htmlspecialchars($fieldencrypt);?>">
</form>

<?php } } ?>

