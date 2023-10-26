The welcome email is sent after a patron has successfully self registered.  

The following template variables are available: 
- library.displayName - The name of the active library where the patron registered
- library.baseUrl - The URL to the aspen instance where the patron registered
- library.email - The contact email of the library where the patron registered
- user.firstname - The first name of the patron who registered
- user.lastname - The last name of the patron who registered
- user.ils_barcode - The barcode of the patron who registered

Template variables are case insensitive and should be enclosed within percent symbols.  I.e. 

  _Welcome to %library.displayName%._