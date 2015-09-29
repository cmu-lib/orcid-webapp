<html>
<head>
  <title>ORCID-webapp : Orcid Created</title>
</head>
<body>
<?php  
if(!ORCID_PRODUCTION){   // test stuff.  
  echo '<div id="test-warn-div" class="alert" style="background-color:#f5c120">';
  echo '<strong>Warning: Test mode.  Using sandbox.orcid.org .</strong>';
  echo '</div>';
 }
?>

<pre>
<?php
  /* ORCID-webapp
   *   @copyright  2014-2015 Carnegie Mellon University   
   *                                                    
  */

// create_done.php:   get the posted information for a new account (first name, last name & e-mail),
//  then submit that to ORCID to create a new account.  
//   Also register a webhook to get notified when ORICID is claimed.

include '_config.php';


$status=0; // 0=OK, 
           // 1=problem communicating with orcid, we'll create record soon
           // 2=user with this e-mail already exists
           // 3=missing user data from POSTed form   
$ERROR=""; // so far, so good
$CMU_EPPN = $_SERVER['REMOTE_USER'];
$WARNINGS='';  // place to append warnings [in brackets] for logging
// ----------------------------------------------------------------------------------

// No REMOTE_USER, so page not Shib-protected.  fake it for testing
if(!ORCID_PRODUCTION) { if(strlen($CMU_EPPN)<1) $CMU_EPPN = 'fake_user@university.edu'; }

if(DEBUG)  echo "CMU_EPPN='" . $CMU_EPPN . "'<p>";



// ----------------------------------------------------------------------------------
// ** 0 get the user's data from the POSTED form:
//$user_given_names='Larry'; $user_sur_names='Hardwired-Name'; $user_email='cmuorcidtest_200@mailinator.com'; //test
$user_given_names=$_POST['given_names'];
$user_sur_names=  $_POST['family_names'];
$user_email=      $_POST['email'];

if(empty($user_given_names)||empty($user_sur_names)||empty($user_email)){
  log_error("2. missing POST data from user form: |".$user_given_names."|".$user_dur_names."|".$user_email."|");
  $status = 3;  // missing user data
  $ERROR='Missing required information about the user.';
}

log_submission();  //log the fact that user submitted form

// ** 1. Create authorization token to add new record
// fetch the access token
if($status==0){
 $curl = curl_init();
 curl_setopt_array($curl, array(
       CURLOPT_URL => OAUTH_TOKEN_URL,
       CURLOPT_RETURNTRANSFER => true,
       CURLOPT_HTTPHEADER => array('Accept: application/json'),
       CURLOPT_POST => true,
       CURLOPT_POSTFIELDS => http_build_query(array(
				    'grant_type' => 'client_credentials',
				    'scope' => '/orcid-profile/create',
				    'client_id' => OAUTH_CLIENT_ID,
				    'client_secret' => OAUTH_CLIENT_SECRET    ))
       ));
 
 $result = curl_exec($curl);
 //$info = curl_getinfo($curl);
 $response = json_decode($result, true);
 $ACCESS_TOKEN = $response['access_token'];

 if (DEBUG){
  echo "1. authorization response=";
  print_r($response);
  echo "** ACCESS_TOKEN=" . $ACCESS_TOKEN . "=**";
 }
 if(empty($ACCESS_TOKEN)){ log_error("1. ACCESS_TOKEN empty.");
  $status=1; // problem
  $ERROR='Unable to contact ORCID at this time';
 }
} // status==0


// ** 2. Create record by POSTing XML with user's data
// construct XML message for ORCID    - minimal info
$xml='';
$xml.='<?xml version="1.0" encoding="UTF-8"?>';
$xml.='<orcid-message ';
$xml.='  xmlns:xsi="http://www.orcid.org/ns/orcid ';
$xml.='     https://raw.github.com/ORCID/ORCID-Source/master/orcid-model/src/main/resources/orcid-message-1.1.xsd"';
$xml.='    xmlns="http://www.orcid.org/ns/orcid">';
$xml.='    <message-version>1.1</message-version>';
$xml.='    <orcid-profile>';
$xml.='        <orcid-bio>';
$xml.='            <personal-details>';
$xml.='                <given-names>'.$user_given_names.'</given-names>';
$xml.='                <family-name>'.$user_sur_names.'</family-name>';
$xml.='            </personal-details>';
$xml.='            <contact-details>';
$xml.='                <email primary="true">'.$user_email.'</email>';
$xml.='            </contact-details>';
$xml.='        </orcid-bio>';
$xml.='    </orcid-profile>';
$xml.='</orcid-message>';


