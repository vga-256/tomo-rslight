    <?php
    include "config.inc.php";
    include ("$file_newsportal"); 
    include $bbsroot_dir."/admintools/rslight-lib.php";
    if(file_exists($config_dir."/nntp.disable")) {
       clearstatcache(true, $config_dir."/nntp.disable");
       $parent_pid = file_get_contents($lockdir.'/rslight-nntp.lock', IGNORE_NEW_LINES);
       posix_kill($parent_pid, SIGTERM);
       exit;
    }
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

    if(isset($CONFIG['enable_all_networks']) && $CONFIG['enable_all_networks'] == true) {
        $bind="0.0.0.0";
    } else {
        $bind=$CONFIG['local_server'];
    }
    server_loop($bind, $CONFIG['local_port']);
    
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
	GLOBAL
$CONFIG,$logdir,$lockdir,$webserver_uid,$webserver_gid,$installed_path,
$config_path,$groupconfig,$workpath,$path,$spooldir,$nntp_group,$auth_ok;
	$logfile=$logdir.'/nntp.log';
	$lockfile = $lockdir . '/rslight-nntp.lock';
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
	$sock = stream_socket_server(
		'tcp://'.$address.':'.$port,
		$errno,
		$errstr,
		STREAM_SERVER_BIND|STREAM_SERVER_LISTEN
	);
/* Change to non root user */
  $uinfo=posix_getpwnam($CONFIG['webserver_user']);
  change_identity($uinfo["uid"],$uinfo["gid"]);
/* Everything below runs as $CONFIG['webserver_user'] */

        echo "waiting for clients to connect\n";

        while ($__server_listening)
        {
            $connection = stream_socket_accept($sock);
            if ($connection === false)
            {
                usleep(100);
            }elseif ($connection > 0)
            {
                handle_client($sock, $connection);
            }else
            {
                echo "error: ".socket_strerror($connection);
                file_put_contents($logfile, "\n".format_log_date()." error: ".socket_strerror($connection), FILE_APPEND); 
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
            fclose($ssock);
            interact($csock, false);
            fclose($csock);
        }else
        {
            fclose($csock);
        }
    }
?>
