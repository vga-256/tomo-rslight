#!/usr/bin/php

    <?php
    include "config.inc.php";
    include ("$file_newsportal");
    /**
      * Listens for requests and forks on each connection
      */

    $__server_listening = true;

    //error_reporting(E_ALL);
    set_time_limit(0);
    ob_implicit_flush();
    declare(ticks = 1);

    become_daemon();

    /* nobody/nogroup, change to your host's uid/gid of the non-priv user */

    /* handle signals */
    pcntl_signal(SIGTERM, 'sig_handler');
    pcntl_signal(SIGINT, 'sig_handler');
    pcntl_signal(SIGCHLD, 'sig_handler');

    /* change this to your own host / port */
    server_loop($local_server, $local_port);
    
    /**
      * Change the identity to a non-priv user
      */
    function change_identity( $uid, $gid )
    {
        if( !posix_setgid( $gid ) )
        {
            print "Unable to setgid to " . $gid . "!\n";
            exit;
        }

        if( !posix_setuid( $uid ) )
        {
            print "Unable to setuid to " . $uid . "!\n";
            exit;
        }
    }
    /**
      * Creates a server socket and listens for incoming client connections
      * @param string $address The address to listen on
      * @param int $port The port to listen on
      */
    function server_loop($address, $port)
    {
        GLOBAL $__server_listening;
        GLOBAL $config_name,$webserver_uid,$webserver_gid,$installed_path,$config_path,$groupconfig,$workpath,$path,$spooldir,$group,$auth_ok;

	$lockfile = sys_get_temp_dir() . '/'.$config_name.'-nntp.lock';
	$pid = file_get_contents($lockfile);
	if (posix_getsid($pid) === false || !is_file($lockfile)) {
	   print "Starting Rocksolid Light NNTP Server...\n";
	   file_put_contents($lockfile, getmypid()); // create lockfile
	} else {
	   print "Rocksolid Light NNTP Server currently running\n";
	   exit;
	}

	$auth_ok = 0;
	$user = "";
	$pass = "";
        if(($sock = socket_create(AF_INET, SOCK_STREAM, 0)) < 0)
        {
            echo "failed to create socket: ".socket_strerror($sock)."\n";
            exit();
        }

        if(($ret = socket_bind($sock, $address, $port)) < 0)
        {
            echo "failed to bind socket: ".socket_strerror($ret)."\n";
            exit();
        }

        if( ( $ret = socket_listen( $sock, 0 ) ) < 0 )
        {
            echo "failed to listen to socket: ".socket_strerror($ret)."\n";
            exit();
        }

        socket_set_nonblock($sock);
	change_identity($webserver_uid,$webserver_gid);
        echo "waiting for clients to connect\n";

        while ($__server_listening)
        {
            $connection = @socket_accept($sock);
            if ($connection === false)
            {
                usleep(100);
            }elseif ($connection > 0)
            {
                handle_client($sock, $connection);
            }else
            {
                echo "error: ".socket_strerror($connection);
                die;
            }
        }
    }

    /**
      * Signal handler
      */
    function sig_handler($sig)
    {
        switch($sig)
        {
            case SIGTERM:
            case SIGINT:
                exit();
            break;

            case SIGCHLD:
                pcntl_waitpid(-1, $status);
            break;
        }
    }

    /**
      * Handle a new client connection
      */
    function handle_client($ssock, $csock)
    {
        GLOBAL $__server_listening;

        $pid = pcntl_fork();

        if ($pid == -1)
        {
            /* fork failed */
            echo "fork failure!\n";
            die;
        }elseif ($pid == 0)
        {
            /* child process */
            $__server_listening = false;
            socket_close($ssock);
            interact($csock);
            socket_close($csock);
        }else
        {
            socket_close($csock);
        }
    }

    function interact($msgsock)
    {
	global $installed_path,$config_path,$config_name,$groupconfig,$workpath,$path,$spooldir,$group,$auth_ok,$user,$pass;

	$workpath=$spooldir."/";
	$path=$workpath."articles/";
	$groupconfig=$config_path."groups.txt";
    

/* END CONFIG */

    $group="none";
    /* Send instructions. */
    $msg = "200 Rocksolid Light NNTP Server ready (no posting)\r\n";
    socket_write($msgsock, $msg, strlen($msg));
      do {   
	echo $msg."\r\n";
        if (false === ($buf = socket_read($msgsock, 2048, PHP_NORMAL_READ))) {
            echo "socket_read() failed: reason: " . socket_strerror(socket_last_error($msgsock)) . "\n";
            break;
        }
        if (!$buf = trim($buf)) {
            continue;
        }
        echo $buf."\r\n\r\n";
	$command = explode(' ', $buf);
	$command[0] = strtolower($command[0]);
	if(isset($command[1])) {
  	  $command[1] = strtolower($command[1]);
	}
	if ($command[0] == 'list') {
	    if(isset($command[1])) {
	        $msg = get_list($command[1]);
            } else {
		$msg = get_list("active");
            } 
            socket_write($msgsock, $msg, strlen($msg));
	    continue;
	}
	if ($command[0] == 'post') {
	    if($auth_ok == 0) {
	        $msg = "480 Posting not permitted\r\n";	
		socket_write($msgsock, $msg, strlen($msg));
                continue;
	    }
	    @mkdir($spooldir."/".$config_name."/outgoing/",0755,'recursive');
	    $msg = "340 Send article to be posted\r\n";
	    $postfilename = tempnam($spooldir.'/'.$config_name.'/outgoing', '');
	    $postfilehandle = fopen($postfilename, 'wb');
	    socket_write($msgsock, $msg, strlen($msg));
	    $buf = socket_read($msgsock, 2048, PHP_NORMAL_READ);
	    $buf = socket_read($msgsock, 2048, PHP_NORMAL_READ);
	    while (trim($buf)  != '.') {
		fwrite($postfilehandle, $buf);
		$buf = socket_read($msgsock, 2048, PHP_NORMAL_READ);
	    }
	    fclose($postfilehandle);
	    $msg = "240 Article received OK\r\n";
	    socket_write($msgsock, $msg, strlen($msg));     
	    continue;
	}
	if ($command[0] == 'newgroups') {
            $msg = get_newgroups($command);
            socket_write($msgsock, $msg, strlen($msg));
            continue;
        }
	if ($command[0] == 'authinfo') {
          if($command[1] == 'user') {
            $user = $command[2];
	    if(isset($command[3])) {
		$user = $user." ".$command[3];
	    }
            $msg="381 Enter password\r\n";
	    socket_write($msgsock, $msg, strlen($msg));
            continue;
          } 
	  if ($command[1] == 'pass') {
            if($user == "") {
              $msg="482 Authentication commands issued out of sequence\r\n";
            } else {
              $pass = $command[2];
	      if (check_bbs_auth($user,$pass)) {
	        $auth_ok = 1;
                $msg="281 Authentication succeeded\r\n";
	      } else {
	        $auth_ok = 0;
	        $msg="481 Authentication failed\r\n";
	      }
            }
	    socket_write($msgsock, $msg, strlen($msg));
            continue;
	  }
	  $msg="501 Syntax error\r\n";
          socket_write($msgsock, $msg, strlen($msg));
          continue;
        }
	if ($command[0] == 'mode') {
	    $msg = "200 Rocksolid Light NNRP Server ready (no posting)\r\n";
	    socket_write($msgsock, $msg, strlen($msg));
	    continue;
	}
	if ($command[0] == 'article') {
	    $msg = get_article($command[1], $group);
	    socket_write($msgsock, $msg, strlen($msg));
	    continue;
	}
	if ($command[0] == 'head') {
            $msg = get_header($command[1]);
            socket_write($msgsock, $msg, strlen($msg));
            continue;
        }
	if ($command[0] == 'group') {
	    $group=$command[1];
	    $msg = get_group($group);
	    socket_write($msgsock, $msg, strlen($msg));
	    continue;
	}
	if ($command[0] == 'xgtitle') {
            if(isset($command[1])) {
                $msg = get_title($command[1]);
            } else {
                $msg = get_title("active");
            }
            socket_write($msgsock, $msg, strlen($msg));
            continue;
        }	
	if ($command[0] == 'xover') {
	    $msg = get_xover($command[1]);
	    socket_write($msgsock, $msg, strlen($msg));
	    continue;
	}
	if ($command[0] == 'help') {
	    $msg = "100 Sorry, can't help\r\n";
	    socket_write($msgsock, $msg, strlen($msg));
            continue;
        }
	if ($command[0] == 'quit') {
	    $msg = "205 closing connection - goodbye!\r\n";
	    socket_write($msgsock, $msg, strlen($msg));
            break;
        }
        echo $msg."\r\n";
        $talkback = "500 Syntax error or unknown command\r\n";
        socket_write($msgsock, $talkback, strlen($talkback));
    } while (true);    
        
    }

    /**
      * Become a daemon by forking and closing the parent
      */
    function become_daemon()
    {
        $pid = pcntl_fork();
       
        if ($pid == -1)
        {
            /* fork failed */
            echo "fork failure!\n";
            exit();
        }elseif ($pid)
        {
            /* close the parent */
            exit();
        }else
        {
            /* child becomes our daemon */
            posix_setsid();
            chdir('/');
            umask(0);
            return posix_getpid();

        }
    }

