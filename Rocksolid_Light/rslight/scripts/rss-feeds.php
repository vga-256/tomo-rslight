#!/usr/local/bin/php
<?php
   include "config.inc.php";
   include "newsportal.php";

   $rssdir = $config_dir.'/rss/';
   $rssfiles = array();
   if(isset($argv[1])) {
     $rssfiles[0] = $argv[1];
   } else {
     $rssfiles = array_diff(scandir($rssdir), array('..', '.'));
   }
   foreach($rssfiles as $rssfile) {
     if(!is_file($config_dir.'/rss/'.$rssfile)) {
       continue;
     }
     $body = '';
     unset($RSS);
     $RSS = get_rss_config($config_dir.'/rss/'.$rssfile);
     if($RSS['enable'] !== '1') {
       continue;
     }
     if(filemtime($spooldir.'/'.$rssfile.'-rss-timer') + $RSS['timer'] > time()) {
       if(!is_file($rssdir.'/debug')) {
         continue;
       } 
     }
     $xmlData = file_get_contents($RSS['url']);
     $xml = simplexml_load_string($xmlData);
     if($RSS['root'] != '') {
       $xmlstart = $xml->{$RSS['root']};
     } else {
       $xmlstart = $xml;
     }
     foreach($xmlstart->{$RSS['item']} as $item)
     {
       if(trim($item->{$RSS['link']}) == '') {
         $item->{$RSS['link']} = $item->{$RSS['link']}[href];
       } else {
         $item->{$RSS['link']} = $item->{$RSS['link']};
       }
       $body.=$item->{$RSS['title']}."\n";
       if(isset($RSS['urlprefix']) && ($RSS['urlprefix'] !== '')) {
         $url = $RSS['urlprefix'].$item->{$RSS['link']};
       } else {
         $url = $item->{$RSS['link']};
       }
       if(isset($RSS['urlprefixalt']) && ($RSS['urlprefixalt'] !== '')) {
         $urlalt = $RSS['urlprefixalt'].$item->{$RSS['link']};
       } else {
         $urlalt = '';
       }
       if(substr($url,0,4) !== "http") {
         $urlprefix = explode('/', $RSS['url']);
         $url = $urlprefix[0].'/'.$urlprefix[1].'/'.$urlprefix[2].$item->{$RSS['link']};
       } 
       $body.=$url."\n";
       if($urlalt !== '') {
         $body.=$urlalt."\n";
       }
       if(isset($RSS['date_namespaceuri']) && ($RSS['date_namespaceuri'] !== '')) { 
         $dc_date = $item->children($RSS['date_namespaceuri']);
         $body.=date("F j, Y, g:i A", strtotime($dc_date));
       } else {
         $body.=date("F j, Y, g:i A", strtotime($item->{$RSS['date']}));
       }
       $body.="\n";
       $body.=trim($item->{$RSS['content']});
       $body.="\n--------------------\n";
     }
     if(strpos($RSS['postfrom'], '@') === false) {
       $RSS['postfrom'] = $RSS['postfrom'].$CONFIG['email_tail'];
     }
     if(isset($RSS['followupto']) && ($RSS['followupto'] !== '')) {
       $followupto = $RSS['followupto'];
     } else {
       $followupto = null;
     }
     $body = strip_tags($body);

     if(is_file($rssdir.'/debug')) {
       echo $body;
     } else {
       echo message_post($RSS['message_subject'], $RSS['postfrom'], $RSS['newsgroup'], null, $body, null, null, null, $followupto)."\n";
       touch($spooldir.'/'.$rssfile.'-rss-timer');
     }
   }
   
   function get_rss_config($rssfile) {
      $RSS = include($rssfile);
      return($RSS);
   }
?>
