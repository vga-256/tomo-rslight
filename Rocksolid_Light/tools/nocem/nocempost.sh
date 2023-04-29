#!/bin/bash

gpglocaluser="XXXXXXXX"
nntpserver="news.example.com"
nntpuser="nntpusername"
nntppassword="nntppassword"

gpg2 --local-user $gpglocaluser --clearsign -a nocem.out 

newsserver=$nntpserver
today=$(date -u +%F-%H:%M)
id=$(od -xvAn -N8 < /dev/urandom | tr -cd 0-9a-f)
rpost $newsserver -U $nntpuser -P $nntppassword <<%end
$(<header.out)

$(<nocem.out.asc)

%end