function get_post($command) {
    global $spooldir, $auth_ok;



    return $filename."\r\n";
}

function get_title($mode) {
    global $group,$workpath,$spooldir,$path;
    $mode = strtolower($mode);
    if($mode == "active") {
	$msg="481 descriptions unavailable\r\n";
        return $msg;
    }
    if(!file_exists($spooldir."/".$mode."-title")) {
        $msg="481 descriptions unavailable\r\n";
        return $msg;
    }
    $title = file_get_contents($spooldir."/".$mode."-title");
    $msg="282 list of group and description follows\r\n";
    $msg.=$title;

    $msg.=".\r\n";
    return $msg;
}

function get_xover($articles,$mode) {
    global $group,$workpath,$path;
    if($group == 'none') {
        $msg="412 no newsgroup selected\r\n";
        return $msg;
    }
    $overviewdata = file($workpath.$group."-overview");
    $article_num = explode('-', $articles);
    $first = $article_num[0];
    if(isset($article_num[1]) && is_numeric($article_num[1]))
        $last = $article_num[1];
    else {
	if(strpos($articles, "-")) {
	$thisgroup = $path."/".preg_replace('/\./', '/', $group);
    $articles = scandir($thisgroup);
    $ok_article=array();
    foreach($articles as $article) {
	if(!is_numeric($article)) {
	    continue;
	}
	$ok_article[]=$article;
    }
    sort($ok_article);
    $last = $ok_article[key(array_slice($ok_article, -1, 1, true))];
    if(!is_numeric($last))
        $last = 0;
    } else {
	$last = $first;
    }
   } 
    $msg="224 Overview information follows for articles ".$first." through ".$last."\r\n";
      
    foreach($overviewdata as $overviewline) {
      $article=preg_split("/[\s,]+/", $overviewline);
      for($i=$first; $i<=$last; $i++) {
	if($article[0] === strval($i)) {
	    $msg.=$overviewline;
	} 
      }
    }
    
    $msg.=".\r\n";
    return $msg;
}

