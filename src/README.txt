ORCID-webapp software web app
_______________________________________________________________________________________

** overview
 Web application to create or link ORCID accounts with campus identity management system.

 -PHP and HTML files
 -PHP curl library needs to be installed -- used to send/get info from ORCID
 -Shibboleth protects app pages
 -uses Shib credentials to lookup user in LDAP  
 -check user [we don't register undergrad students for FERPA reasons. ]
             [also, check if user already has an ORCID in campus LDAP]
 - create XML file based on user info from LDAP.   
   this is currently VERY minimal - you can add fields easily.
  (allow user to confirm/choose info  e.g. multiple names)
   - XML includes callback URL to tell us when user claims record
 - PHP curl module to POST XML file to create new user
 - log everything

 -after creation, get ORCID ID# from reply & add to campus identity management (IDM) system for user
    - external perl program w/ stomp module to create message & add to idm Apache ActiveMQ queue 
     (this program is not included here.  You probably do things differently anyway)

 - for testing, mailinator.com addresses generated for sandbox - 
    orcid specifies this as only supported test mail service. 
    When testing, we use app-generated e-mail address based on date/time
    so ORCID sandbox doesn't complain that the user already exists.


** How it all works

The main index.html file lets you choose to create an ORCID or link an existing one.
These go to app/create.php & app/link.php
app/ is Shibboleth-protected, so the pages there will have access to your
Shibboleth credentials.  These are used to lookup your info in LDAP.

ORCID-webapp structure:
  index.html           (homepage & choose create or link) 
  claimed.php          (called by orcid server when user uses orcid first time -out of Shibboleth)
  app/
      index.html       (redirect to top)
      _config.php      (parameters & log functions)
      callback.php     (page to go to after link success)
      create.php       (user confirm data to create)
      create_done.php  (use create data, POST to ORCID, add info to IDM & report results) 
      link.php         (link user with their existing orcid, goto callback.php after)

-create a new ORCID
The create.php page displays the info that will be submitted to ORCID.
When you click 'submit to orcid' the info goes to our app/create_done.php
file, which creates an XML file with your info & uses PHP's CURL functions
to submit the data to orcid.   Results are logged & displayed.
create_done.php contains a function 'add_to_IDM', which calls an external program
with the campus ID and ORCID ID as parameters -- this is where you add your program
to add the new information to your campus system.

-logging that the record is claimed
 The new record is also given a 'webhook' URL that is called when the user claims
the record -- this is just a URL on our server that the ORCID server calls. 
It is a php program which simply logs the fact that the user claimed the record -- NOTE:
this webhook page needs to exist _OUTSIDE_ the Shibboleth-protected app since it is
being called by ORCID's server at the time of the claim - possibly days from now.
This is setup in _config.php, so you'll want to change the line
 define('WEBAPP_LOCATION', 'orcid.university.edu'); 
to the server (and path if necessary) of your app.
That will update the claim URL since it is defined as 
   define('CLAIM_WEBHOOK', 'http://'.WEBAPP_LOCATION.'/claimed.php?orcid=');


-link
  app/link.php uses your Shibboleth credentials to get your LDAP info.
 linking is a bit trickier, since it requires you to then login at ORCID
 to get your ORCID info.  This is done by specifying a callback URL
 in the ORCID login form so control  comes back to our app from ORCID after login.
 Note: This URL *needs to be registered* with your app credentials at ORCID,
 because they won't redirect to just any old URL. 
  [ Redirect URI 1 at https://orcid.org/content/register-client-application-sandbox  ]
 In our case it goes to app/callback.php, which just logs the ORCID ID & Shib credentials
 and adds them to the campus identity management system using an external program.
 
-configuration
 In an attempt to clean things up a bit, I tried moving most of the
configuration variables to app/_config.php -- most importantly, your ORCID credentials go there.
This simplifies things since the whole system now uses the same parameters & the whole system is
either using the sandbox or not.  (prevents you from creating fake 'real' ORCID IDs.)
So once you have your sandbox credentials in there you can start using the app.

** START BY 
1) getting your sandbox credentials
  At https://orcid.org/content/register-client-application-sandbox
  Everything is pretty straightforward except the "OAuth2 redirect_uris or callback URLs"
 For this app, there is only one needed, the callback after linking
 This will be 'https://{webapp-location}/app/callback.php'
 (you can hide the .php by creating an alias in your webserver if you like)


2) EDITING app/_config.php ***
ORCID_PRODUCTION = false means the system will use the ORCID sandbox, so you
don't have to worry about making a mess of things.  the default.
DEBUG = true will print a bunch of debugging info before the page renders.

There is still an embarassingly large number of hard-wired URLs in the PHP code,
but you'll need to edit those pages/links anyway for your app.

Shibboleth is now optional for testing, and it will use 'fake_name@university.edu' if
it is being run outside of a Shibboleth environment.
The LDAP is also be optional, and fake values will be used for that, too.
Note: Shib & LDAP are optional _ONLY_ if the system is NOT in ORCID_PRODUCTION mode.

So you should be able to just drop these files  on a webserver, add your sandbox credentials,
set the WEBAPP_LOCATION & start playing with it.

---------------------


** OUR CONFIGURATION **
We're running on Red Hat 6.5   using PHP 5.3.3 
A few extra PHP modules were added in addition to the standard PHP distribution:
curl, ldap, openssl & xml.  and their dependencies.
The complete list of modules in our PHP setup (php -m) is: 
[PHP Modules]
 bz2 calendar Core ctype curl date dom ereg exif fileinfo filter ftp
 gd gettext gmp hash iconv json ldap libxml openssl pcntl pcre PDO
 pdo_sqlite Phar readline Reflection session shmop SimpleXML sockets
 SPL sqlite3 standard tokenizer wddx xml xmlreader xmlwriter xsl zip zlib



    
----------------------------------------------------------------------------------------
