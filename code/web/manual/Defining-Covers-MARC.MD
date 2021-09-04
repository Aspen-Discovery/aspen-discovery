## Defining Covers in MARC 
If you want to use the same cover for a large number of records in the ILS, it can be easier to set the cover for all of the records using an 856 link within the MARC record. 

To use this method, you will first need to make sure that the cover is available to Aspen. This can be done by either uploading it to the Aspen server using Web Builder functionality or by adding it to one of your own content servers. 

After the cover has been uploaded, you will add an 856 field with the following subfields: 
1. Subfield 2 = **Aspen**
2. Subfield 3 = **CoverImage**
3. Subfield u = **Full url to the image**

Note: 
For the cover to show properly, you may need to click the Reload Cover button within Staff View of the record. 