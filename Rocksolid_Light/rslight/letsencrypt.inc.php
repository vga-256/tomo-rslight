<?php 

/* Set paths for fullchain.pem abnd privkey.pem */
$letsencrypt['fullchain'] = file_get_contents("/etc/letsencrypt/live/<domain>/fullchain.pem");
$letsencrypt['privkey'] = file_get_contents("/etc/letsencrypt/live/<domain>/privkey.pem");

/* Please do not change anything below */
$letsencrypt['pem_private_key'] = openssl_pkey_get_private($letsencrypt['privkey']);
$pem_public_key = openssl_pkey_get_details($letsencrypt['pem_private_key'])['key'];

$letsencrypt['server.pem'] = $letsencrypt['fullchain'];
$letsencrypt['pubkey.pem'] = $pem_public_key;
