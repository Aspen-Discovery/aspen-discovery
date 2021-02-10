## Search Relevancy

[![Search Relevancy Video](/manual/images/Aspen-Discovery-Relevancy.png)](https://www.youtube.com/watch?v=bUpG7U_ciTA&list=PLV_OXyJ1D3Bjr49J9FQ3M0uNhiNv4E04f&index=4&t=0s)

The relevancy is calculated in Aspen depending on boosting numbers. The higher the boost, the higher the relevancy. There are many factors that are included in the determination of the relevancy of a particular title for a patrons search. The relevancy is based on a combination of settings and built in code. Below the boosting is listed out based on what kinds of search a patron is performing and other factors that Aspen considers. You can also reference the code itself for a more in depth look at how searching affects the relevancy. You can find that [here](https://github.com/mdnoble73/aspen-discovery/blob/21.02.00/sites/default/conf/groupedWorksSearchSpecs.yaml)

### Keyword Searching Relevancy:
 * When using Keyword search, if the input exactly matches the title of a record, then that record will get a boost of 10000. 
 * If the search term matches the beginning of the title it will get a boost of 8000. As the title match is less exact, the relevancy will decrease accordingly. 
 * If the search in keyword exactly matches an author field, then we get a boost of 2000. 
 * If the search matches the beginning of the author, we get a boost of 1000 and if the search hits other parts of the author it does take that into account for relevancy but much smaller (from 50-200 depending on what parts of the author it hits)
 * Aspen will also check to see if the input in the keyword search matches the barcode, issn, isbn, and call numbers and return those records.
 * For the call number, Aspen will boost an exact match on call number with a boost of 400 and if the call number only matches the beginning it will boost it with a boost of 300.
 * If the input you have in the keyword search matches the series information, it will boost the record if it's series exactly matches with a boost of 1500. If it matches on part of a series name it will boost some but less (30-1500). 
 * Finally, Aspen will check to see if the search you put into keyword lives in the table of contents, subject, topic, geographic region, genre, era, keywords, description, target audience, and literary form (nonfiction/fiction).

### Keyword Proper Searching Relevancy:
Keyword proper differs from keyword by having quotations around the search term. For example, Aspen will follow the Keyword Search criteria if you do not have quotations around Harry Potter but if you do "Harry Potter" the relevancy will follow the following relevancy guidelines:
  * If the input surrounded by quotations exactly match a title, it will have a boost of 2400. 
  * If the quoted input matches the beginning of a title, it will have a boost of 1600. 
  * If the quoted input exactly matches an author, it has a boost of 2200. 
  * If the quoted input matches the beginning of the author, it has a boost of 1000. 
  * If the quoted input matches the series of a title, it has a boost of 500.
  * The proper keyword will also look for the input in the table of contents, topics, geographic region, genre, keywords, issn, isbn, upc, OCLC number, call number, alternate ids, and barcode.



### Title Searching Relevancy:
 * If the search term exactly matches a title, there is a boost of 10000.
 * If the search term matches the beginning of the title, there is a boost of 8000.
 * If the search matches parts of the title, it will be boosted from 50-800 depending on the level of match.
 * If the search matches part of a series, it will boost from 50-800 depending on the levle of the match.

### Title Proper Searching Relevancy:
Title proper searching differs from title search by having quotations around the search term.
 * If the quoted search term exactly matches a title, there is a boost of 2400.
 * If the quoted search term matches the beginning of the title, there is a boost of 2400.
 * If the search matches part of a series, it has a boot of 100.


### Start of Title Searching Relevancy:
 * If the search term exactly matches the beginning, or the start of a title, there is a boost of 1600.

### Series Searching Relevancy:
 * If the search term exactly matches the series, there is a boost of 2400.
 * If the search term matches on some of the series information, there is a boost of 50-1000 depending on the level of the match.

### Series Proper Searching Relevancy:
Series proper searching differs from series depending on if you have quotations around the search.
 * If the quoted search term exactly matches the series, there is a boost of 2400.
 * If the quoted search term matches on some of the series information, there is a boost of 500-1000 depending on the level of the match.

### Author Searching Relevancy:
If you are doing an author search in the catalog, if the input exactly matches a records author it will have a boost of 2200, regardless of if it is surrounded by quotations. If the author matches the beginning of the author, it will have a boost of 1400.

### Subject Searching Relevancy:
 * If the search term exactly matches the subject, there is a boost of 300.
 * If the search term exactly matches the topic, there is a boost of 300.
 * If the search term exactly matches the geographic region, there is a boost of 300.
 * If the search term exactly matches the genre, there is a boost of 300.
 * If the search term exactly matches the era, there is a boost of 300.

### Subject Proper Searching Relevancy:
Subject proper searching differs from subject depending on if you have quotations around the search.
 * If the quoted search term exactly matches the subject, there is a boost of 300.
 * If the quoted search term exactly matches the topic, there is a boost of 300.
 * If the quoted search term exactly matches the geographic region, there is a boost of 300.
 * If the quoted search term exactly matches the genre, there is a boost of 300.
 * If the quoted search term matches part of the subject, topic, geographic region, genre, or era there is a boost of 100.

### Check Outs and the Effect on Relevancy
Aspen Discovery also considers the number of checkouts on items and titles. As patrons use a title more, Aspen Discovery will increase the relevancy of that title. The system does this because most likely if a title is very popular and being checked out often, it is a title other patrons would be expecting to see when doing a search. 

### Number of Holdings
In the Catalog / Grouped Works section of the administration menu, if you click into the specific ‘Grouped Work Display’ settings there is a checkbox option that says 'Apply Number Of Holdings Boost'. This setting adds boosting or relevancy to title records that have a lot of items attached. The thought here is that you are most likely buying a lot of copies of popular titles but maybe not as many of the unpopular titles. If a title record has 20 items attached it may show higher in search results than a title with one item attached. 

### Manipulating Relevancy
In addition to the ways that Aspen Discovery already decides relevancy and boosting, we also have the ability to manipulate that logic for the specific patrons and their needs. You can actually self assign boosting to the formats. We recommend using a scale of 1-12 for this. 1 being less relevant and 12 is very relevant. For example, if you want to make sure Books or DVDs are showing higher in relevancy, therefore higher in a patrons search results than Microfilm then you can assign the Book format a relevancy of 12 and Microfilm a 1. You will go into ILS Integration -> Indexing Profiles -> Click into the profile and scroll down to ‘Format Information’. In the ‘Format Map’ there is a column called ‘Format Boost’. This is where you input the boosting number.
