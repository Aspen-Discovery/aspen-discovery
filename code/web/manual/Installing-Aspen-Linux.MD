##Installing Aspen on Linux or Mac OS X
Aspen can run in production in a variety of environments.  We recommend CentOS, RedHat, and Debian.  Other environments may need code tweaks to run properly. 
These instructions are designed for CentOS 7.  Other environments may require some changes to commands to run. 

### Installation Steps
1. Update the server`sudo yum check-update`
1. Install git `sudo yum -y install git` 
1. Install the Aspen Discovery code (if you will be doing code development, you will want to create your own fork of the [main git repository](https://github.com/mdnoble73/aspen-discovery))   
   `cd /usr/local`  
   `sudo git clone https://github.com/mdnoble73/aspen-discovery.git`  
   Checkout the current branch:  
   `cd /usr/local/aspen-discovery/; sudo git pull origin 21.xx.yy`  
1. Run the installer to setup the system files  
   `cd /usr/local/aspen-discovery/install/`  
   `sudo ./installer.sh`  
   At the end of the installer, you will secure mariadb using a new password of your choosing  
   When setting timezone use one of:  
     America/New_York  
     America/Chicago  
     America/Denver  
     America/Los_Angeles  
1. Restart the server to have SELinux changes take effect  
   `sudo reboot now`
1. The next step will be easier if you have connection information to your ILS. For Koha you will need ODBC and REST API connection information. 
1. Setup the site  
   `cd /usr/local/aspen-discovery/install/`  
   `sudo php createSite.php`
1. Run database updates by logging in with the aspen_admin user you created while setting up the site and then going to:
   System Admin -> DB Maintenance -> run

Your site is now setup, but you will need to do some configuration of the site.
1. Add the server external IP to the list of IPs as 'Allow API Access'
1. Setup Initial Library and Location information
1. Setup Account Profile (this is setup during the install process if you are using Koha)
1. Setup Indexing Profile
1. Setup Translation Maps
1. Setup recaptcha
1. Run a new index
1. Setup cron (not needed for development installs) 
1. Setup SSL (not needed for development installs)
