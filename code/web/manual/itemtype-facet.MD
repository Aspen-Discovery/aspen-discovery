## Item Type Facet

This facet is pulled from different places depending on the ILS and your ILS indexing profile. You will define where the item type lives in your marc records by going into the Administration Menu -> ILS Integration -> Indexing Profiles. Click into your profile and scroll down to the 'Item Information'. Whatever subfield you choose to put into the 'iType' field is where Aspen Discovery grabs the information for the Item Type facet. 

You can fine tune or change the way those item types display by going into ILS Integration -> Translation Maps. You should have a map called 'itype'. The first column should hold the database codes that live in whatever item subfield you specified as the item type. The translation column is what Aspen will show in the facet list. If you want one of the item types to not display in the facet list, you will leave the translation blank for that code. 
