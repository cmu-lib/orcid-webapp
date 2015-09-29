<?php
  /* ORCID-webapp  Web App                               
   *   @copyright  2014-2015 Carnegie Mellon University   
   *                                                    
  */
include '_config.php';

$CMU_EPPN = $_SERVER['REMOTE_USER'];
$user_email=$CMU_EPPN;    //email them at their authenticated e-mail address

// No REMOTE_USER, so page not Shib-protected.  fake it for now
if(!ORCID_PRODUCTION) { if(strlen($CMU_EPPN)<1) $CMU_EPPN = 'fake_name@university.edu'; }

if(DEBUG)  echo "CMU_EPPN='" . $CMU_EPPN . "'<p>";

// pull out uid from full e-mail address
$arr = explode("@", $CMU_EPPN, 2);
$CMU_UID = $arr[0];

   // status of the user
   // 0:ok, 1:FERPA student, 2:Orcid already in LDAP
$status = 0;  // ok so far...

// LOG that user logged in  -- FILE_APPEND and LOCK_EX to make thread-safe
log_login();


// ************ LDAP LOOKUP ******************** 
$ds=ldap_connect(LDAP_SERVER);  // must be a valid LDAP server!
// echo "connect result is " . $ds . "<br />";
if ($ds) { 
    $sr=ldap_search($ds, "dc=cmu,dc=edu", "uid=$CMU_UID");  
    if(DEBUG){
     echo "Search result is " . $sr . "<br />";
     echo "Number of entries returned is " . ldap_count_entries($ds, $sr) . "<br />";
     echo "Getting entries ...<p>";
    }
    $info = ldap_get_entries($ds, $sr);
    if(DEBUG){
     echo "Data for " . $info["count"] . " items returned:<p>";
     echo "<pre>";     print_r($info);     echo "</pre>";
     echo "Closing connection";
    }
    ldap_close($ds);
}

if (!ORCID_PRODUCTION  ) {  // no LDAP info? no problem.  make some up for testing
  if( $info["count"] == 0){
    $info["count"] = 1;
    $info[0]["displayname"]="Fake User";
    if(DEBUG){
     echo "making up LDAP info...";
     echo "<pre>";     print_r($info);     echo "</pre>";
    }
  }
}

// ****************** LDAP LOOKUP END ****************

//*** check data for non-standard users
// 1: if FERPA student
if (   !array_key_exists('displayname',$info[0]) ){   // display name hidden?
  // && $info[0]['edupersonprimaryaffiliation'][0]=='Student') {
 $status=1; 
}
// 2: if ORCID already in LDAP
if (   array_key_exists('edupersonorcid',$info[0]) ){
 $status=2; 
 $existing_orcid=$info[0]['edupersonorcid'][0];
}


// LOG status 
log_status();


?>
<!DOCTYPE html>
<html>
 <head>
  <title>ORCID-webapp : Provide Existing ORCID</title>
 </head>
 <!-- header -->
 <body style='padding:20px;'>

<?php  
if(!ORCID_PRODUCTION){   // test stuff.  
  echo '<div id="test-warn-div" class="alert" style="background-color:#f5c120">';
  echo '<strong>Warning: Test mode.  Using sandbox.orcid.org .</strong>';
  echo '</div>';
 }
?>

<table width="700px;"><tr>
<td><h2><A HREF='../'>ORCID-webapp</A><BR> Provide Existing ORCID</h2></td>
<td style="width:200px;"><img src="../images/Corp-comp-OP-logo16-0.jpg" width="80%"></td>
<td style="width:200px;"></td>
</tr>
</table>


<hr style="border-width: 3px;color: #939598;height: 5px; width:700px; float:left;">
<br>
<P style="width:700px;text-align:right;">
<?php echo $CMU_EPPN;?> <BR> 
</P>

<div class="test" style="background: none repeat scroll 0% 0% rgb(221, 221, 221);">
</div>


<?php  if ($status==1) {     // FERPA  student error ?>
<div style="border-width:3px;border-style:solid;border-color:red;width:600px;padding:20px;">
<h2>You seem to be a student covered under FERPA</h2>

We are not acquiring ORCID IDs for FERPA protected students at this time.<BR>  

</div>
<?php  } ?>

<?php  if ($status==2) {     // already have an ORDID ID ?>
<div style="border-width:3px;border-style:solid;border-color:#a6ce39;width:600px;padding:20px;">
<h2>You already have an ORCID ID associated with your account</h2>
You already have an ORCID ID registered with your account: <BR>
<A HREF="<?php echo $existing_orcid ?>" TARGET="_blank"><?php echo $existing_orcid ?></A>
<BR>
Congratulations, you do not need to do anything else.
</div>
<?php  } ?>


<?php  if ($status==0) {     // NORMAL USER ?>
<div style="padding-top:20px; padding-left:30px;">
Login to ORCID to link your ORCID ID:<BR>

<form action="<?php echo OAUTH_AUTHORIZATION_URL;?>#show_login" method="get">
<input type="hidden" name="client_id" value="<?php echo OAUTH_CLIENT_ID;?>">    
<input type="hidden" name="response_type" value="code">
<input type="hidden" name="scope" value="/orcid-profile/read-limited">
<input type="hidden" name="redirect_uri" value="<?php echo OAUTH_REDIRECT_URI;?>">    


<input type="submit" name="submit" value="Login to ORCID" style="margin-top:5px; margin-left:40px;">
</form>

<p style="width:500px;">
      To enable us to retrieve your ORCID ID and associate it with your ID in the campus identity management system, please login to ORCID as you normally would.

<BR>
<BR>

</div>



<?php  } ?>

<!--
info from LDAP: <font size='-2'>
<pre>
 <?php print_r($info); ?>
</pre></font><hr>
-->



<?php  
if(!ORCID_PRODUCTION){   // test stuff.  
  echo '<div id="test-warn-div" class="alert" style="background-color:#f5c120">';
  echo '<strong>Warning: Test mode.  Using sandbox.orcid.org .</strong>';
  echo '</div>';
 }
?> 

 </body>
</html>