function get_article($article, $group) {
    global $path,$group,$groupconfig,$config_name,$spooldir;
    $msg2="";
// By Message-ID
    if($article[0] === '<') {
      $overviewdata = file($spooldir."/".$config_name."-overview", FILE_IGNORE_NEW_LINES);
      foreach($overviewdata as $overviewline) {
	$articledata = explode(':#rsl#:', $overviewline);
	if(!strcasecmp($articledata[2], $article)) {
	  $group=$articledata[0];
	  $article=$articledata[1];
	}
      }
    }
// By article number
    if($group === "") {
        $msg.="412 no newsgroup has been selected\r\n";
        return $msg;
    }
    if(!is_numeric($article)) {
        $msg.="420 no article has been selected\r\n";
        return $msg;
    }
    $thisgroup = $path."/".preg_replace('/\./', '/', $group);
    if(!file_exists($thisgroup."/".$article)) {
        $msg.="430 no such article found\r\n";
        return $msg;
    }
    $thisarticle=file($thisgroup."/".$article, FILE_IGNORE_NEW_LINES);
    foreach($thisarticle as $thisline) {
        if((strpos($thisline, "Message-ID: ") === 0) && !isset($mid[1])) {
	    $mid=explode(': ', $thisline);
        }
        $msg2.=$thisline."\r\n";
    }
    $msg="220 ".$article." ".$mid[1]." article retrieved - head and body follow\r\n";
    return $msg.$msg2;
}

function get_header($article) {
    global $path,$group,$groupconfig;
    if($group === "") {
        $msg.="412 no newsgroup has been selected\r\n";
        return $msg;
    }
    if(!is_numeric($article)) {
        $msg.="420 no article has been selected\r\n";
        return $msg;
    }
    $thisgroup = $path."/".preg_replace('/\./', '/', $group);
    if(!file_exists($thisgroup."/".$article)) {
        $msg.="430 no such article found\r\n";
        return $msg;
    }
    $thisarticle=file($thisgroup."/".$article, FILE_IGNORE_NEW_LINES);
    foreach($thisarticle as $thisline) {
	if($thisline == "") {
	  $msg2.=".\r\n";
	  break;
	}
        if((strpos($thisline, "Message-ID: ") === 0) && !isset($mid[1])) {
            $mid=explode(': ', $thisline);
        }
        $msg2.=$thisline."\r\n";
    }
    $msg="221 ".$article." ".$mid[1]." article retrieved - header follows\r\n";
    return $msg.$msg2;
}

