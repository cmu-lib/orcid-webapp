<?php
  /* ORCID-webapp
   *   @copyright  2014-2015 Carnegie Mellon University   
   *                                                    
  
      _config.php   the ORCID app configuration file
      define variables & functions for all the pages to use
     main modifiable ones are 
      - ORCID_PRODUCTION [true/false] use sandbox or real orcid?
      - DEBUG [true/false] print debugging info at top of pages
      - OAUTH_CLIENT_ID [string]  credential from orcid  - sandbox & production
      - OAUTH_CLIENT_SECRET [string] secret password for your app from orcid
      - OAUTH_REDIRECT_URI [URL] link callback page for your app
      - LDAP_SERVER [string] server from which to get user info 
      - LOGDIR [string] - place to put log files
   */

define('ORCID_PRODUCTION', false); 
                       // false=>sandbox; change to true when ready for production

define('DEBUG', false);   // top of pages filled with useful(?) info


/* start editable ----------------------------------------------------------------------- */
// Register your client at https://orcid.org/developer-tools and replace the details below 
// note: you just need to get sandbox credentials to get started

if (ORCID_PRODUCTION) { 
  // credentials to make real ORCID IDs in their production system
  define('OAUTH_CLIENT_ID', 'XXXX-XXXX-XXXX-XXXX');
  define('OAUTH_CLIENT_SECRET', 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx');
} else {  //sandbox credentials:
  define('OAUTH_CLIENT_ID', 'XXXX-XXXX-XXXX-XXXX');
  define('OAUTH_CLIENT_SECRET', 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx');
}

// hostname & path for this webapp
//  the REDIRECT URL and CLAIM URL are based on this.
define('WEBAPP_LOCATION', 'orcid.university.edu');


define('LDAP_SERVER', 'ldap.missing.edu'); //the ldap server
// note: your server is most definitely setup differently than ours(!)  
//       you'll need to modify code in create.php & link.php to get your info properly


// LOG files
define('LOGDIR', '/tmp/');    // where to put the logs?  
                              // I'd suggest  /var/log/orcid/ , but /tmp works now


/* end editable ------------------------------------------------------------------------- */

// --- Derived parameters:
//      you shouldn't need to change these 
if (ORCID_PRODUCTION) {
  // production endpoints
  define('OAUTH_AUTHORIZATION_URL', 'https://orcid.org/oauth/authorize');
  define('OAUTH_TOKEN_URL', 'https://pub.orcid.org/oauth/token'); // public
  //define('OAUTH_TOKEN_URL', 'https://api.orcid.org/oauth/token'); // members
  define('OAUTH_ROOT_URL', 'http://api.orcid.org/'); // members
  define('OAUTH_RESOURCE_URL', 'https://api.orcid.org/v1.1/orcid-profile');
} else {  //sandbox:
  // sandbox endpoints
  define('OAUTH_AUTHORIZATION_URL', 'https://sandbox.orcid.org/oauth/authorize');
  define('OAUTH_TOKEN_URL', 'https://pub.sandbox.orcid.org/oauth/token'); // public
  //define('OAUTH_TOKEN_URL', 'https://api.sandbox.orcid.org/oauth/token'); // members
  define('OAUTH_ROOT_URL', 'http://api.sandbox.orcid.org/'); // members
  define('OAUTH_RESOURCE_URL', 'https://api.sandbox.orcid.org/v1.1/orcid-profile');

} //sandbox

// ** URLs in our app that orcid.org needs to use
//
// the page that ORCID will go to after logging in to link your accounts [link.php]
// ** NOTE: THIS URL MUST BE REGISTERED WITH YOUR APP TO WORK **
define('OAUTH_REDIRECT_URI', 'https://'.WEBAPP_LOCATION.'/app/callback'); //the callback page 
//
// orcid's server will call this page when user eventually logs into their new orcid account
//  note: outside of 'app' since it can NOT authenticate
define('CLAIM_WEBHOOK', 'http://'.WEBAPP_LOCATION.'/claimed.php?orcid=');



// LOG files
$login_log=   LOGDIR."login_log.txt";    // user logged in
$status_log=  LOGDIR."status_log.txt";
$linked_log=  LOGDIR."orcid_linked.txt";
$error_log=   LOGDIR."error_log.txt";  // errors
$submit_log=  LOGDIR."submit_log.txt";
$user_log=    LOGDIR."orcid_created.txt";
$idm_log=     LOGDIR."idm_log.txt";


// ------------------------ LOGGING FUNCTIONS 
//  ( probably don't belong here, but makes things easier )

function log_login()
{ global $login_log, $CMU_EPPN; 
  file_put_contents($login_log, date("Ymd:G:i:s")." ".$CMU_EPPN." login create\n", FILE_APPEND | LOCK_EX);
}
function log_status()
{ global $login_log, $CMU_EPPN, $status; 
  file_put_contents($login_log, date("Ymd:G:i:s")." ".$CMU_EPPN." status= ".$status."\n", FILE_APPEND | LOCK_EX);
}

function log_submission()
{ global $submit_log, $CMU_EPPN, $user_given_names, $user_sur_names, $user_email; 
  file_put_contents($submit_log, date("Ymd:G:i:s")." ".$CMU_EPPN." submit |".$user_given_names."|".$user_sur_names."|".$user_email."|\n", FILE_APPEND | LOCK_EX);
}

function log_user()   // date USER msg
{ global $user_log, $CMU_EPPN, $status, $ORCID;  
  file_put_contents($user_log, date("Ymd:G:i:s")." ".$CMU_EPPN." ".$status." ".$ORCID." "."\n", FILE_APPEND | LOCK_EX);
}

function log_linked()
{ global $linked_log, $CMU_EPPN, $ORCID, $status; 
  file_put_contents($linked_log, date("Ymd:G:i:s")." ".$CMU_EPPN." ".$status." ".$ORCID."\n", FILE_APPEND | LOCK_EX);
}
function log_error($msg)   // date USER msg
{ global $error_log, $CMU_EPPN; 
  file_put_contents($error_log, date("Ymd:G:i:s")." ".$CMU_EPPN.": ".$msg."\n", FILE_APPEND | LOCK_EX);
}


function add_to_IDM()   // add id & orcid to the campus identity management system using 
                        // external orcid-add program (not included)
{ global $idm_log, $CMU_EPPN, $ORCID;  
  // pull out uid from full e-mail address
  $arr = explode("@", $CMU_EPPN, 2);
  $CMU_UID = $arr[0];
  file_put_contents($idm_log, date("Ymd:G:i:s")." ".$CMU_UID." http://orcid.org/".trim($ORCID)." "." [START]\n", FILE_APPEND | LOCK_EX);
  exec('/usr/local/bin/orcid-add '.$CMU_UID.' http://orcid.org/'.trim($ORCID).' &>> '.$idm_log);
  file_put_contents($idm_log, date("Ymd:G:i:s")." ".$CMU_UID." http://orcid.org/".trim($ORCID)." "." [*END*]\n", FILE_APPEND | LOCK_EX);
}




// ----- end _config.php
?>