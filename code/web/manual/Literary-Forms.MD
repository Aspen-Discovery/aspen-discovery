## Literary Form Criteria

In order to determine the literary form Aspen determines fiction vs. nonfiction by various checks through the biblio record. First, the code looks at the leader positions 6 and 7. If position 6 = 'A' or 'T' and position 7 = 'A', 'C', 'D'
, or 'M', the code goes to the 006 field. If the 006 is more than 16 characters long, Aspen uses the litery form criteria specified by the Library of Congress standards. You can find those specifications here for the 006: https://www.loc.gov/marc/bibliographic/bd006.html

If Aspen is unable to determine the literary form based on the 006, it then goes to the 008. Aspen confirms the 008 is more than 33 characters and uses the litery form criteria specified by the Library of Congress standards. You can find those specifications here for the 008 here: 
https://www.loc.gov/marc/bibliographic/bd008.html

At this point, the code looks at the subjects in the 650$v and 651$v. Additionally, there are some checks to look in the 655$a. The code looks for specfic subjects and determines nonfiction vs. fiction. You can see below the list of subjects per literary form.

Fiction
-("Young adult fiction" )
-("Juvenile fiction" )
-("Junior fiction" )
-("Comic books, strips, etc")
-("Comic books,strips, etc")
-("Children's fiction" )
-("Fictional Works" )
-("Cartoons and comics" )
-("Folklore" )
-("Legends" )
-("Stories" )
-("Fantasy" )
-("Mystery fiction")
-("Romances")
-("Novela juvenil")
-("Novela")
-("Novels")
-("Drama")
-("Dramas")
-("Juvenile drama")
-("Poetry")
-("Juvenile Poetry")
-("Humor")
-("Juvenile Humor")
-("Comedy")
-("Wit and humor")
-("Satire")
-("Humor, Juvenile")
-("Humour")
-("Short stories")

If any of the above subjects are in the 650$v or 651$v, they will have a fiction literary form. 

Non Fiction
-("Biography")
-("Letters")
-("essays")
-("Essays")
-("Personal narratives, American")
-("Personal narratives, Polish")
-("Personal narratives, Sudanese")
-("Personal narratives, Jewish")
-("Personal narratives")
-("Guidebooks")
-("Guide-books")
-("Handbooks, manuals, etc")
-("Problems, exercises, etc")
-("Case studies")
-("Handbooks")
-("Biographies")
-("Interviews")
-("Autobiography")
-("Cookbooks")
-("Dictionaries")
-("Encyclopedias")
-("Encyclopedias, Juvenile")
-("Dictionaries, Juvenile")
-("Nonfiction")
-("Non-fiction")
-("Juvenile non-fiction")
-("Maps")
-("Catalogs")
-("Recipes")
-("Diaries")
-("Designs and Plans")
-("Reference books")
-("Travel guide")
-("Textbook")
-("Atlas")
-("Atlases")
-("Study guides")

If any of the above subjects are in the 650$v or 651$v, they will have a non fiction literary form. 

If the 655$a has ("instructional film") or 
("educational film") then the literary form will be non fiction. 

If the 008 or 006 literary form conflicts with the literary form from the subjects, the subjects take precedence. If a record has multiple subjects that conflict with one another, the literary form with the most subjects will take precedence.   
