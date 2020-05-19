##Library Systems

- Library Systems is a part of inital setup for Aspen. This lives under Account -> Primary Configuration

![Library Systems](/manual/images/Library-Systems.png)

- Within library systems you can customize many aspects of Aspen for your system. You will want include subdomain and base URL in your options. This lets Aspen know which URL is specific to a system. This is especially important if you have many systems and subdomains.
- If you fill in 'Display Name' that will display within the header of Aspen if you click the box 'Show Display Name in Header'.
- Systems Message allows you to add customized language if you have an annoucement for your patrons. This message will display in the header.
- The checkbox 'Generate Sitemap' will allow pages within Aspen to be searchable on search engines like Google.
- The dropdown for 'Basic Display' allows you to reference which of your themes you want to use for your system.
- The 'Contact Links' allows you to input the appropriate links for social media pages. When you input a link, the social media icon will appear at the bottom right of your Aspen page. The icons are clickable and redirect patrons to those pages.
- ILS/Account Integration offers a few choices for how you would like your ILS to interact with Aspen Discovery.
    - ILS Code is asking for the location code or codes that your items for this system start with. If you want all items you can use .* - this pulls all items.
    - The checkbox 'Show Expiration Warnings' will warn patrons when their accounts are set to expire soon.
    - The checkbox 'Show While You Wait' allows Aspen to give patrons recommendations on items when placing holds. Reference the section of the manual on 'While You Wait' for more information.
    - The checkbox 'Allow Linked Accounts' tells Aspen that relationships between patron accounts in your ILS are present and can be allowed in Aspen, as well. For example if your ILS has guarantor and guarantee relationships.
    - The checkbox 'Show Library Hours Notice on Account Pages' determines whether or not the library hours should show My Account's Checked Out, Holds, and Bookings page.
  - Barcode
    - You can let Aspen know what your minimum and maximum length on patron barcodes.
    - If you define a Barcode Prefix, patrons can login with their cardnumber without the prefix. This is useful when patrons are more familar with thier unique id instead of the entire cardnumber.
  - You can also customize language and placement of information for User Profile, Holds, Login, and Self Registration in their respective dropdowns.
  - Masquerade Mode, if enabled, allows staff to 'login' with a patrons cardnumber to show searches and information.
- Fines/e-commerce is where you would set up the connection to your PayPal account. This is where you can update settings to allow certain actions. For example, you can choose between letting the patron choose what fines to pay or forcing them to pay all their fines. You can also set a minimum fine amount here. In order to connect to PayPal, you will need you PayPal ClientID and PayPal Client Secret from PayPal. 
- If you set up different grouped work settings, you will define what your system uses with the 'Grouped Work Display Settings' dropdown.
- Within the Searching dropdown, you can choose to allow searches within your system to be confined to only those branches. Additionally, you can choose if lists are searchable and which types of lists are searchable. 
- Catalog Enrichment allows you to choose if patrons can make lists.
- Within Full Record Display you have the option to allow patrons the ability to email themselves or share online a record they are interested in.
- Holdings Summary gives you options on customizing how holdings data is displayed in results.
- Materials Request System is where you can establish a connection to how you handle materials requests. If you use an external source, this is where you would input this URL.
- Similarly, Interlibrary loans is where you establish what system you use for ILL
- Below all of these dropdowns, you also have the option to enable Genealogy functionality, include DPLA content in searches, and add library holidays
  - In addition to enabling DPLA content in searches, you will need to set up a connection to DP.LA - see the DP.LA section of the manual for more information.
- Records owned allows you to tell Aspen what items are owned by your system. If you are a one branch system, you can input .* as your location, which tells Aspen all of the locations are your items. If you are a multi-branch system then you will need to define your distinct locations in the locations section under Library Systems. You can find more information on that on the location page of the manual. 
- Records to Include tells Aspen what records should be included in your searches.

