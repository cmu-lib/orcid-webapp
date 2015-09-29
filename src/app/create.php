<?php
  /* ORCID-webapp                               
   *   @copyright  2014-2015 Carnegie Mellon University   
   *                                                    
  */
include '_config.php';
if(DEBUG) echo "DEBUG=true<br>";


// get user from Shibboleth login info
$CMU_EPPN = $_SERVER['REMOTE_USER'];
$user_email=$CMU_EPPN;    //email them at their e-mail address

// No REMOTE_USER, so page not Shib-protected.  fake it for testing
if(!ORCID_PRODUCTION) { if(strlen($CMU_EPPN)<1) $CMU_EPPN = 'fake_user@university.edu'; }
if(DEBUG)  echo "CMU_EPPN='" . $CMU_EPPN . "'<p>";


if(!ORCID_PRODUCTION){   // test stuff.  set email to mailinator address & override user
  $user_email='cmuorcidtest_'.date("ymdGi").'@mailinator.com';   //override for testing
}


// pull out uid from full e-mail address
$arr = explode("@", $CMU_EPPN, 2);
$CMU_UID = $arr[0];

   // status of the user
   // 0:ok, 1:FERPA student, 2:Orcid already in LDAP
$status = 0;  // ok so far...

// --------------------------------------- 
// LOG that user logged in 
log_login();  //log the fact that user logged in


// ************ LDAP LOOKUP ******************** 
$ds=ldap_connect(LDAP_SERVER);  // must be a valid LDAP server!
//echo "connect result is " . $ds . "<br />";
if ($ds) { 
    $sr=ldap_search($ds, "dc=cmu,dc=edu", "uid=$CMU_UID");  
    if(DEBUG){
     // echo "Search result is " . $sr . "<br />";
     echo "Number of entries returned is " . ldap_count_entries($ds, $sr) . "<br />";
     echo "Getting entries ...<p>";
    }
    $info = ldap_get_entries($ds, $sr);
    if(DEBUG){
     echo "Data for " . $info["count"] . " items returned:<p>";
     echo "<pre>";
     print_r($info);
     echo "</pre>";
     echo "Closing connection<br>";
    }
    ldap_close($ds);
}

if (!ORCID_PRODUCTION  ) {  // no LDAP info? no problem.  make some up for testing
  if( $info["count"] == 0){
    $info["count"] = 1;
    $info[0]["displayname"]="Fake User Since No LDAP Results";
    $info[0]['givenname'][count] = 1; 
    $info[0]['givenname'][0] = 'Fake'; 
    $info[0]['sn'][count] = 1; 
    $info[0]['sn'][0] = 'User'; 
    if(DEBUG){
     echo "No LDAP info, so we made some up for testing...<br>";
     echo "<pre>";     print_r($info);     echo "</pre>";
    }
  }
}

// ****************** LDAP LOOKUP END ****************

//*** check data for non-standard users
// 1: if FERPA student
if (   !array_key_exists('displayname',$info[0]) ){
  // && $info[0]['edupersonprimaryaffiliation'][0]=='Student') {
 $status=1; 
}
// 2: if ORCID already in LDAP
if (   array_key_exists('edupersonorcid',$info[0]) ){
 $status=2; 
 $existing_orcid=$info[0]['edupersonorcid'][0];
}


// LOG status 
log_status();  // 



?>
<!DOCTYPE html>
<html>
 <head>
  <title>ORCID-webapp: Create My Orcid</title>
 </head>
 <!-- header -->

 <body style='padding-left:20px;'>

<?php  
if(!ORCID_PRODUCTION){   // test stuff.  
  echo '<div id="test-warn-div" class="alert" style="background-color:#f5c120">';
  echo '<strong>Warning: Test mode.  Using sandbox.orcid.org .</strong>';
  echo '</div>';
 }
?>


<table width="700px;"><tr>
<td><h2><A HREF='../'>ORCID-webapp</A> <BR> Create My ORCID</h2></td>
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

<!-- ** PUT LDAP DEBUGGING INFO IN THE HTML SOURCE BUT DON'T DISPLAY IT -->
<div style="display:none;"> 
<table><ttitle>LDAP info<ttitle>
<tbody style="background: none repeat scroll 0% 0% rgb(221, 221, 221);">
<tr><td>CMU_EPPN:</td><td><?php echo $CMU_EPPN ?></td></tr>
<tr><td>orcid_id:</td><td><A HREF="<?php echo $info[0]['edupersonorcid'][0] ?>">
                                   <?php echo $info[0]['edupersonorcid'][0] ?> </A></td></tr>
<tr><td>displayname:</td><td><?php echo $info[0]['displayname'][0] ?></td></tr>
<tr><td>affiliation:</td><td><?php echo $info[0]['edupersonprimaryaffiliation'][0] ?></td></tr>

