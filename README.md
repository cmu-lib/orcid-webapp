# orcid-webapp
web application for creating and linking ORCID IDs

Web application to create and/or link ORCID accounts with a campus identity management system. 

 ORCID (Open Researcher and Contributor Identifier) is a non-profit organization dedicated to solving the name ambiguity problem in scholarly research by assigning a unique identifier to each author.
These IDs are used to uniquely identify researchers in a variety of systems including grant management systems, publication databases and repositories.   It is a valuable piece of information used to track the activities of a researcher and is increasingly being integrated into scholarly systems.

 
ORCID provides a web-based application programming interface, though not very many code samples to get a system up and running quickly.   The web app we created is fairly simple and would provide a good starting point for other universities who are interested in creating a similar system.
  
The web application consists of ~1100 lines of PHP and HTML code.  
The PHP curl library is used to interact with ORCID using the ORCID API.
The web app uses the campus authentication system (e.g. Shibboleth) to get a user's identity, and then uses the campus LDAP system to get additional information such as names & affiliation.   The app also uses LDAP to see if the user already has an ORCID ID.
If the user is permitted to create an ORCID ID, a minimal ORCID XML file based on user info from LDAP is created and submitted to ORCID. 
After  a new ORCID ID is successfully created, the new ORCID ID is linked to the user in the campus identity management system using an external program that is not included here.   
The app also contains a callback page to log when a user claims his ORCID profile  (i.e. initially logs into the orcid website).  There is extensive logging to track the activities of users.
 
