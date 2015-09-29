<?php
  /* ORCID-webapp                               
   *   @copyright  2014-2015 Carnegie Mellon University   
   *                                                    
  */
  // callback from the link function
  //  gets the orcid id and, since we're shib logged in, campus id
  //  and sends them to the external program to add to the IDM system
  //    Note: this URL MUST be registered with your app at orcid
  //     I registered it as 'https://orcid.library.cmu.edu/app/callback'
  //     then added an Alias to our httpd.conf file to point to this file:
  //               Alias /app/callback "/var/www/html/app/callback.php"

include '_config.php';
/* end editable */


$status=0; // OK so far

// redirect the user to approve the application
if (!$_GET['code']) {

    $state = bin2hex(openssl_random_pseudo_bytes(16));
    setcookie('oauth_state', $state, time() + 3600, null, null, false, true);
 
    $url = OAUTH_AUTHORIZATION_URL . '?' . http_build_query(array(
							  'response_type' => 'code',
							  'client_id' => OAUTH_CLIENT_ID,
							  'redirect_uri' => OAUTH_REDIRECT_URI,
							  'scope' => '/authenticate',
							  'state' => $state,
							  ));
 
    header('Location: ' . $url);
    exit();
} // ! code
 
// code is returned, check the state
//if (!$_GET['state'] || $_GET['state'] !== $_COOKIE['oauth_state']) {
//  exit('Invalid state');
//}
 
// fetch the access token
$curl = curl_init();
 
curl_setopt_array($curl, array(
	       CURLOPT_URL => OAUTH_TOKEN_URL,
	       CURLOPT_RETURNTRANSFER => true,
	       CURLOPT_HTTPHEADER => array('Accept: application/json'),
	       CURLOPT_POST => true,
	       CURLOPT_POSTFIELDS => http_build_query(array(
					    'code' => $_GET['code'],
					    'grant_type' => 'authorization_code',
					    'client_id' => OAUTH_CLIENT_ID,
					    'client_secret' => OAUTH_CLIENT_SECRET,
					    'redirect_uri' => OAUTH_REDIRECT_URI,
					    ))
	       ));
 
$result = curl_exec($curl);
//$info = curl_getinfo($curl);
$response = json_decode($result, true);


// find out what happened 
$ORCID = $response['orcid'];
$ERROR = $response['error-desc']['value'];
$CMU_EPPN = $_SERVER['REMOTE_USER'];

$status=0; // ok
if(isset($ERROR)) {
  $status=1; // fail
  log_error('ERROR='.$ERROR);
}

if($status==0) add_to_IDM();   // looks good. add to queue to update campus IDM system
log_linked();   // log what happened
?>

 



<!DOCTYPE html>
<html>
 <head>
  <title>ORCID-webapp: ORCID ID Provided</title>
 </head>
 <body>
<!-- header -->
<table width="700px;"><tr>
<td><h2><A HREF='../'>ORCID-webapp</A><BR> ORCID ID Provided</h2></td>
<td style="width:200px;"><img src="/images/Corp-comp-OP-logo16-0.jpg" width="80%"></td>
<td style="width:200px;"></td>
</tr>
</table>

<hr style="border-width: 3px;color: #939598;height: 5px; width:700px; float:left;">
<br>
<P style="width:700px;text-align:right;">
   <?php echo $CMU_EPPN;?> <BR>
</P>


<p>


<?php  if ($status==1) {     // error ?>
 <div style="border-width:3px;border-style:solid;border-color:red;width:600px;padding:20px;">
 <h2>Error: </h2>

 '<?php echo $ERROR;?>'     <BR>
 This error has been logged and we will contact you if necessary.<BR>
 <BR>
 Thank you!
<BR>
 </div>
<?php  }  // status == 1?>


<?php  if ($status==0) {     // OK  ?>
<div style="border-width:3px;border-style:solid;border-color:#a6ce39;;width:550px;padding:20px;">

<h2>Congratulations!</h2>
<h3>  Your ORCID ID is now associated with your ID in the campus identity management system.</h3>


Thank you.<BR><BR>

Your ORCID ID is <B><?php echo $ORCID;?>
</B><BR>
Your ORCID record can be viewed at 
  <A href="http://orcid.org/<?php echo $ORCID;?>"> 
   http://orcid.org/<?php echo $ORCID;?></A>
    
<BR>
      <?php  }// status == 0  ?>

<BR>
<BR>
<BR>
</div>

<div style="display:none;"> 
<pre>
DEBUG: result= <?php print_r($response); ?>
</pre>
</div>
 </body>
</html>