function get_group($group) {
    global $path,$group,$groupconfig;
    $grouplist = file($groupconfig, FILE_IGNORE_NEW_LINES);
    $ok_group=false;
    $count=0;
    foreach($grouplist as $findgroup) {
	$name=explode(' ',$findgroup);
	$name[0]=strtolower($name[0]);
	$group=strtolower($group);
	if(!strcmp($name[0], $group)) {
	    $ok_group=true;
	    break;
	}
    }
    $thisgroup = $path."/".preg_replace('/\./', '/', $group);
    if(!is_dir($thisgroup) || $ok_group === false) {
        $msg.="411 no such news group\r\n";
        $group="";
        return $msg;
    }
    $articles = scandir($thisgroup);
    $ok_article=array();
    foreach($articles as $article) {
	if(!is_numeric($article)) {
	    continue;
	}
	$ok_article[]=$article;
	$count++;
    }
    sort($ok_article);
    $last = $ok_article[key(array_slice($ok_article, -1, 1, true))];
    $first = $ok_article[0];
    if(!is_numeric($last))
        $last = 0;
    if(!is_numeric($first))
        $first = 0;
    $msg="211 ".$count." ".$first." ".$last." ".$group." group selected\r\n";
    return $msg;    
}

function get_newgroups($mode) {
    global $path,$groupconfig;
    $grouplist = file($groupconfig, FILE_IGNORE_NEW_LINES);
//  $mode = strtolower($mode);
  $mode = "active";
  if($mode == "active") {
    $msg = '231 list of newsgroups follows"'."\r\n";
    foreach($grouplist as $findgroup) {
        $name=explode(' ',$findgroup);
        if($name[0][0] === ':')
            continue;
        $thisgroup = $path."/".preg_replace('/\./', '/', $name[0]);
        $articles = scandir($thisgroup);
        $ok_article=array();
        foreach($articles as $article) {
            if(!is_numeric($article)) {
                continue;
            }
            $ok_article[]=$article;
        }
        sort($ok_article);
        $last = $ok_article[key(array_slice($ok_article, -1, 1, true))];
        $first = $ok_article[0];
        if(!is_numeric($last))
            $last = 0;
        if(!is_numeric($first))
            $first = 0;
        $msg.=$name[0]." ".$last." ".$first." n\r\n";
    }
  }
  if($mode == "newsgroups") {
    $msg = '215 list of newsgroups and descriptions follows"'."\r\n";
    foreach($grouplist as $findgroup) {
      if($findgroup[0] === ':')
        continue;
      $msg.=$findgroup."\r\n";
    }
  }
  if($mode == "overview.fmt") {
    $msg="215 Order of fields in overview database.\r\n";
    $msg.="Subject:\r\n";
    $msg.="From:\r\n";
    $msg.="Date:\r\n";
    $msg.="Message-ID:\r\n";
    $msg.="References:\r\n";
    $msg.="Bytes:\r\n";
    $msg.="Lines:\r\n";
    $msg.="Xref:full\r\n";
  }
  if(isset($msg)) {
    return $msg.".\r\n";
  } else {
    $msg="501 Syntax error or unknown command\r\n";
    return $msg.".\r\n";
  }
}

function get_list($mode) {
    global $path,$groupconfig;
    $grouplist = file($groupconfig, FILE_IGNORE_NEW_LINES);
  $mode = strtolower($mode);
  if($mode == "active") {  
    $msg = '215 list of newsgroups follows"'."\r\n";
    foreach($grouplist as $findgroup) {
	$name=explode(' ',$findgroup);
	if($name[0][0] === ':')
	    continue;
	$thisgroup = $path."/".preg_replace('/\./', '/', $name[0]);
	$articles = scandir($thisgroup);
	$ok_article=array();
	foreach($articles as $article) {
	    if(!is_numeric($article)) {
	        continue;
	    }
	    $ok_article[]=$article;
	}
	sort($ok_article);
	$last = $ok_article[key(array_slice($ok_article, -1, 1, true))];
	$first = $ok_article[0];
	if(!is_numeric($last))
	    $last = 0;
	if(!is_numeric($first))
	    $first = 0;
        $msg.=$name[0]." ".$last." ".$first." n\r\n";
    }
  }
  if($mode == "newsgroups") {
    $msg = '215 list of newsgroups and descriptions follows"'."\r\n";
    foreach($grouplist as $findgroup) {
      if($findgroup[0] === ':')
        continue;
      $msg.=$findgroup."\r\n";
    }
  }
  if($mode == "overview.fmt") {
    $msg="215 Order of fields in overview database.\r\n";
    $msg.="Subject:\r\n";
    $msg.="From:\r\n";
    $msg.="Date:\r\n";
    $msg.="Message-ID:\r\n";
    $msg.="References:\r\n";
    $msg.="Bytes:\r\n";
    $msg.="Lines:\r\n";
    $msg.="Xref:full\r\n";
  }
  if(isset($msg)) {
    return $msg.".\r\n";
  } else {
    $msg="501 Syntax error or unknown command\r\n";
    return $msg.".\r\n";
  }
}
    ?>

