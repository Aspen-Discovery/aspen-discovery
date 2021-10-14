## Administration Troubleshooting
**Problem:**  
The library uses Koha and the hours for one or more days of a location keeps reverting to closed even after marking it as open. 

**Solution:**  
The library has a recurring holiday in Koha that indicates that it should be closed on that day. Deleting the holiday will allow you to mark the library as opened in Aspen.    

**Problem:**
One of my facets is showing duplicate values within the facet.  For example, I'm seeing 2 **Kids** values within the Audience facet. 

**Solution:**
This typically happens if the library is using translations to rename a facet value.  In this case, the actual values can be different within Solr, but the same when displayed to the user.
I.e. there can be a Juvenile and Kids value in Solr, but when displayed the values are both Kids. To fix the problem, you will need to ensure that the values in Solr are consistent. 
So all translation maps will need to use the Kids value and values in code will need to match the value from the translation maps.  