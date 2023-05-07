<?php
/*  rslight NNTP<->HTTP Gateway
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

@session_start();
/*
 * Encode lines with 8bit-characters to quote-printable
 *
 * $line: the to be encoded line
 *
 * the function returns a sting containing the quoted-printable encoded
 * $line
 */
function encode_subject($line) {
		$newstring=mb_encode_mimeheader(quoted_printable_decode($line));
	    return $newstring;
}

if(!function_exists('quoted_printable_encode'))
{
function quoted_printable_encode($line) {
  global $www_charset;
  $qp_table=array(
     '=00', '=01', '=02', '=03', '=04', '=05',
     '=06', '=07', '=08', '=09', '=0A', '=0B',
     '=0C', '=0D', '=0E', '=0F', '=10', '=11',
     '=12', '=13', '=14', '=15', '=16', '=17',
     '=18', '=19', '=1A', '=1B', '=1C', '=1D',
     '=1E', '=1F', '_',   '!',   '"',   '#',
     '$',   '%',   '&',   "'",   '(',   ')',
     '*',   '+',   ',',   '-',   '.',   '/',
     '0',   '1',   '2',   '3',   '4',   '5',
     '6',   '7',   '8',   '9',   ':',   ';',
     '<',   '=3D', '>',   '=3F', '@',   'A',
     'B',   'C',   'D',   'E',   'F',   'G',
     'H',   'I',   'J',   'K',   'L',   'M',
     'N',   'O',   'P',   'Q',   'R',   'S',
     'T',   'U',   'V',   'W',   'X',   'Y',
     'Z',   '[',   '\\',  ']',   '^',   '=5F',
     '',    'a',   'b',   'c',   'd',   'e',
     'f',   'g',   'h',   'i',   'j',   'k',
     'l',   'm',   'n',   'o',   'p',   'q',
     'r',   's',   't',   'u',   'v',   'w',
     'x',   'y',   'z',   '{',   '|',   '}',
     '~',   '=7F', '=80', '=81', '=82', '=83',
     '=84', '=85', '=86', '=87', '=88', '=89',
     '=8A', '=8B', '=8C', '=8D', '=8E', '=8F',
     '=90', '=91', '=92', '=93', '=94', '=95',
     '=96', '=97', '=98', '=99', '=9A', '=9B',
     '=9C', '=9D', '=9E', '=9F', '=A0', '=A1',
     '=A2', '=A3', '=A4', '=A5', '=A6', '=A7',
     '=A8', '=A9', '=AA', '=AB', '=AC', '=AD',
     '=AE', '=AF', '=B0', '=B1', '=B2', '=B3',
     '=B4', '=B5', '=B6', '=B7', '=B8', '=B9',
     '=BA', '=BB', '=BC', '=BD', '=BE', '=BF',
     '=C0', '=C1', '=C2', '=C3', '=C4', '=C5',
     '=C6', '=C7', '=C8', '=C9', '=CA', '=CB',
     '=CC', '=CD', '=CE', '=CF', '=D0', '=D1',
     '=D2', '=D3', '=D4', '=D5', '=D6', '=D7',
     '=D8', '=D9', '=DA', '=DB', '=DC', '=DD',
     '=DE', '=DF', '=E0', '=E1', '=E2', '=E3',
     '=E4', '=E5', '=E6', '=E7', '=E8', '=E9',
     '=EA', '=EB', '=EC', '=ED', '=EE', '=EF',
     '=F0', '=F1', '=F2', '=F3', '=F4', '=F5',
     '=F6', '=F7', '=F8', '=F9', '=FA', '=FB',
     '=FC', '=FD', '=FE', '=FF');
  // are there "forbidden" characters in the string?
  for($i=0; $i<strlen($line) && ord($line[$i])<=127 ; $i++);
  if ($i<strlen($line)) { // yes, there are. So lets encode them!
    $from=$i;
    for($to=strlen($line)-1; ord($line[$to])<=127; $to--);
    // lets scan for the start and the end of the to be encoded _words_
    for(;$from>0 && $line[$from] != ' '; $from--);
    if($from>0) $from++;
    for(;$to<strlen($line) && $line[$to] != ' '; $to++);
    // split the string into the to be encoded middle and the rest
    $begin=substr($line,0,$from);
    $middle=substr($line,$from,$to-$from);
    $end=substr($line,$to);
    // ok, now lets encode $middle...
    $newmiddle="";
    for($i=0; $i<strlen($middle); $i++)
      $newmiddle .= $qp_table[ord($middle[$i])];
    // now we glue the parts together...
    $line=$begin.'=?'.$www_charset.'?Q?'.$newmiddle.'?='.$end;
  }
  return $line;
}
}

