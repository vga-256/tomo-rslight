<?php
echo '<center>';
echo '<font size="1em">';
$pubkeyfile = getcwd().'/../tmp/pubkey.pem';
if(is_file($pubkeyfile)) {
    echo 'sitename: '.$CONFIG[site_shortname].'&nbsp;&nbsp;';
    echo '<a href="/tmp/pubkey.pem" target=_blank>public key</a>';
    echo '<br />';
}
echo '<i>rocksolid light</i> '.$rslight_version;
echo '<br />';
echo '<a href="https://github.com/novabbs/rocksolid-light" target=_blank>clearnet</a>';
echo '<a href="http://rslight.i2p/getrslight" target=_blank>i2p</a>';
echo '<a href="http://git.fwfwqtpi2ofmehzdxe3e2htqfmhwfciwivpnsztv7dvpuamhr72ktlqd.onion/novabbs/rocksolid-light" target=_blank>tor</a>';
echo '</font>';
echo '</center>';
?>
