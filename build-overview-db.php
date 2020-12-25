<?php
####################
# This script creates articles-overview.db3 in your spool directory from
# pre 0.6.7 -overview files
# This is REQUIRED to upgrade from pre 0.6.7 versions
# This script MUST be run as your web user (debian = www-data)
#
# To create the file do the following:
# Disable access to site (to disallow posting)
# Disable cron job for rslight
# Disable rslight nntp servers running (kill PID)
# cd to your spool directory. Default is /var/spool/rslight
# For each section you must run this script: 
# php /path/to/script rocksolid
# php /path/to/script spoolnews
# php /path/to/script othersection 
# etc...
# Re-enable cron job and access to site
# That should be all that is necessary
####################

$database = 'articles-overview.db3';
$table = 'overview';
$dbh = rslight_db_open($database, $table);

  $sql = "INSERT INTO overview(newsgroup, number, msgid, date, name, subject) VALUES(?,?,?,?,?,?)";
  $stmt = $dbh->prepare($sql);
  $overviewfp = fopen($argv[1].'-overview', 'r');
  echo "Building $database";
  $i = 0;
  while($data=fgets($overviewfp)) {
    $parts=preg_split("/(:#rsl#:|\t)/", $data);;
    $stmt->execute([$parts[0], $parts[1], $parts[2], $parts[3], $parts[4], $parts[5]]);
      if($i++ > 100) {
        echo '.';
        $i = 0;
      }
  }
  echo "\nFinished.\n"; 
  fclose($overviewfp);
  $dbh = null;

function rslight_db_open($database, $table) {
  try {
    $dbh = new PDO('sqlite:'.$database);
  } catch (PDOExeption $e) {
    echo 'Connection failed: '.$e->getMessage();
    exit;
  }
  $dbh->exec("CREATE TABLE IF NOT EXISTS $table(
     id INTEGER PRIMARY KEY,
     newsgroup TEXT,
     number TEXT,
     msgid TEXT,
     date TEXT,
     name TEXT,
     subject TEXT)");
  return($dbh);
}
?>