/*
 * generate a message-id for posting.
 * $identity: a string containing informations about the article, to
 *     make a md5-hash out of it.
 *
 * returns: a complete message-id
 */
function generate_msgid($identity) {
  global $CONFIG, $msgid_generate,$msgid_fqdn;
  switch($msgid_generate) {
    case "no":
      // no, we don't want to generate a message-id.
      return false;
      break;
    case "md5":
      if($CONFIG['email_tail'][0] !== '@') {
        $mymsgid = '@'.$CONFIG['email_tail'];
      } else {
	$mymsgid = $CONFIG['email_tail'];
      }
      return '<'.md5($identity).$mymsgid.'>';
      break;
    default:
      return false;
      break;
  }
}

function check_rate_limit($name,$set=0,$gettime=0) {
	    global $CONFIG,$spooldir;
	    if(strcasecmp($name, $CONFIG['anonusername']) == 0) {
	      $name = session_id();
	    }
	    $ratefile=$spooldir.'/'.strtolower($name).'-rate.dat';
            $postqty=0;
	    $first=0;
            $newrate=array();
            if(is_file($ratefile)) {
              $ratedata='';
              $ratefp=fopen($ratefile,'r');
              while(!feof($ratefp)) {
                $ratedata.=fgets($ratefp,1000);
              }
              fclose($ratefp);
              $rate=unserialize($ratedata);
	      sort($rate);
              foreach($rate as $ratepost) {
                if($ratepost > (time() - 3600)) {
                  $postqty=$postqty+1;
                  $newrate[]=$ratepost;
	          if($first == 0) {
		    $oldest = $ratepost;
		    $first=1;
		  }
                }
              }
            }
            $newrate[]=time();
	    if($set) {
              $ratefp=fopen($ratefile,'w');
              fputs($ratefp,serialize($newrate));
              fclose($ratefp);
              $postqty=$postqty+1;
	    }
	    $rate_limit = get_user_config($name, 'rate_limit');
            if(($rate_limit !== FALSE) && ($rate_limit > 0)) {
              $CONFIG['rate_limit'] = $rate_limit;
            }
	    $postsremaining = $CONFIG['rate_limit']-$postqty;
	    if($gettime) {
	      $wait=(3600-(time()-$oldest))/60;
	      return($wait);
	    } else {
	      return($postsremaining);
	    }
}

/*
 * Post an article to a newsgroup
 *
 * $subject: The Subject of the article
 * $from: The authors name and email of the article
 * $newsgroups: The groups to post to
 * $ref: The references of the article
 * $body: The article itself
 */
