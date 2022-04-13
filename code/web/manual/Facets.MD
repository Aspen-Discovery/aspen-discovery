##Facet Customization

You can customize your facets, including which facets are included in search and how those facets appear. The facets can be customized through the grouped work facets section of Catalog/Grouped Works in the administration menu. 

[![Facets](/manual/images/Customizing-Facets.jpg)](https://youtu.be/MA1Myln_-sA)

[Watch on Youtube](https://youtu.be/MA1Myln_-sA)


##Facet Definitions
- **AR Interest Level**: Renaissance Learning based off ISBN- we get a weekly download- automatically taken care of in Aspen
- **AR Point Value**:	Renaissance Learning based off ISBN- we get a weekly download- automatically taken care of in Aspen
- **AR Reading Level**: Renaissance Learning based off ISBN- we get a weekly download- automatically taken care of in Aspen
- **Added in the Last**:	First date that the grouped work is added to the catalog; you can do it location specific for a library system/consortium but there is sometimes issues with econtent dates and shared eresource collections. Added in the last should exlude re-ordered copies of items already owned. Generally, Aspen is looking at date created to determine this information. Re-orders might still appear if they are the only item on a bib. 
- **[Audience Facet](/Admin/HelpManual?page=Audience-Facets)** Click for more detailed information
- **Author**:	100 a,b,c,d,q or 110ab
- **Available At**:Listing by physical location it is available at
- **Available**: Toggle up at the top of search results
- **Awards**:	The Awards facet is pulled from the 586$a
- **Bisac Subject**: 650 field. Aspen will treat 650 fields where subfield 2 holds ""bisacsh"" OR ""bisacmt"" OR ""bisacrt"" as BISAC Subjects.
- **Branch**:	Who owns it; regardless of who has it or not (might not have a lot of usefulness to general public)
- **Collection**: This facet is pulled from different places depending on the ILS and your ILS indexing profile. You will define where the collection lives in your marc records by going into the Administration Menu -> ILS Integration -> Indexing Profiles. Click into your profile and scroll down to the 'Item Information'. Whatever subfield you choose to put into the 'Collection' field is where Aspen Discovery grabs the information for the Collection facet. You can fine tune or change the way those collections display by going into ILS Integration -> Translation Maps. You should have a map called 'collection'. The first column should hold the database codes that live in whatever item subfield you specified as the collection. The translation column is what Aspen will show in the facet list. If you want one of the collections to not display in the facet list, you will leave the translation blank for that code."
- **Detailed Location	Branch**: (owned) + Shelf Location (best used for special collections and when combining facets)
- **E-Content Collection**:	Any integrated econtent vendors
- **Earliest Publication Year**: Earliest publication year is the first year that any record in a grouped work was published (helpful for classics or replacements)
- **Era**:	The values for the era facet come from the following places in your bibliographic records:
    - 600$d
    - 610$y
    - 611$y
    - 630$y
    - 648$a
    - 648$y
    - 650$y
    - 651$y
    - 655$y
- **Form**: 
    - 0 = Non Fiction
    - 1 = Fiction
    - D = Fiction
    - E = Non Fiction
    - F = Fiction
    - H = Non Fiction
    - I = Non Fiction
    - J = Fiction
    - M = Fiction
    - P = Fiction
    - S = Non Fiction
    - U = Unknown
    - Unknown = Unknown
    - {*} = Not Coded (anything that doesn't match the above values)
 - **Format:** Using Format Information Chart in ILS Indexing Profiles
 - **Format Category:** Using Format Information Chart in ILS Indexing Profiles to group like formats; also toggle/icons at the top of search results
 - **Fountas & Pinnell:**	Alternative to AR; 521a if the 521a starts with Guided Reading Level: {space}
 - **Genre:**	655 subfields a and v
 - **Item Type:**	"This facet is pulled from different places depending on the ILS and your ILS indexing profile. You will define where the item type lives in your marc records by going into the Administration Menu -> ILS Integration -> Indexing Profiles. Click into your profile and scroll down to the 'Item Information'. Whatever subfield you choose to put into the 'iType' field is where Aspen Discovery grabs the information for the Item Type facet. You can fine tune or change the way those item types display by going into ILS Integration -> Translation Maps. You should have a map called 'itype'. The first column should hold the database codes that live in whatever item subfield you specified as the item type. The translation column is what Aspen will show in the facet list. If you want one of the item types to not display in the facet list, you will leave the translation blank for that code."
 - **LC Call Number**	DISREGARD- We Will Delete
 - **LC Subject:**	Aspen Discovery pulls LC subject facets from the 650 field and subfields:
    Within the 650 field: Aspen will treat 650 fields with a 2nd indicator of '0' OR '1' as an LC Subject (if a subject is BISAC, it cannot also be an LC Subject)
 - **Language:** The language facet is pulled from the following fields and subfields:
      - 008 in position 35-37
      - 041$b
      - 041$d
      - 041$h
      - 041$j
 
 You can find the language codes here: https://www.loc.gov/marc/languages/language_code.html
 - **Lexile Code:** 521 subfield a and subfield b but only if subfield b contains the word lexile in it
 - **Lexile Measure:**	521 subfield a and subfield b but only if subfield b contains the word lexile in it
 - **Library System:**	Library systems; typically within a catalog for a consortium
 - **[Literary Form (Nonfiction/Fiction) Facet](/Admin/HelpManual?page=Literary-Forms)** Click For Detailed Information
 - **Movie Rating:**	521a comparing a bunch of different patterns to pull rating out of it
 - **Publication Year:**	264 subfield c or the 260 c or 008 position 7-10; Aspen will give you the option to sort and you can jump to a year, 5 years, 10 years
 - **Publisher:**	264b and 260b
 - **Rating:** Ratings from logged in users rating titles within Aspen
 - **[Reading Level Facet](/Admin/HelpManual?page=ReadingLevel-Facets)** Click for Detailed Information
 - **Region:**	650$z
 - **Series** Could come from a variety of fields
       - GroupedWorkSolr - addSeriesWithVolume & GroupedWorkSolr - addSeriesInfoToField 
       - Novelist if the data exists in our cache 
       - MARC 830anp 830v, 800pqt 
       - OverDrive - series 
       - Hoopla - series & episode
 - **[Shelf Location Facet](/Admin/HelpManual?page=ShelfLocation-Facet)** Click for Detailed Information
 - **Subjects:**	Within the 600 field: If the subfield is >= 'a' AND = 'j' AND = 'x' AND = 'a' AND = 'j' AND = 'x' AND = 'c' AND = 'k' AND = 'p' AND = 'x' AND = 'f' AND = 'k' AND = 'r' AND = 'v' OR >= 'x' AND = 'a' AND = 'v' OR >= 'x' AND = 'a' AND = 'v' OR >= 'x' AND = 'a' AND = 'v' OR >= 'x' & = 'x' AND
 - **Topics:**	"The values for the topic facet come from the following places in your bibliographic records:
        - 600$a
        - 600$x
        - 610$x
        - 611$x
        - 630$x
        - 648$x
        - 650$a
        - 650$x
        - 651$x
        - 655$x"
  -**Translations:**	041 b, 041b, 041h, and 041j 