if(DEBUG){ // see what we're sending
 echo "<BR>";   echo "XML=<BR><HR>"; echo htmlentities($xml); echo "<BR><HR><BR>[END_XML]";
}

if($status==0) {  //if everything OK, try to create record

 // We send XML via CURL using POST with a http header of text/xml.
 $ch = curl_init();
 // set URL and other appropriate options
 //curl_setopt($ch, CURLOPT_URL, 'http://octopus.library.cmu.edu/cgi-bin/test-cgi');
 curl_setopt($ch, CURLOPT_URL, OAUTH_RESOURCE_URL);
 curl_setopt($ch, CURLOPT_HTTPHEADER, 
                  array('Content-Type: application/vdn.orcid+xml',
			'Accept: application/xml',
                        'Authorization: Bearer '.$ACCESS_TOKEN           ));
 curl_setopt($ch, CURLOPT_HEADER, 0);
 curl_setopt($ch, CURLOPT_POST, 1);
 curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
 curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
 curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
 // to get header
 curl_setopt($ch, CURLOPT_VERBOSE, 1);
 curl_setopt($ch, CURLOPT_HEADER, 1);

 $ch_result = curl_exec($ch);

 // Print CURL result.
 if(DEBUG){
  echo "[ORCID_RESULT=]<BR>";
  print_r(htmlentities($ch_result));
  echo "[ORCID_RESULT_END]<BR>";
  echo "[ORCID_RESULT_INFO=]";
  print_r(curl_getinfo($ch));
  echo "[ORCID_RESULT_INFO_END]";
 } // DEBUG

 $ORCID_CODE=curl_getinfo($ch, CURLINFO_HTTP_CODE);
 $ORCID='not_yet_defined';
 if(DEBUG){  echo "<BR><B>ORCID_CODE=".$ORCID_CODE ."<B>"; }

 if($ORCID_CODE != "201"){
  $status=1;
  $err_1 = strpos($ch_result,'<error-desc>')+12;
  $err_2 = strpos($ch_result,'</error-desc>');
  if($err_1 > 0) $ERROR=substr($ch_result, $err_1, ($err_2-$err_1));
  else $ERROR="some error occurred.";
  log_error("2.1 ORCID_CODE not 201 (=".$ORCID_CODE.") error=".$ERROR);
 } else {  // code=201   everything worked 
  // get our ORCID id out of
  // Location: https://api.sandbox.orcid.org/0000-0001-5944-1845/orcid-profile
  $olocation = strpos($ch_result,'Location: ');
  $ostart = strpos($ch_result, 'orcid.org/', $olocation) + 10;
  $oend   = strpos($ch_result, '/orcid-profile', $olocation);
  $ORCID = substr($ch_result, $ostart, ($oend-$ostart));

 }

 if(empty($ORCID))log_error("2.2 ORCID value not found in result [".print_r($ch_result,TRUE)."]");
 curl_close($ch);
} // if status == 0

// ** 3. Set webhook so we know when user claims record 
//         Note: webhook page needs to be outside of Shib so orcid's servers can reach it!
$webhook_url=CLAIM_WEBHOOK.$ORCID;
$final_webhook = OAUTH_ROOT_URL . $ORCID . '/webhook/' . urlencode($webhook_url);
if(DEBUG){
  echo "<BR>";
  echo "ORCID=".$ORCID."<br>";
  echo "webhook_url=". $webhook_url ."<br>";
  echo "final_webhook=". $final_webhook ."<br>";
}

