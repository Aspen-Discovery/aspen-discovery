Sent to a patron whose waiting list invitation to register for an event expired before they completed registration. The patron's invitation has been cleared and, if seats remain on the waiting list, they may re-join from the event page.

The following template variables are available:
- library.displayName - The name of the active library where the patron registered
- library.baseUrl - The URL to the aspen instance where the patron registered
- library.messagingSettingsUrl - The URL where the user can update messaging preferences (Koha, Symphony, and Sierra only)
- library.email - The contact email of the library where the patron registered
- user.firstname - The first name of the patron
- user.lastname - The last name of the patron
- user.userPreferredName - The preferred name of the patron
- user.ils_barcode - The barcode of the patron
- event.title - The title of the event
- event.date - The date of the event instance
- event.time - The time of the event instance

Template variables are case-insensitive and should be enclosed within percent symbols. I.e.

  _Hello %user.firstname%, your invitation to register for %event.title% on %event.date% at %event.time% has expired._
