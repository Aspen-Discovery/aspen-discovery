## System Reports

The System Reports section of Aspen Discovery includes Site Status, Usage Dashboard, Usage by IP Address, Nightly Index Log, Cron Log, Performance Report, Error Log, and PHP Information.

### System Reports Available

#### Site Status
The Site Status will give a detailed view of each aspect of Aspen and the level at which it is performing. This are also shows Solr reports.
#### Usage Dashboard
The Usage Dashboard shows general usage of Aspen, including page views and exceptions. You are able to toggle the view of these reports to show the information in a graph format as well.
- **Page Views By Bot**	We have a list of bots that we check against- Google bot, Bing bot, etc. that are out checking the web. We try to keep the list up to date but it is possible that we are missing some. If we are missing some, then these will be counted in page views. 	
- **Asynchronous Requests:**	Showing a pop up or when we load menu data. When we load how many things you have checked out or on hold. Not something libraries are likely to report on.	
- **Covers Requested:**	Total number of book covers that we're showing on search result pages. Includes covers shown in the item record like "More Like This" and "Series".	
- **Grouped Work Searches:**	Total number of searches done	
- **User List Searches:**	When someone goes in and searches from the "list" dropdown 	
- **Genealogy Searches**	When someone goes in and searches from the "genealogy" dropdown	
- **Open Archives Searches:**	When someone goes in and searches from the "history and archives" dropdown
- **Website Searches:**	When someone goes in and searches from the "website" dropdown	
- **Blocked Pages:**	Blocked pages from IP address configuration. Exp: if there was a bot causing an issue, you can block that IP address. 	High numbers in either- probably worth figuring out what's going on. Should be low. 
- **Blocked API Requests:**	If someone was trying to make calls to the API without approval. 	High numbers in either- probably worth figuring out what's going on. Should be low. 
- **Errors:**	Whenever there is an error that pops up in Aspen, those are logged.	Should be low. The Aspen team is monitoring for these. 
- **Web Resources:**	We can track clicks within Aspen resources	

#### Usage by IP Address
Shows usage by IP addresses to help determine if there is any usage that is slowing your site. All IP addresses that have accessed the site will show here along with the total requests and the latest request date.
#### Nightly Index Log
The nightly index updates all records in Aspen so as to show the most updated information. This will show you the nightly index log information.
#### Cron Log
The Cron Log will show a report of when crons have started, how long they lasted, and any associated notes. Crons will update user reading history and process periodic cleanup tasks.
#### Performance Report
The Performance Report will show how each of the pages of Aspen are loading and at what speed.
#### Error Log
Shows any error that may have recently occurred and at what date and time they occured.
#### PHP Information
Information on Aspen Discovery code.


### Video Tutorial

[![System Reports](/manual/images/System-Admin-System-Reports.jpg)](https://youtu.be/yfmmSB7J_s4)

[Watch on Youtube](https://youtu.be/yfmmSB7J_s4)
