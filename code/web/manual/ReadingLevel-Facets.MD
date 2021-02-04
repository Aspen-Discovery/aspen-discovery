## Reading Level Facet

The determination coming from the bib record for the 'Reading Level' facet is based first on leader to determine the record type. Then based off of the Library of Congress recommendations, we grab the reading level from the 008 and the 006. You can see those recommendations in their entirety here:

https://www.loc.gov/marc/bibliographic/bd008.html

https://www.loc.gov/marc/bibliographic/bd006.html

If position 6 of the leader is 'A' or 'T' and position 7 is 'A','C','D' or 'M' then the record type is 'Books'. If position 6 of the leader is 'M' then the record type is 'Computer Files'. If position 6 of the leader is 'C', 'D', 'I' or 'J' then the record type is 'Music'. In all of these instances, we then go to the 006 or 008 to determine the reading level. 

If position 6 of the leader is 'G', 'K', 'O', 'R', or does not exist then the record type does not have an reading level determiner and we classify that as 'unknown'.

We then move onto the 006 and we look at position 5 to determine the reading level. The reading level is mapped in the following way:

* A = Preschool (0-5)
* B = Primary (6-8)
* C = Pre-adolescent (9-13)
* D = Adolescent (14-17)
* E = Adult
* F = Special Interest
* G = General Interest
* J = Juvenile
* Juvenile = Juvenile
* Adult = Adult
* Easy = Easy Reader
* YA = Young Adult
* Unknown = Unknown
* | = No Attempt To Code
* '*' = Unknown

We then move onto the 008 and we look at position 22 to determine the reading level. The reading level is mapped in the following way:

* A = Preschool (0-5)
* B = Primary (6-8)
* C = Pre-adolescent (9-13)
* D = Adolescent (14-17)
* E = Adult
* F = Special Interest
* G = General Interest
* J = Juvenile
* Juvenile = Juvenile
* Adult = Adult
* Easy = Easy Reader
* YA = Young Adult
* Unknown = Unknown
* | = No Attempt To Code
* '*' = Unknown


If the reading level is undetermined from any of the above logic, the reading level will default to "Unknown".

Keep in mind many econtent vendors do not supply this data, so this may cause high counts of Unknown reading level. 

