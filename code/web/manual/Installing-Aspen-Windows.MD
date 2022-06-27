##Installing Aspen on Windows
Aspen can run for development on Windows. We do not recommend using Windows for production environments. Prior to starting these steps you should [setup your environment](/Admin/HelpManual?page=Development-Environment). 

For Windows, we also recommend using [GitHub Desktop](https://desktop.github.com/) to make working with git easier.  
[Cygwin](https://www.cygwin.com/) will add common Linux commands to your Windows shell and is recommended for Aspen development.   

### Installation Steps
1. Make a fork of the main Aspen Discovery Code from [https://github.com/mdnoble73/aspen-discovery](https://github.com/mdnoble73/aspen-discovery).
1. Install the Aspen Discovery code by cloning the repository to `c:\web\`.   
1. The next step will be easier if you have connection information to your ILS. For Koha you will need ODBC and REST API connection information. 
1. Setup the site  
   `cd c:\web\aspen-discovery\install\`  
   `php createSite.php`
1. Run database updates by logging in with the aspen_admin user you created while setting up the site and then going to:
   System Admin -> DB Maintenance -> run

Your site is now setup, but you will need to do some configuration of the site.
1. Ensure all ports other than 80 and 443 (and 25 if needed for SSH access) are firewalled so they cannot be accessed by the world. 
1. Add the hostname you want to use for aspen to c:\Windows\System32\drivers\etc\hosts
1. Add the server external IP to the list of IPs as 'Allow API Access' (not needed for development installs)
1. Setup Initial Library and Location information
1. Setup Account Profile (this is setup during the install process if you are using Koha)
1. Setup Indexing Profile
1. Setup Translation Maps
1. Setup recaptcha
1. Run a new index
1. Setup cron (not needed for development installs) 
1. Setup SSL (not needed for development installs)