if($status==0){  // if we created the ORCID ID ok, make a webhook for it
  // note: ignore any errors with the webhook setting since it doesn't affect user
// -------------- get authorization for webhook
 // fetch the access token
 $wha_curl = curl_init();
 curl_setopt_array($wha_curl, array(
       CURLOPT_URL => OAUTH_TOKEN_URL,
       CURLOPT_RETURNTRANSFER => true,
       CURLOPT_HTTPHEADER => array('Accept: application/json'),
       CURLOPT_POST => true,
       CURLOPT_POSTFIELDS => http_build_query(array(
				    'grant_type' => 'client_credentials',
				    'scope' => '/webhook',
				    'client_id' => OAUTH_CLIENT_ID,
				    'client_secret' => OAUTH_CLIENT_SECRET
				    ))
       ));
 $wha_result = curl_exec($wha_curl);
 $wha_response = json_decode($wha_result, true);
 $WHA_ACCESS_TOKEN = $wha_response['access_token'];
 if (DEBUG){
  echo "<BR>* 3a. webhook authorization response=";
  print_r($wha_response);  echo "** WHA_ACCESS_TOKEN=" . $WHA_ACCESS_TOKEN . "=**<BR>";
 }
 curl_close($wha_curl);
 if(empty($WHA_ACCESS_TOKEN))log_error("3.1 webhook access token not found in result [".print_r($wha_result,TRUE)."]");


  // -------------- add webhook
 $wh_curl = curl_init();
 curl_setopt_array($wh_curl, array(
       CURLOPT_URL => $final_webhook, // OAUTH_TOKEN_URL,
       CURLOPT_RETURNTRANSFER => true,
       CURLOPT_HTTPHEADER => array('Accept: application/json',
				   'Authorization: Bearer '.$WHA_ACCESS_TOKEN,
				   'Content-Length: 0'
				   ),
       CURLOPT_CUSTOMREQUEST => "PUT"
				   )
		  );
 
 $wh_result = curl_exec($wh_curl);
 $wh_info = curl_getinfo($wh_curl);
 $wh_response = json_decode($wh_result, true);
 curl_close($wh_curl);
 // Print CURL result.
 if(DEBUG){
  echo "* 3b.  WEBHOOK ** -------------------";
  echo "[WH_RESULT=]<BR>"; print_r(htmlentities($wh_result)); echo "[WH_RESULT_END]<BR>";
  echo "[WH_RESULT_INFO=]"; print_r(curl_getinfo($wh_curl)); echo "[WH_RESULT_INFO_END]";
 } // DEBUG
 // webhook set?    
 if(($wh_info['http_code'] != '201')&&($wh_info['http_code'] != '204')){
  $WARNINGS .= '[webhook not set]';
  log_error("3.2 webhook not set. result=[".print_r($wh_result,TRUE)."]");
 }


}

// ** 4. log what happened
// date/time user orcid statuscode warnings
log_user();

if($status==0) add_to_IDM();   // looks good. add to queue to update campus IDM system

?>
</pre>

<!-- header -->
<table width="700px;"><tr>
<td><h2><A HREF='../'>ORCID-webapp</A><BR> ORCID ID Created</h2></td>
<td style="width:200px;"><img src="../images/Corp-comp-OP-logo16-0.jpg" width="80%"></td>
<td style="width:200px;"></td>
</tr>
</table>

<hr style="border-width: 3px;color: #939598;height: 5px; width:700px; float:left;">
<br>
<P style="width:700px;text-align:right;">
  <?php echo $CMU_EPPN;?> <BR>
</P>


<p>


<?php  if ($status != 0) {     // error ?>
 <div style="border-width:3px;border-style:solid;border-color:red;width:600px;padding:20px;">
 <h2>Error: </h2>
 '<?php echo $ERROR;?>'     <BR>
 <BR>
 <BR>
 This error has been logged and we will contact you if necessary.<BR>
 <BR>
 Thank you!
 </div>
<?php  }  // status == 1?>

<?php  if ($status==0) {     // OK  ?>
 <div style="border-width:3px;border-style:solid;border-color:#a6ce39;;width:550px;padding:20px;">
 <h2>Congratulations!</h2>
 <h3>  Your ORCID ID has been created and associated with your ID 
        in the campus identity management system.</h3>

ORCID will send you email with instructions for claiming your ORCID account.<BR>
To reap maximum benefit from ORCID, please claim your ORCID account, import your citations, and complete your profile.  Thank you.
<BR>
<BR>
 For answers to frequently asked questions, see 
<a href="http://orcid.org/faq-page" target="_blank">
ORCID FAQ</a>.</p>


<BR><BR>

      Your ORCID ID is <B><?php echo $ORCID;?>
 </B><BR>

<div style="display:none">
 Your ORCID record can be viewed at 
  <A href="http://orcid.org/<?php echo $ORCID;?>"> 
    http://orcid.org/<?php echo $ORCID;?></A> <BR>
</div>
<?php  }// status == 0  ?>




<?php  
if(!ORCID_PRODUCTION){   // test stuff.  
  echo '<div id="test-warn-div" class="alert" style="background-color:#f5c120">';
  echo '<strong>Warning: Test mode.  Using sandbox.orcid.org .</strong>';
  echo '</div>';
 }
?>
</body>
</html>