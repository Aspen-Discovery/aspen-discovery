## Literary Form Criteria

In order to determine the literary form Aspen determines fiction vs. nonfiction by various checks through the biblio record. First, the code looks at the leader positions 6 and 7. If position 6 = 'A' or 'T' and position 7 = 'A', 'C', 'D'
, or 'M', the code goes to the 006 field. If the 006 is more than 16 characters long, Aspen uses the litery form criteria specified by the Library of Congress standards. You can find those specifications here for the 006: https://www.loc.gov/marc/bibliographic/bd006.html

If Aspen is unable to determine the literary form based on the 006, it then goes to the 008. Aspen confirms the 008 is more than 33 characters and uses the litery form criteria specified by the Library of Congress standards. You can find those specifications here for the 008 here: 
https://www.loc.gov/marc/bibliographic/bd008.html

At this point, the code looks at the subjects in the 650$v and 651$v. Additionally, there are some checks to look in the 655$a. The code looks for specfic subjects and determines nonfiction vs. fiction. You can see below the list of subjects per literary form.

Fiction
- Cartoons and comics
- Children's fiction
- Comedy
- Comic books, strips, etc
- Comic books,strips, etc
- Drama
- Dramas
- Fantasy
- Fictional Works
- Folklore
- Humor
- Humor, Juvenile
- Humour
- Juvenile drama
- Juvenile fiction
- Juvenile Humor
- Juvenile Poetry
- Junior fiction
- Legends
- Mystery fiction
- Novela juvenil
- Novela
- Novels
- Poetry
- Romances
- Satire
- Science fiction comics
- Short stories
- Stories
- Wit and humor
- Young adult fiction

If any of the above subjects are in the 650$v or 651$v, they will have a fiction literary form. 

Non Fiction
- Autobiography
- Biography
- Biographies
- Case studies
- Catalogs
- Cookbooks
- Dictionaries
- Dictionaries, Juvenile
- Encyclopedias
- Encyclopedias, Juvenile
- essays
- Essays
- Guidebooks
- Guide-books
- Handbooks
- Handbooks, manuals, etc
- Interviews
- Juvenile non-fiction
- Letters
- Maps
- Nonfiction
- Non-fiction
- Personal narratives, American
- Personal narratives, Polish
- Personal narratives, Sudanese
- Personal narratives, Jewish
- Personal narratives
- Problems, exercises, etc
- Recipes
- Diaries
- Designs and Plans
- Reference books
- Travel guide
- Textbook
- Atlas
- Atlases
- Study guides

If any of the above subjects are in the 650$v or 651$v, they will have a non fiction literary form. 

If the 655$a has instructional film or educational film then the literary form will be non fiction. 

If the 008 or 006 literary form conflicts with the literary form from the subjects, the subjects take precedence. If a record has multiple subjects that conflict with one another, the literary form with the most subjects will take precedence.   
