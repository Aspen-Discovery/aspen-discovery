Aspen Discovery Layer originally based on VuFind with synchronization to VuFind source up to version 1.3
Additional features include
- Record Grouping/FRBR
- OverDrive support
- Browse functionality
- Novelist integration
- Reading Recommendations
- Consortial Support
- Administration Panel
- etc

We develop based off branches, the latest branch deployed in production can be seen in the the footer at 
http://aspen.demo.

Directory structure: 
conf       - contains configuration information for each server at each library.  
             Library specific customizations should not be checked in to Git. 
             Default subfolder conains configuration showing all options that can be copied for 
             setup of new libraries. Configuration files in the base folder can be overridden by 
             adding a copy to the local library configuration folder. 
covers     - contains cached covers for each library.  Data should not be checked in to git. 
             each library specific folder should have large, medium, small, and original folders within it. 
econtent   - contains eContent files that are served wthin VuFind for each library. 
           - Data should not be checked into git.
           - each library should have subfolders called: 
             - library - which will store the full epubs, pdfs, mp3, ext for display. 
             - fulltext - stores full text versions extracted from epubs or pdfs for loading into the index
httpd-conf - configuration files for loading the sites into apache for each server at each library.  
           - Should  not be checked into public version control. 
logs       - contains debug log files for each library. Will not be checked into version control
           - should not be checked in to git
marc       - contains marc files for each library base on the daily export.  
           - Should not be checked into git. 
solr-data  - contains solr index data for each library.  Will not be checked into version control. 
           - default contains files to help setup a new instance.  Should be copied to new instance directory
startup    - contains startup scripts to start aspen discovery customized by library domain.
code       - contains all aspen discovery code (website, cron, and load tester).  Checked in to version control.
  
