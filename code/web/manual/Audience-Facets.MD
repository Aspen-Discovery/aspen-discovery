## Audience Facet

In Aspen Discovery, you can configure the audience data to pull from an item level field. In order to do this you will need to configure that in ILS Integration -> Indexing Profile -> 'Determine Audience By'. If you choose to use that option, you do need to create a translation map called 'Audience'. In this instance, Aspen will reference the item data first and if it does not exist or if it is econtent, Aspen will default to the bib record data. 

The determination coming from the bib record for the 'Audience' facet is based first on leader to determine the record type. Then based off of the Library of Congress recommendations, we grab the audience from the 008 and the 006. You can see those recommendations in their entirety here:

https://www.loc.gov/marc/bibliographic/bd008.html

https://www.loc.gov/marc/bibliographic/bd006.html

If position 6 of the leader is 'A' or 'T' and position 7 is 'A','C','D' or 'M' then the record type is 'Books'. If position 6 of the leader is 'M' then the record type is 'Computer Files'. If position 6 of the leader is 'C', 'D', 'I' or 'J' then the record type is 'Music'. In all of these instances, we then go to the 006 or 008 to determine the audience. 

If position 6 of the leader is 'G', 'K', 'O', 'R', or does not exist then the record type does not have an audience determiner and we classify that as 'unknown'.

We then move onto the 006 and we look at position 5 to determine the audience. The audience is mapped in the following way:

* A = Juvenile
* B = Juvenile
* C = Juvenile
* D = Young Adult
* E = Adult
* F = Special
* G = General
* J = Juvenile
* Juvenile = Juvenile
* Adult = Adult
* Easy = Juvenile
* YA = Young Adult
* Unknown = Unknown
* | = Unknown
* '*' = Adult

We then move onto the 008 and we look at position 22 to determine the audience. The audience is mapped in the following way:


* A = Juvenile
* B = Juvenile
* C = Juvenile
* D = Young Adult
* E = Adult
* F = Special
* G = General
* J = Juvenile
* Juvenile = Juvenile
* Adult = Adult
* Easy = Juvenile
* YA = Young Adult
* Unknown = Unknown
* | = Unknown
* '*' = Adult

If the audience is undetermined from any of the above logic, the audience will default to "Unknown".
