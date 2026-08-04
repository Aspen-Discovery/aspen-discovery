The saved search alert is sent if a patron has opted in to receiving saved searches and one or more of their searches has new values.

The following template variables are available: 
- library.displayName - The name of the active library where the patron registered
- library.baseUrl - The URL to the aspen instance where the patron registered
- library.messagingSettingsUrl - The URL where the user can update messaging preferences (Koha, Symphony, and Sierra only)
- library.email - The contact email of the library where the patron registered
- user.firstname - The first name of the patron who registered
- user.lastname - The last name of the patron who registered
- user.ils_barcode - The barcode of the patron who registered
- searchHistory.url - The URL for the patron to access their saved searches. 
- searchHistory.updatedSearches - A list of the searches that were updated.
- searchHistory.updatedSearchesHtml - A list of the searches that were updated including HTML links to the search.
- searchHistory.updatedSearchesWithSampleTitles - A list of the searches that were updated with sample titles that are new. 
- searchHistory.updatedSearchesWithSampleTitlesHtml - A list of the searches that were updated with sample titles that are new with HTML markup.

Template variables are case-insensitive and should be enclosed within percent symbols.  I.e. 

  _Welcome to %library.displayName%._