## Library Systems

- Library Systems is a part of inital setup for Aspen. This lives under Account -> Primary Configuration

![Library Systems](/manual/images/Library-Systems-Update.png)

### Creating a New Library System

- To create a library system, click the 'Add New' box.
- If you have more than one system, you can click the small checkbox next to two and click 'Compare' at the bottom of the list to see the similarities and differences in configuration for the two.
- To edit already created systems you can either click the 'Edit' box on the right hand side of the screen or click the Library ID
- Underneath the edit button the right hand side, there is a 'History' box. This shows you changes made in the configuration. 

- Within library systems you can customize many aspects of Aspen for your system.
- You will want include subdomain and base URL in your options. This lets Aspen know which URL is specific to a system. This is especially important if you have many systems and subdomains.
- If you fill in 'Display Name' that will display within the header of Aspen if you click the box 'Show Display Name in Header'.
- If you click the box 'Consortial Interface' it treats this Library system as part of a consortium, including other Library systems on the same Aspen installation. This setting assumes showing items from all locations and includes all libraries in the Owning Library and Owning Location search facets. Additionally, all owned location copies will show within a grouped works in search results.
- The 'Create Search Interface' checkbox controls whether or not the library has a scope created for it in Solr. 
- The 'Show in Select Interface' checkbox enables... (It requires the 'Create Search Interface' checkbox to be ticked)
- Systems Message allows you to add customized messages if you have an annoucement for your patrons. This message will display in the header of all Library Locations in this Library System. More granular system messages down to Library Location level, and also with time/date publishing controls can be configured under >Local Catalog Enrichment>System Messages. [Read more here](/Admin/HelpManual?page=System-Messages).
- The checkbox 'Generate Sitemap' will allow pages within Aspen to be searchable on search engines like Google.
- The dropdown for **Basic Display** allows you to reference which theme and layout settings to use for your system. The Home Link can contain a URL to an external website, if applicable. You can control where the home link is used within Layout Settings. 
- **Contact Links** allows you to input the appropriate links for social media pages. When you input a link, the social media icon will appear at the bottom right of your Aspen page. The icons are clickable and redirect patrons to those pages.
- **ILS/Account Integration** offers a few choices for how you would like your ILS to interact with Aspen Discovery.
    - ILS Code: the location code for this system.
    - Show Card Expiration Date: whether or not the user should be shown their card's expiration date in the My Library Card page of their account.
    - Show Expiration Warnings: will warn patrons when their accounts are set to expire soon.
    - Expiration Near Message: a custom message to show when the user's account will expire soon (if the above option is checked).
    - Expired Messafe: a custom message to show when the user's account is expired.
    - Show While You Wait: allows Aspen to give patrons recommendations on items when placing holds. Reference the section of the manual on 'While You Wait' for more information.
    - Show Messaging Settings (Koha/Symphony): whether or not the user should be able to see their messaging preferences in their account.
    - Allow Linked Accounts: enables users to link multiple library accounts under a single Aspen account. This setting exists independently of any guarantor and guarantee settings in your ILS, if applicable.
    - Show Library Hours Notice on Account Pages: determines whether or not the library hours should show on the checkouts and holds pages within the user account.
    - Display Item Barcodes in Patron Accounts: whether or not patrons can see item barcodes for materials they have checked out.
  - Barcode
    - You can let Aspen know your minimum and maximum length of patron barcodes.
    - If you define a Barcode Prefix, patrons can login with their cardnumber without the prefix. This is useful when patrons are more familiar with their unique id instead of the entire cardnumber.
  - User Profile
     - You have the ability to allow profile updates from the patron to their profile within aspen, address updates from patron, PIN/password reset, alternate library updates and pickup location changes.
     - This is where you define how you would like patron names to be displayed
     - You can enable a forgot password link. To find out more on how to enable this functionality within Aspen Discovery sites connected to Koha you can see more information here: [https://bywatersolutions.com/education/password-reset-in-aspen-discovery](https://bywatersolutions.com/education/password-reset-in-aspen-discovery)
  - Holds
    - customize what information is displayed in regard to holds
  - Login
    - You can choose to prevent login for users that have expired
    - 'Login Form Username Label' and 'Login Form Password Label' will display whatever you put within those boxes. This is what borrowers see and are prompted by when they log in. 
  - Self Registration 
    - In order to enable self registration, you can configure options for self registration and the language they see during the self registration process
    - The default path for the Self Registration page is: /MyAccount/SelfReg
  - Masquerade Mode, if enabled, allows staff to 'log in' with a patron's card number in order to perform actions as that patron and see account information.
- Fines/e-commerce: here, you can set up the connection to your eCommerce vendor. This is where you can update settings to allow certain actions. For example, you can choose between letting the patron choose what fines to pay or forcing them to pay all their fines. You can also set a minimum fine amount here. In order to connect to PayPal, you will need your PayPal ClientID and PayPal Client Secret from PayPal. 
- If you set up different grouped work settings, you will define what your system uses with the 'Grouped Work Display Settings' dropdown.
- Within the Searching dropdown, you can choose to allow searches within your system to be confined to only those branches. Additionally, you can choose if lists are searchable and which types of lists are searchable. 
- Catalog Enrichment allows you to choose if patrons can make lists, whether they can import lists from your legacy OPAC, and whether or not to show Wikipedia content on author pages.
- Within Full Record Display you have the option to allow patrons the ability to email themselves or share online a record they are interested in.
- Holdings Summary gives you options for customizing how holdings data is displayed in results.
- Materials Request System is where you can establish a connection to how you handle materials requests. If you use an external source, this is where you would input this URL.
- Similarly, Interlibrary loans is where you establish what system you use for ILL.
- Below all of these dropdowns, you also have the option to enable Genealogy functionality, include DPLA content in searches, and add library holidays
  - In addition to enabling DPLA content in searches, you will need to set up a connection to DP.LA - see the DP.LA section of the manual for more information.
- **Records Owned** allows you to tell Aspen what items are owned by your system. If you are a one branch system, you can input .* as your location, which tells Aspen all items from all of the locations in this Library System are owned by this Library System. If you are a multi-branch system then you will need to define your distinct locations in the locations section under Primary Configuration. You can find more information on that on the [location page](/Admin/HelpManual?page=Library-Systems-Locations) of the manual. You can also use this section to exclude specific locations or sub-locations from the System's 'records owned'.
- **Records to Include** tells Aspen what records should be included in your searches. Again .* can be used in the 'Locations' box to include all locations that are associated with this Library system. Alternatively each location can be added as a line and its location code used in the 'locations' box. Against each line there are further options to include and excluded different item types, audiences, formats, items on order etc.

## Video Tutorial 

[![Library System and Locations](/manual/images/library-systems-and-locations.jpg)](https://youtu.be/d720bfDRSUI)

[Watch on Youtube](https://youtu.be/d720bfDRSUI)