<tr><td>givenname.#:</td><td><?php echo $info[0]['givenname']['count'] ?></td></tr>
<tr><td>givenname.0:</td><td><?php echo $info[0]['givenname'][0] ?></td></tr>
<tr><td>givenname.1:</td><td><?php echo $info[0]['givenname'][1] ?></td></tr>
<tr><td>givenname.2:</td><td><?php echo $info[0]['givenname'][2] ?></td></tr>

<tr><td>surname.#:</td><td><?php echo $info[0]['sn']['count'] ?></td></tr>
<tr><td>surname.0:</td><td><?php echo $info[0]['sn'][0] ?></td></tr>
<tr><td>surname.1:</td><td><?php echo $info[0]['sn'][1] ?></td></tr>
<tr><td>surname.2:</td><td><?php echo $info[0]['sn'][2] ?></td></tr>

<tr><td>ferpa:</td><td><?php echo array_key_exists('displayname',$info[0])?'FALSE':'TRUE' ?></td></tr>

<tr><td>status:</td><td><?php echo $status ?></td></tr>

</tbody></table>
<hr>
</div>






<?php  if ($status==1) {     // FERPA  student error ?>
<div style="border-width:3px;border-style:solid;border-color:red;width:600px;padding:20px;">
<h2>You seem to be a student covered under FERPA</h2>

We are not acquiring ORCID iDs for FERPA protected students at this time.<BR>  

</div>
<?php  } ?>

<?php  if ($status==2) {     // already have an ORDID iD ?>
<div style="border-width:3px;border-style:solid;border-color:#a6ce39;width:600px;padding:20px;">
<h2>You already have an ORCID iD associated with your account</h2>
You already have an ORCID iD registered with your account: <BR>
<A HREF="<?php echo $existing_orcid ?>" TARGET="_blank"><?php echo $existing_orcid ?></A>
<BR>
Congratulations, you do not need to do anything else.
</div>
<?php  } ?>


<?php  if ($status==0) {     // NORMAL USER ?>
<div style="padding-top:20px; padding-left:30px;">
The following information will be sent to ORCID to create your ORCID iD:<BR>

<form action="create_done.php" method="post">

<input type="hidden" name="email" value="<?php echo $user_email ?>">


<table style="border-style:solid; border-weight:1; border-color:#a6ce39;">
      <ttitle>&nbsp;<ttitle>
<tbody style="background: none repeat scroll 0% 0% #f9fafb;">
<tr><td>e-mail:</td><td><?php echo $user_email ?></td></tr>


<tr><td>given name:</td><td>
 <?php if ($info[0]['givenname']['count']==1) { 
     echo $info[0]['givenname'][0]; 
     echo "<input type='hidden' name='given_names' value='".$info[0]['givenname'][0]."'>";

   } else {     //select a name
    for ($i = 0; $i < $info[0]['givenname']['count']; $i++) {
     //echo $info[0]['givenname'][$i]."<br>";
     echo "<input type='radio' name='given_names' value='".$info[0]['givenname'][$i]."'";
     if($i==0) echo " checked";
     echo ">".$info[0]['givenname'][$i]." <br>";
    }//for
   } // else
?>
</td></tr>

<tr><td>family name:</td><td>
 <?php if ($info[0]['sn']['count']==1) { 
     echo $info[0]['sn'][0]; 
     echo "<input type='hidden' name='family_names' value='".$info[0]['sn'][0]."'>";

   } else {     //select a name
    for ($i = 0; $i < $info[0]['sn']['count']; $i++) {
     //echo $info[0]['sn'][$i]."<br>";
     echo "<input type='radio' name='family_names' value='".$info[0]['sn'][$i]."'";
     if($i==0) echo " checked";
     echo ">".$info[0]['sn'][$i]." <br>";
    }//for
   } // else
?>
</td></tr>


</tbody></table>

<input type="submit" name="" value="Submit to ORCID" style="margin-top:5px; margin-left:40px;">
</form>

<p>
 Submitting this form will create your ORCID ID and account.<BR>
 Your email address will not be displayed or discoverable on the ORCID website.<BR>
 ORCID will send you email with instructions for claiming your ORCID account.

<BR>
<BR>

</div>
<?php  


  if(!ORCID_PRODUCTION){   // test stuff.  
   $mailbox = substr($user_email,0,strpos($user_email,'@'));
  echo '<div id="test-warn-div" class="alert" style="background-color:#f5c120">';
  echo 'TEST MODE: View your confirmation e-mail here: <A href="http://mailinator.com/inbox.jsp?to='.$mailbox.'" target="_blank">'.$user_email.' </A>';
   echo '<BR><I>(click this before you Submit your info above)</I>';
  echo '</div>';
  } 


} // status = 0
?>

<!--
info from LDAP: <font size='-2'>
<pre>
 <?php print_r($info); ?>
</pre></font><hr>
-->


<?php  

?>

 </body>
</html>