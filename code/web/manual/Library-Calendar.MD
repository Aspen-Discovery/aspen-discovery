## Library Calendar

**Setting Up Library Calendar**

Library Calendar by Library Market is a calendar that can be integrated directly into Aspen Discovery.

To begin, have a library Admin go to **System Administration > Modules**, find the **Events** module and make sure **Enabled?** is clicked. Then click save.

Then, make sure you set corresponding permissions. Select your role from 'Role to Edit' or the role you wish to add the Events permissions to, and scroll to find the **Events** menu. Assign the permission **Administer Library Calendar Settings- 
Allows the user to administer integration with Library Calendar for all libraries.** and click save. 

You should now see the Events module appear in the Aspen Administration menu. 

![Events Module](/manual/images/EventsModule.png)

Click into **Library Market - Library Calendar Settings** 
Click **Add New**
Name your settings (Exp: Library Calendar)
Fill in the base URL (Exp: https://yoursite.librarymarket.com)
Select which Library location(s) you would like this to apply to
Click Save

*Note: We used to need the other fields like Client Secret and Admin login informaiton. You should no longer need that information but it also does not hurt if you fill those fields in*

**Finding Events in Aspen Discovery**
Once your events begin to be pulled in, they will begin to show up in related library catalog searches in the **Explore More** box.
You will also notice that in your Aspen search bar, you know have an **Events** field in your dropdown menu so users can also search directly in Aspen for events.

![Events Dropdown](/manual/images/EventsDropdown.png)

**Events Indexing Log**
You will also notice in **Aspen Administration > Events** that you have an option for **Indexing Log**. Here you can check the Events connection and keep track of any errors. Like with the other logs in Aspen, you can click **Show Notes** to find out more details about the indexing. 
