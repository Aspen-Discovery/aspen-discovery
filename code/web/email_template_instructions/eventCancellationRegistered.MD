Sent to a patron who was registered for an event when that event is cancelled or rescheduled.

The following template variables are available:
- library.displayName - The name of the active library where the patron registered
- library.baseUrl - The URL to the aspen instance where the patron registered
- library.messagingSettingsUrl - The URL where the user can update messaging preferences (Koha, Symphony, and Sierra only)
- library.email - The contact email of the library where the patron registered
- user.firstname - The first name of the patron
- user.lastname - The last name of the patron
- user.userPreferredName - The preferred name of the patron
- user.ils_barcode - The barcode of the patron
- changeType - The type of change applied to the event ("cancelled" by default).
- eventInstances - A formatted list of the affected event instance(s) including title, date, and time.

Template variables are case-insensitive and should be enclosed within percent symbols. I.e.

  _Hello %user.firstname%, the event you registered for at %library.displayName% has been %changeType%._
