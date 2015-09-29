<html>
<head><title>ORCID-webapp Claimed</title></head>
<body>
<?php
  /* ORCID-webapp
   *   @copyright  2014-2015 Carnegie Mellon University   
   *                                                    
  */
 //       claimed.php 
 //       logs the fact that an ORCID record was claimed
 // specifically, it is the webhook URL which is called when a given ORCID record is modified.
 // since the user is modifying the record we created, he must be claiming it.
 // 

$claimed_log="/tmp/orcid_claimed.txt";   // where to log it
$ORCID=$_GET['orcid'];  // we put ORCID id into 'orcid' parameter of this url

if (empty($ORCID)) $ORCID='MISSING_ORCID';

function log_claimed()
{ global $claimed_log, $ORCID; 
  file_put_contents($claimed_log, date("Ymd:G:i:s")." ".$ORCID." claimed\n", FILE_APPEND | LOCK_EX);
}

    log_claimed();

    echo "ORCID ".$ORCID." claimed.";
?>
     ORCID ID claimed.
</body>
</html>