function message_post($subject,$from,$newsgroups,$ref,$body,$encryptthis=null,$encryptto=null,$authname=null,$followupto=null) {
  global $server,$port,$send_poster_host,$text_error,$CONFIG;
  global $www_charset,$config_dir,$spooldir;
  global $msgid_generate,$msgid_fqdn,$rslight_version;
  flush();
  $myconfig = false;
  if(file_exists($config_dir.'/userconfig/'.$authname.'.config')) {
    $userconfig = unserialize(file_get_contents($config_dir.'/userconfig/'.$authname.'.config'));  
    $myconfig = true;
  }
  if(isset($encryptthis)) {
    $workpath = $config_dir."users/";
    $username = trim(strtolower($encryptto));
    $userFilename = $workpath.$username;
    if((!is_file($userFilename)) || $encryptto == $CONFIG['anonusername']) {
      $response = "Cannot encrypt to $encryptto. No such user";
      return $response;
    }
  }

  $msgid=generate_msgid($subject.",".$from.",".$newsgroups.",".$ref.",".$body);
/*
 * SPAM CHECK
 */
  if ((isset($CONFIG['spamassassin']) && ($CONFIG['spamassassin'] == true))) {
	  $spam_result_array = check_spam($subject,$from,$newsgroups,$ref,$body,$msgid);
	  $res = $spam_result_array['res'];
	  $spamresult = $spam_result_array['spamresult'];
	  $spamcheckerversion = $spam_result_array['spamcheckerversion'];
	  $spamlevel = $spam_result_array['spamlevel'];
	  $spam_fail = $spam_result_array['spam_fail'];
  } 
  $ns=nntp_open($server,$port);
  if ($ns != false) {
    fputs($ns,"POST\r\n");
    $weg=line_read($ns);
    $t = explode(' ', $weg);
    if($t[0] != "340") {
	  nntp_close($ns);
	  return $weg;
    }


    fputs($ns,'Subject: '.encode_subject($subject)."\r\n");
// For Synchronet use
    if (isset($fromname) && (isset($CONFIG['synchronet']) && ($CONFIG['synchronet'] == true))) {
//    if ( isset($fromname) && isset($CONFIG['synchronet']) ) {
        fputs($ns,'To: '.$fromname."\r\n");
         $fromname="";
    }	
    
// X-Rslight headers
    if ((isset($CONFIG['spamassassin']) && ($CONFIG['spamassassin'] == true))) {
	if(isset($res) && $spam_fail == 0) {
		fputs($ns,$spamcheckerversion."\r\n");
        if(strpos($spamlevel, '*') !== false)
            fputs($ns,$spamlevel."\r\n");
		if($res === 1) {
			fputs($ns,"X-Rslight-Original-Group: ".$newsgroups."\r\n");
			$newsgroups=$CONFIG['spamgroup'];
		} 
	}
    } 
    fputs($ns,'From: '.$from."\r\n");
    if($followupto !== null) {
      fputs($ns,'Followup-To: '.$followupto."\r\n");
    }
    fputs($ns,'Newsgroups: '.$newsgroups."\r\n");
	$sitekey=password_hash($CONFIG['thissitekey'].$msgid, PASSWORD_DEFAULT);
    fputs($ns,'X-Rslight-Site: '.$sitekey."\r\n");
    fputs($ns,'X-Rslight-Posting-User: '.hash('sha1', $from.$_SERVER['HTTP_HOST'].$CONFIG['thissitekey'])."\r\n");
    if(isset($encryptthis)) {
      fputs($ns,'X-Rslight-To: '.$encryptto."\r\n"); 
      $CONFIG['postfooter']="";
    }
    fputs($ns,"Mime-Version: 1.0\r\n");
    fputs($ns,"Content-Type: text/plain; charset=".$www_charset."; format=flowed\r\n");
    fputs($ns,"Content-Transfer-Encoding: 8bit\r\n");
    fputs($ns,"User-Agent: Rocksolid Light ".$rslight_version."\r\n");
    if ($send_poster_host)
      @fputs($ns,'X-HTTP-Posting-Host: '.gethostbyaddr(getenv("REMOTE_ADDR"))."\r\n");
    if (($ref!=false) && (count($ref)>0)) {
      // strip references
      if(strlen(implode(" ",$ref))>900) {
        $ref_first=array_shift($ref);
        do {
          $ref=array_slice($ref,1);
        } while(strlen(implode(" ",$ref))>800);
        array_unshift($ref,$ref_first);
      }
      fputs($ns,'References: '.implode(" ",$ref)."\r\n");
    }
    if (isset($CONFIG['organization']))
      fputs($ns,'Organization: '.quoted_printable_encode($CONFIG['organization'])."\r\n");
    $body=trim($body);
    if ($userconfig['signature'] !== '' && $myconfig) {
      $body.="\n\n-- \n".$userconfig['signature'];
    } else { 
      if ((isset($CONFIG['postfooter'])) && ($CONFIG['postfooter']!="")) {
        $postfooter = preg_replace('/\{DOMAIN\}/', "\n".$_SERVER['HTTP_HOST'], $CONFIG['postfooter']);
        $body.="\n\n-- \n".$postfooter; 
      }
    }
    fputs($ns,'Message-ID: '.$msgid."\r\n");
    if ($userconfig['xface'] !== '' && $myconfig) {
      fputs($ns,'X-Face: '.$userconfig['xface']."\r\n");
    }
    $body=str_replace("\n.\r","\n..\r",$body);
    $body=str_replace("\r",'',$body);
    $body=stripSlashes($body);
// Encrypt?
      if(isset($encryptthis)) {
        $encryptkey=get_user_config($encryptto, "encryptionkey");

        $body=chunk_split(rslight_encrypt($body, $encryptkey));
        $body="-- RSLIGHT DAT START\n".$body."-- RSLIGHT DAT END\n";
      }
    $body=rtrim($body);
    fputs($ns,"\r\n".$body."\r\n.\r\n");
    $message=line_read($ns);
    nntp_close($ns);
  } else {
    $message=$text_error["post_failed"];
  }
  // let thread.php ignore the cache for this group, so this new
  // article will be visible instantly
  $groupsarr=explode(",",$newsgroups);
  foreach($groupsarr as $newsgroup) {
    $cachefile=$spooldir.'/'.$newsgroup.'-cache.txt';
    @unlink($cachefile);
  }
  return $message;
}
function message_post_with_attachment($subject,$from,$newsgroups,$ref,$body,$encryptthis,$encryptto,$authname) {
  global $server,$port,$send_poster_host,$CONFIG,$text_error;
  global $config_dir,$www_charset,$spooldir;
  global $msgid_generate,$msgid_fqdn;
  global $CONFIG;
  flush();
  $myconfig = false;
  if(file_exists($config_dir.'/userconfig/'.$authname.'.config')) {
    $userconfig = unserialize(file_get_contents($config_dir.'/userconfig/'.$authname.'.config')); 
    $myconfig = true;
  }
  $msgid=generate_msgid($subject.",".$from.",".$newsgroups.",".$ref.",".$body);
/*
 * SPAM CHECK
 */
  if (isset($CONFIG['spamassassin']) && ($CONFIG['spamassassin'] == true)) {
	  $spam_result_array = check_spam($subject,$from,$newsgroups,$ref,$body,$msgid);
	  $res = $spam_result_array['res'];
	  $spamresult = $spam_result_array['spamresult'];
	  $spamcheckerversion = $spam_result_array['spamcheckerversion'];
	  $spamlevel = $spam_result_array['spamlevel'];
  }
  move_uploaded_file($_FILES["photo"]["tmp_name"], $spooldir."/upload/" . $_FILES["photo"]["name"]);
  $ns=nntp_open($server,$port);
  if ($ns != false) {
    fputs($ns,"POST\r\n");
    $weg=line_read($ns);
    fputs($ns,'Subject: '.encode_subject($subject)."\r\n"); 
// X-Rslight headers
    if(isset($res)) {
        fputs($ns,$spamcheckerversion."\r\n");
		if(strpos($spamlevel, '*') !== false)
			fputs($ns,$spamlevel."\r\n");
		if($res === 1) {
			fputs($ns,"X-Rslight-Original-Group: ".$newsgroups."\r\n");
			$newsgroups=$CONFIG['spamgroup'];
        }
    }
    $sitekey=password_hash($CONFIG['thissitekey'].$msgid, PASSWORD_DEFAULT);
    fputs($ns,'X-Rslight-Site: '.$sitekey."\r\n");
    fputs($ns,'X-Rslight-Posting-User: '.hash('sha1', $from.$_SERVER['HTTP_HOST'].$CONFIG['thissitekey'])."\r\n");
    if(isset($encryptthis))
      fputs($ns,'X-Rslight-To: '.$encryptto."\r\n");    
    fputs($ns,'From: '.$from."\r\n");
    fputs($ns,'Newsgroups: '.$newsgroups."\r\n");
    fputs($ns,"Mime-Version: 1.0\r\n");
/*
	fputs($ns,"Content-Type: text/plain; charset=".$www_charset."; format=flowed\r\n");
*/
	if ($send_poster_host)
      @fputs($ns,'X-HTTP-Posting-Host: '.gethostbyaddr(getenv("REMOTE_ADDR"))."\r\n");
    if (($ref!=false) && (count($ref)>0)) {
      // strip references
      if(strlen(implode(" ",$ref))>900) {
        $ref_first=array_shift($ref);
        do {
          $ref=array_slice($ref,1);
        } while(strlen(implode(" ",$ref))>800);
        array_unshift($ref,$ref_first);
      }
      fputs($ns,'References: '.implode(" ",$ref)."\r\n");
    }
    if (isset($CONFIG['organization']))
      fputs($ns,'Organization: '.quoted_printable_encode($CONFIG['organization'])."\r\n");
    if ($userconfig['signature'] !== '' && $myconfig) {
      $body.="\n-- \n".$userconfig['signature'];
    } else {
      if ((isset($CONFIG['postfooter'])) && ($CONFIG['postfooter']!="")) {
        $postfooter = preg_replace('/\{DOMAIN\}/', "\n".$_SERVER['HTTP_HOST'], $CONFIG['postfooter']);
        $body.="\n-- \n".$postfooter;
      }
    }
/*
    if ((isset($file_footer)) && ($file_footer!="")) {
      $footerfile=fopen($file_footer,"r");
      $body.="\n".fread($footerfile,filesize($file_footer));
      fclose($footerfile);
        }
*/
	$boundary=uniqid('', true);
	$body.="\r\n--------------".$boundary."\r\n";
    fputs($ns,'Message-ID: '.$msgid."\r\n");
    if ($userconfig['xface'] !== '' && $myconfig) {
      fputs($ns,'X-Face: '.$userconfig['xface']."\r\n");
    }
    fputs($ns,'Content-Type: multipart/mixed;boundary="------------'.$boundary.'"');
    fputs($ns,"\r\n");
    $contenttype = shell_exec('file -b --mime-type '.$spooldir.'/upload/'.$_FILES['photo']['name']);
    $contenttype = rtrim($contenttype);
    $b64file = shell_exec('uuencode -m '.$spooldir.'/upload/'.$_FILES['photo']['name'].' '.$_FILES['photo']['name'].' | grep -v \'begin-base64\|====\'');
    $body.='Content-Type: '.$contenttype.';';
    $body.="\r\n name=".$_FILES['photo']['name'];
    $body.="\r\nContent-Transfer-Encoding: base64";
    $body.="\r\nContent-Disposition: attachment;";
    $body.="\r\n filename=".$_FILES['photo']['name'];
    $body.="\r\n";
    $body.="\r\n".$b64file;
    $body.="\r\n--------------".$boundary."--\r\n";

// Headers end here

    $body=str_replace("\n.\r","\n..\r",$body);
    $body=str_replace("\r",'',$body);
    $body=stripSlashes($body);

    fputs($ns,"\r\nThis is a multi-part message in MIME format.\r\n");
    fputs($ns,"--------------".$boundary."\r\n");
    fputs($ns,"Content-Type: text/plain; charset=utf-8\r\n");
    fputs($ns,"Content-Transfer-Encoding: 7bit\r\n");
// Encrypt?
      if(isset($encryptthis)) {
        $encryptkey=get_user_config($encryptto, "encryptionkey");

        $body=chunk_split(rslight_encrypt($body, $encryptkey));
        $body="-- RSLIGHT DAT START\n".$body."-- RSLIGHT DAT END\n";
      }
// Body sent here
	fputs($ns,"\r\n".$body."\r\n.\r\n");

	$message=line_read($ns);
    nntp_close($ns);
// clean up attachment file
  unlink($spooldir.'/upload/'.$_FILES["photo"]["name"]);
  } else {
    $message=$text_error["post_failed"];
  }
  // let thread.php ignore the cache for this group, so this new
  // article will be visible instantly
  $groupsarr=explode(",",$newsgroups);
  foreach($groupsarr as $newsgroup) {
    $cachefile=$spooldir.'/'.$newsgroup.'-cache.txt';
    @unlink($cachefile);
  }
  return $message;
}
?>
