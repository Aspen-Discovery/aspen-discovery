## Materials Requests & Purchase Suggestions

Materials Requests can be enabled in the Library System settings under the Materials Request section. You can choose between the Aspen request system, an ILS request system, an external request link, or no system. Being able to specify material request settings at the system level means that each scoped library can choose whether or not to enable purchase requests for their library, and how to manage their own workflow. 

### **ILS Request System**

Currently, this setting only works with the Koha ILS due to their built-in purchase request management functionality. If you use the Koha purchase suggestion module already, Aspen will pull in the required fields as specified in your system preferences and display the form within Aspen. To learn more about setting up or modifying purchase suggestions in Koha, check out [this blog post](https://bywatersolutions.com/news/purchase-suggestions-opac).

![Materials Request ILS](/manual/images/MaterialsRequestsILS.png) 

You can add an optional message to the form displayed in Aspen in the “New Request Summary” text editor. This will display above the form fields and can be used to inform your patrons of any restrictions or other helpful information. 

![Enter New Suggestion](/manual/images/EnterNewSuggestion.png)

### **External Request Link**

Perhaps you use an external link or another online form for purchase suggestions. You might also have a variety of options available and would like to link patrons to either your library’s website or to a web builder page within Aspen to provide information to your patrons. In this case, setting up an external request link might work best for you.

Simply choose the External Request Link option, make sure “Turn on Materials Request for Public” is checked, and enter in the desired URL. 

![External Materials Request](/manual/images/ExternalMaterialsRequest.png)


**Submitting Material Requests**

If you have any of the purchase suggestion options enabled, patrons can always find a link to submit requests at the bottom of search results. This will either take them to a form to complete, or to the external link you’ve specified in the settings.

When filling out a form, you can specify which fields are required in order to submit the form - other fields will be optional. Mandatory fields will be marked with a Required label. 

![Required Fields Materials Request](/manual/images/RequiredFieldsMaterialsRequest.png)

After filling out the form and submitting with at least all the required fields, patrons will see a success screen confirming their submitted request, along with how many requests they’ve used or have in progress (if applicable). 

![Materials Request Result](/manual/images/MaterialsRequestResult.png)

If either the Aspen or ILS request options are enabled, patrons will also be able to access a link to submit requests from within their account, as well as view and manage their requests. Patrons can click their username, then click Materials Requests to see this information. They’ll be able to filter whether they see only open requests or all requests, check on the status of their requests, and cancel a request if the item is no longer needed. Clicking the “Details” button will show them all the information they supplied when filling out the form. They will not be able to edit this information, but they can cancel and resubmit if there are errors.

![My Materials Requests](/manual/images/MyMaterialsRequests.png)

### **Aspen Request System**

**Before You Begin**

If you have multiple libraries within Aspen that have unique scoped catalogs, each library can set up their own versions of the materials request module with their desired form fields, requestable formats, and statuses. However, this means that permissions for the materials request module are ultimately tied to the admin user’s Home Library. Any administrative user with the permission to access Library Systems settings will be able to perform the initial setup for Materials Requests, but a user will only be able to see the accompanying Materials Request module if their home library matches the Library System for which they’re setting up materials requests.

![Library Systems Settings](/manual/images/MRLibrarySystems.png)

![Library Administrators](/manual/images/MRAdministrators.png)

The admin user will also need the **library_material_requests** role assigned to them. If you no longer have this role, you can create a new role or add permissions to an existing role. In the list of permissions, you’ll need to make sure the top three permissions under Materials Requests are selected. 

![Permissions](/manual/images/MRPermissions.png)

**Getting Started**

To begin setting up Aspen’s materials request system, you’ll start in **Primary Configuration > Library Systems**. Edit the appropriate library system setting, then scroll down to the **Materials Request** section. This is where you will enable the Materials Request module, which will appear as its own section within Aspen Administration. You will also configure the initial setup for the Materials Request form that patrons fill out, and specify which formats can be requested.

Select the **Aspen Request System** from the first dropdown. While you’re still in the configuration stage, you may want to uncheck **Turn on Materials Request for Public**. While this is unchecked, patrons will not be able to access a link to the materials request form from within their accounts or at the bottom of search results. However, you can still get to the form directly by adding /MaterialsRequest/NewRequest to the end of your main catalog URL. This way you can still access the form in order to submit test requests. When you’re ready to go live with the Aspen request system, return to these settings and check Turn on Materials Request for Public to enable.

![Public Request](/manual/images/MRPublicRequest.png)

Next, you’ll specify the **Max Requests Per Year** and **Max Open Requests**. The default is 60 per year and 5 requests open at a time, but you can input whatever works best for your library.

In the **New Request Summary** text editor, you can specify any message you’d like to appear above the request form. Some libraries choose to clarify what can or can’t be requested, how old an item has to be before requesting, etc. 

For **Delete Closed Requests Older Than (days)**, specify the number of days closed requests should remain accessible in the system. If left at 0, all closed requests will be preserved indefinitely.

In the next section, you’ll get to specify the fields that display within the Manage Requests screen. Leaving this blank will automatically populate the management screen with default fields, but you can click Add New here to configure your screen from scratch. 

Under **Formats of Materials that can be Requested**, you’ll specify the formats you want patrons to be able to request on your form. Click **Set Materials Request Formats to Default** to automatically load in a default set of formats. From here, you can edit or delete any of these fields and continue to add new formats if desired. Under the **Format** column, if you add any new formats that aren’t listed, make sure the value there is in all lowercase with no spaces. Under the **Special Fields** for Format column, you can select whether the form asks for additional information if they’ve selected that format for their request.

![Format Fields](/manual/images/MRFormatFields.png)

In the following section, **Materials Request Form Fields**, you can specify the fields to appear on the form your patrons will complete for their requests. Once again, click **Set Materials Request Form Structure to Default** to auto-populate with default settings. To see what the form looks like, save your changes and use the direct form URL mentioned above. This way you can see how the form is organized and make your changes accordingly.

The **Form Category** column corresponds to different section headings on the form. The **Field Label** is the text label for the field, and Field Type specifies the type of information required for the field.

![Form Fields](/manual/images/MRFormFields.png)

![Form Fields Correspond](/manual/images/MRFormFieldsCorrespond.png)

Once you have these settings in place and have saved your changes, you’re ready to head over to the Materials Request module!

**Manage Statuses**

Under the Materials Request module within Aspen Administration, click **Manage Statuses**.

![Manage Statuses](/manual/images/MRManageStatuses.png)

If setting up for the first time, this page will be blank. At the bottom of the screen, click Reset to Default to load in all of the available default statuses. You can also delete unwanted statuses or add in your own. These statuses will show within the Manage Requests screen and will display to the patron as well, depending on the status you’ve set for the request or actions taken by the patron. In order for materials requests to work, at least one status here must be defined as the default. 

![Manage Statuses Chart](/manual/images/MRMaterialsRequestStatus.png)

You can change which status is the default by editing that status and checking **Default Status?** to enable. You also have other options when editing a status, such as whether to switch to that status if a patron cancels, or whether changing to this status sends an email to the patron. If emailing the patron, you can specify the email to send in the **Email Template** text field. 

![Materials Request Status Form](/manual/images/MRMaterialsRequestStatusForm.png)

**Managing Requests**

Under the Materials Request module, click **Manage Requests** to see submissions. To assign requests to specific users, click the checkbox to the left of the request and select a staff member from the dropdown list, then click **Assign Selected Requests**. Similarly, you can update one or more requests with a new status using **Change Status of Selected To** and selecting the status from the dropdown.

Click Details next to a request to see the complete details for a request. You can also click **Update Request** to edit or add information to a patron’s request. 

![Manage Materials Requests](/manual/images/ManageMaterialsRequests.png)

When dealing with many requests, quickly search for a specific request by using the blank filter boxes to search for information in a specific column. Click Filters for even more options for filtering through requests.

![Manage Materials Request Filters](/manual/images/ManageMaterialsRequestsFilters.png)

**Reports**

Access a summary report of all requests by clicking **Summary Report** in the Materials Request module. You can specify a date range for the report and see how many requests were created and how many are still pending from the specific time period. 

You can also access numbers for individual users in the **Report By User** section. See an overview of users with pending requests, filter user requests by specific statuses with Filters, or export the data to Excel. 

![Material Requests Report](/manual/images/MRReports.png)

**Ready for Your Patrons**
Once you’re ready to go live with your materials request form, return to the **Library System** settings, navigate to the Materials Request section, and make sure that **Turn on Materials Request for Public** is checked on. Now your patrons will be able to access the link to the form at the bottom of search results and from within their account. 

![Didnt Find It](/manual/images/DidntFindIt.png)

![Patron Materials Requests](/manual/images/PatronMaterialsRequest.png)

When users visit the Materials Requests section of their account, they’ll have a button to submit a new request along with a summary of their materials requests. They will be able to filter by only open requests or all requests. The user can see the full details of their request, the status of their request, or cancel if desired.

![My Materials Requests](/manual/images/MyMaterialsRequests.png)

### Customizing Materials Request Emails

Once you have setup the material requests module, you can customize emails sent to patrons as you apply your customized statuses to a purchase suggestion placed by a patron. So when a patron places a materials request, you can have Aspen automatically send them a notice letting them know you received that request! Or when you need more information from the patron, you can apply the appropriate status to the request and automate an email to send to the patron letting them know that! You can go beyond a generic message here and customize the email to include details from the specific suggestions. You can use tokens to input data that varies by purchase suggestion. For example, if you want the email to say

Hello John! Your suggestion of 'Harry Potter and the Halfblood Prince' will be purchased by our library and placed on hold for you on arrival.
You can use tokens to allow this! The above message in the email form would look like:

Hello {firstname}! Your suggestion of {title} will be purchased by our library and placed on hold for you on arrival.
The tokens available for use in email forms are below:

- firstname
- lastname
- email
- phone
- cat_username (this is patron cardnumber)
- title
- season
- magazineTItle
- magazineDate
- magazineVolume
- magazineTitle
- magazineDate
- magazineVolume
- magazineNumber
- magazinePageNumbers
- author
- format
- formatId
- subFormat
- ageLevel
- bookType
- isbn
- upc
- issn
- oclcNumber
- publisher
- publicationYear
- abridged
- about
- comments
- status
- phone
- email

[![Materials Request](/manual/images/Materials-Request.jpg)](https://youtu.be/b7kEoeN6jKo)

[Watch on Youtube](https://youtu.be/b7kEoeN6jKo)
