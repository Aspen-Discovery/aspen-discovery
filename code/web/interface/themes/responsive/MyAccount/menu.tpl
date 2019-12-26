{strip}
	{if $loggedIn}
		{* Setup the accoridon *}
		<!--suppress HtmlUnknownTarget -->
		<div id="home-account-links" class="sidebar-links row"{if $displaySidebarMenu} style="display: none"{/if}>
			<div class="panel-group accordion" id="account-link-accordion">
				{* My Account *}
				<a id="account-menu"></a>
				{if $module == 'MyAccount' || ($module == 'Search' && $action == 'Home') || ($module == 'MaterialsRequest' && $action == 'MyRequests')}
					{assign var="curSection" value=true}
				{else}
					{assign var="curSection" value=false}
				{/if}

				<div class="panel{if $displaySidebarMenu || $curSection} active{/if}">
					{* With SidebarMenu on, we should always keep the MyAccount Panel open. *}

					{* Clickable header for my account section *}
					<a data-toggle="collapse" data-parent="#account-link-accordion" href="#myAccountPanel">
						<div class="panel-heading">
							<div class="panel-title">
								{*MY ACCOUNT*}
								{translate text="My Account"}
							</div>
						</div>
					</a>
					{*  This content is duplicated in MyAccount/mobilePageHeader.tpl; Update any changes there as well *}
					<div id="myAccountPanel" class="panel-collapse collapse{if  $displaySidebarMenu || $curSection} in{/if}">
						<div class="panel-body">
							<span class="expirationFinesNotice-placeholder"></span>
							{if $userHasCatalogConnection}
								<div class="myAccountLink">
									<a href="/MyAccount/CheckedOut" id="checkedOut">
										{translate text="Checked Out Titles"}
									</a>
								</div>
								<ul class="account-submenu">
									<li class="myAccountLink">
										&nbsp;&nbsp;&raquo;&nbsp;
										<a href="/MyAccount/CheckedOut?tab=ils" id="checkedOutIls">
											{translate text="Physical Materials"} {if !$offline}<span class="badge"><span class="ils-checkouts-placeholder">??</span></span> <span class="ils-overdue" style="display: none"> <span class="label label-danger"><span class="ils-overdue-placeholder"></span> {translate text="Overdue"}</span></span>{/if}
										</a>
									</li>
									{if $user->isValidForEContentSource('overdrive')}
										<li class="myAccountLink">
											&nbsp;&nbsp;&raquo;&nbsp;
											<a href="/MyAccount/CheckedOut?tab=overdrive" id="checkedOutOverDrive">
												{translate text="OverDrive"} {if !$offline}<span class="badge"><span class="overdrive-checkouts-placeholder">??</span></span>{/if}
											</a>
										</li>
									{/if}
									{if $user->isValidForEContentSource('hoopla')}
										<li class="myAccountLink">
											&nbsp;&nbsp;&raquo;&nbsp;
											<a href="/MyAccount/CheckedOut?tab=hoopla" id="checkedOutHoopla">
									            {translate text="Hoopla"} {if !$offline}<span class="badge"><span class="hoopla-checkouts-placeholder">??</span></span>{/if}
											</a>
										</li>
									{/if}
									{if $user->isValidForEContentSource('rbdigital')}
										<li class="myAccountLink">
											&nbsp;&nbsp;&raquo;&nbsp;
											<a href="/MyAccount/CheckedOut?tab=rbdigital" id="checkedOutRBdigital">
												{translate text="RBdigital"} {if !$offline}<span class="badge"><span class="rbdigital-checkouts-placeholder">??</span></span>{/if}
											</a>
										</li>
									{/if}
									{if $user->isValidForEContentSource('cloud_library')}
										<li class="myAccountLink">
										&nbsp;&nbsp;&raquo;&nbsp;
											<a href="/MyAccount/CheckedOut?tab=cloud_library" id="checkedOutCloudLibrary">
											    {translate text="Cloud Library"} {if !$offline}<span class="badge"><span class="cloud_library-checkouts-placeholder">??</span></span>{/if}
											</a>
										</li>
									{/if}
								</ul>

								<div class="myAccountLink">
									<a href="/MyAccount/Holds" id="holds">
										{translate text="Titles On Hold"}
									</a>
								</div>
								<ul class="account-submenu">
									<li class="myAccountLink">
										&nbsp;&nbsp;&raquo;&nbsp;
										<a href="/MyAccount/Holds?tab=ils" id="holdsIls">
											{translate text="Physical Materials"} {if !$offline}<span class="badge"><span class="ils-holds-placeholder">??</span></span> <span class="ils-available-holds" style="display: none"> <span class="label label-success"><span class="ils-available-holds-placeholder"></span> {translate text="Ready for Pickup"}</span></span>{/if}
										</a>
									</li>
									{if $user->isValidForEContentSource('overdrive')}
										<li class="myAccountLink">
											&nbsp;&nbsp;&raquo;&nbsp;
											<a href="/MyAccount/Holds?tab=overdrive" id="holdsOverDrive">
												{translate text="OverDrive"} {if !$offline}<span class="badge"><span class="overdrive-holds-placeholder">??</span></span> <span class="overdrive-available-holds" style="display: none"> <span class="label label-success"><span class="overdrive-available-holds-placeholder"></span> {translate text="Available Now"}</span></span>{/if}
											</a>
										</li>
									{/if}
									{if $user->isValidForEContentSource('rbdigital')}
										<li class="myAccountLink">
											&nbsp;&nbsp;&raquo;&nbsp;
											<a href="/MyAccount/Holds?tab=rbdigital" id="holdsRBdigital">
												{translate text="RBdigital"} {if !$offline}<span class="badge"><span class="rbdigital-holds-placeholder">??</span></span>{/if}
											</a>
										</li>
									{/if}
                                    {if $user->isValidForEContentSource('cloud_library')}
										<li class="myAccountLink">
											&nbsp;&nbsp;&raquo;&nbsp;
											<a href="/MyAccount/Holds?tab=cloud_library" id="holdsCloudLibrary">
												{translate text="Cloud Library"} {if !$offline}<span class="badge"><span class="cloud_library-holds-placeholder">??</span></span> <span class="cloud_library-available-holds" style="display: none"> <span class="label label-success"><span class="cloud_library-available-holds-placeholder"></span> {translate text="Available Now"}</span></span>{/if}
											</a>
										</li>
									{/if}
								</ul>

								{if $enableMaterialsBooking}
									<div class="myAccountLink">
										<a href="/MyAccount/Bookings" id="bookings">
											{translate text="Scheduled Items"} {if !$offline}<span class="badge"><span class="bookings-placeholder">??</span></span>{/if}
										</a>
									</div>
								{/if}
								<div class="myAccountLink">
									<a href="/MyAccount/ReadingHistory">
										{translate text="Reading History"} {if !$offline}<span class="badge"><span class="readingHistory-placeholder">??</span></span>{/if}
									</a>
								</div>
								{if $showFines}
									<div class="myAccountLink" title="Fines and account messages">
										<a href="/MyAccount/Fines">{translate text='Fines and Messages'}</a>
									</div>
								{/if}
							{/if}
							{if $materialRequestType == 1 && $enableAspenMaterialsRequest}
								<div class="myAccountLink" title="{translate text='Materials Requests' inAttribute=true}">
									<a href="/MaterialsRequest/MyRequests">{translate text='Materials Requests'} <span class="badge"><span class="materialsRequests-placeholder">??</span></span></a>
								</div>
							{elseif $materialRequestType == 2 && $userHasCatalogConnection}
								<div class="myAccountLink" title="{translate text='Materials Requests' inAttribute=true}">
									<a href="/MaterialsRequest/IlsRequests">{translate text='Materials Requests'} <span class="badge"><span class="materialsRequests-placeholder">??</span></span></a>
								</div>
							{/if}
							{if $showRatings}
								<hr class="menu">
								<div class="myAccountLink"><a href="/MyAccount/MyRatings">{translate text='Titles You Rated'} <span class="badge"><span class="ratings-placeholder">??</span></span></a></div>
								<ul class="account-submenu">
								{if $user->disableRecommendations == 0}
									<li class="myAccountLink">&nbsp;&nbsp;&raquo;&nbsp;<a href="/MyAccount/SuggestedTitles">{translate text='Recommended For You'}</span></a></li>
								{/if}
								</ul>
							{/if}
							<hr class="menu">
							<div class="myAccountLink">{translate text='Account Settings'}</div>
							<ul class="account-submenu">
								<li class="myAccountLink">&nbsp;&nbsp;&raquo;&nbsp;<a href="/MyAccount/MyPreferences">{translate text='My Preferences'}</a></li>
								<li class="myAccountLink">&nbsp;&nbsp;&raquo;&nbsp;<a href="/MyAccount/ContactInformation">{translate text='Contact Information'}</a></li>
                                {if $user->showMessagingSettings()}
									<li class="myAccountLink">&nbsp;&nbsp;&raquo;&nbsp;<a href="/MyAccount/MessagingSettings">{translate text='Messaging Settings'}</a></li>
                                {/if}
								{if $allowAccountLinking}
									<li class="myAccountLink">&nbsp;&nbsp;&raquo;&nbsp;<a href="/MyAccount/LinkedAccounts">{translate text='Linked Accounts'}</a></li>
								{/if}
								{if $allowPinReset && !$offline}
									<li class="myAccountLink">&nbsp;&nbsp;&raquo;&nbsp;<a href="/MyAccount/ResetPinPage">{translate text='Reset PIN/Password'}</a></li>
								{/if}
								{if $user->isValidForEContentSource('overdrive')}
									<li class="myAccountLink">&nbsp;&nbsp;&raquo;&nbsp;<a href="/MyAccount/OverDriveOptions">{translate text='OverDrive Options'}</a></li>
								{/if}
{*								{if $user->isValidForEContentSource('rbdigital')}*}
{*									<li class="myAccountLink">&nbsp;&nbsp;&raquo;&nbsp;<a href="/MyAccount/RBdigitalOptions">{translate text='RBdigital Options'}</a></li>*}
{*								{/if}*}
								{if $user->isValidForEContentSource('hoopla')}
									<li class="myAccountLink">&nbsp;&nbsp;&raquo;&nbsp;<a href="/MyAccount/HooplaOptions">{translate text='Hoopla Options'}</a></li>
								{/if}
								{if $userIsStaff || count($user->roles) > 0}
									<li class="myAccountLink">&nbsp;&nbsp;&raquo;&nbsp;<a href="/MyAccount/StaffSettings">{translate text='Staff Settings'}</a></li>
								{/if}
							</ul>
							{* Only highlight saved searches as active if user is logged in: *}
							<div class="myAccountLink{if $user && $pageTemplate=="history.tpl"} active{/if}"><a href="/Search/History?require_login">{translate text='history_saved_searches'}</a></div>
							{if $allowMasqueradeMode && !$masqueradeMode}
								{if $canMasquerade}
									<hr class="menu">
									<div class="myAccountLink"><a onclick="AspenDiscovery.Account.getMasqueradeForm();" href="#">{translate text="Masquerade"}</a></div>
								{/if}
							{/if}
						</div>
					</div>
				</div>

				{* My Lists*}
				{if $action == 'MyList'}
					{assign var="curSection" value=true}
				{else}
					{assign var="curSection" value=false}
				{/if}
				<div class="panel{if $curSection} active{/if}">
					<a data-toggle="collapse" data-parent="#account-link-accordion" href="#myListsPanel">
						<div class="panel-heading">
							<div class="panel-title">
								{translate text='My Lists'}
							</div>
						</div>
					</a>
					<div id="myListsPanel" class="panel-collapse collapse{if $action == 'MyRatings' || $action == 'Suggested Titles' || $action == 'MyList'} in{/if}">
						<div class="panel-body">
							<div id="lists-placeholder"><img src="/images/loading.gif" alt="loading"></div>

							<div class="myAccountLink">
								<a href="#" onclick="return AspenDiscovery.Account.showCreateListForm();" class="btn btn-sm btn-primary">{translate text='Create a New List'}</a>
							</div>
							{if $showConvertListsFromClassic}
								<br>
								<div class="myAccountLink">
									<a href="/MyAccount/ImportListsFromClassic" class="btn btn-sm btn-default">{translate text="Import From Old Catalog"}</a>
								</div>
							{/if}
						</div>
					</div>
				</div>

				{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles))}
					{if in_array($action, array('Themes', 'GroupedWorkDisplay'))}
						{assign var="curSection" value=true}
					{else}
						{assign var="curSection" value=false}
					{/if}
					<div class="panel{if $curSection} active{/if}">
						<a href="#configurationTemplatesMenuGroup" data-toggle="collapse" data-parent="#adminMenuAccordion">
							<div class="panel-heading">
								<div class="panel-title">
									{translate text="Configuration Templates"}
								</div>
							</div>
						</a>
						<div id="configurationTemplatesMenuGroup" class="panel-collapse collapse {if $curSection}in{/if}">
							<div class="panel-body">
								{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles))}
									<div class="adminMenuLink"><a href="/Admin/Themes">{translate text="Themes"}</a></div>
								{/if}
								{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles))}
									<div class="adminMenuLink"><a href="/Admin/LayoutSettings">{translate text="Layout Settings"}</a></div>
								{/if}
								{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles))}
									<div class="adminMenuLink"><a href="/Admin/GroupedWorkDisplay">{translate text="Grouped Work Display Settings"}</a></div>
								{/if}
								{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles))}
									<div class="adminMenuLink">&nbsp;&raquo;&nbsp;<a href="/Admin/GroupedWorkFacets">{translate text="Grouped Work Facets"}</a></div>
								{/if}
							</div>
						</div>
					</div>
				{/if}

				{* Admin Functionality if Available *}
				{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('contentEditor', $userRoles) || array_key_exists('libraryManager', $userRoles) || array_key_exists('locationManager', $userRoles) || array_key_exists('translator', $userRoles))}
					{if in_array($action, array('Libraries', 'Locations', 'IPAddresses', 'ListWidgets', 'BrowseCategories', 'Placards', 'PTypes', 'AccountProfiles', 'NYTLists', 'BlockPatronAccountLinks', 'Languages'))}
						{assign var="curSection" value=true}
					{else}
						{assign var="curSection" value=false}
					{/if}
					<div class="panel{if $curSection} active{/if}">
						<a href="#primaryAdminMenuGroup" data-toggle="collapse" data-parent="#adminMenuAccordion">
							<div class="panel-heading">
								<div class="panel-title">
									{translate text="Primary Configuration"}
								</div>
							</div>
						</a>
						<div id="primaryAdminMenuGroup" class="panel-collapse collapse {if $curSection}in{/if}">
							<div class="panel-body">
								{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('translator', $userRoles))}
									<div class="adminMenuLink"><a href="/Translation/Languages">{translate text="Languages"}</a></div>
								{/if}
								{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('translator', $userRoles))}
									<div class="adminMenuLink">&nbsp;&raquo;&nbsp;<a href="/Translation/Translations">{translate text="Translations"}</a></div>
								{/if}
								{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('libraryManager', $userRoles))}
									<div class="adminMenuLink"><a href="/Admin/Libraries">{translate text="Library Systems"}</a></div>
								{/if}
								{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('libraryManager', $userRoles) || array_key_exists('locationManager', $userRoles))}
									<div class="adminMenuLink">&nbsp;&raquo;&nbsp;<a href="/Admin/Locations">{translate text="Locations"}</a></div>
								{/if}
								{* OPAC Admin Actions*}
								{if array_key_exists('opacAdmin', $userRoles)}
									<div class="adminMenuLink">&nbsp;&raquo;&nbsp;<a href="/Admin/IPAddresses">{translate text="IP Addresses"}</a></div>
								{/if}
								{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('libraryManager', $userRoles) || array_key_exists('locationManager', $userRoles))}
									<div class="adminMenuLink"><a href="/Admin/BlockPatronAccountLinks">{translate text="Block Patron Account Linking"}</a></div>
								{/if}
								{* Content Editor Actions *}
								{if array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('contentEditor', $userRoles) || array_key_exists('libraryManager', $userRoles) || array_key_exists('locationManager', $userRoles)}
									<div class="adminMenuLink"><a href="/Admin/ListWidgets">{translate text="List Widgets"}</a></div>
								{/if}
								{if array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('contentEditor', $userRoles) || array_key_exists('libraryManager', $userRoles) || array_key_exists('locationManager', $userRoles)}
									<div class="adminMenuLink"><a href="/Admin/BrowseCategories">{translate text="Browse Categories"}</a></div>
								{/if}
								{if array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('contentEditor', $userRoles) || array_key_exists('libraryManager', $userRoles) || array_key_exists('locationManager', $userRoles)}
									<div class="adminMenuLink"><a href="/Admin/Placards">{translate text="Placards"}</a></div>
								{/if}
								{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('libraryManager', $userRoles) || array_key_exists('contentEditor', $userRoles))}
									<div class="adminMenuLink"><a href="/Admin/NYTLists">{translate text="NY Times Lists"}</a></div>
								{/if}

								{* OPAC Admin Actions*}
								{if array_key_exists('opacAdmin', $userRoles)}
									<div class="adminMenuLink"><a href="/Admin/PTypes">{translate text="Patron Types"}</a></div>
									{* OPAC Admin Actions*}
									<div class="adminMenuLink"><a href="/Admin/AccountProfiles">{translate text="Account Profiles"}</a></div>
								{/if}

							</div>
						</div>
					</div>
				{/if}

				{if (array_key_exists('userAdmin', $userRoles) || array_key_exists('opacAdmin', $userRoles))}
					{if in_array($action, array('Modules', 'Administrators', 'DBMaintenance', 'UsageDashboard', 'PerformanceReport', 'ErrorReport', 'PHPInfo', 'OpCacheInfo', 'Variables', 'CronLog'))
					|| ($module == 'Admin' && $action == 'Home')}
						{assign var="curSection" value=true}
					{else}
						{assign var="curSection" value=false}
					{/if}
					<div class="panel{if $curSection} active{/if}">
						<a href="#adminMenuGroup" data-toggle="collapse" data-parent="#adminMenuAccordion">
							<div class="panel-heading">
								<div class="panel-title">
									{translate text="System Administration"}
								</div>
							</div>
						</a>
						<div id="adminMenuGroup" class="panel-collapse collapse {if $curSection}in{/if}">
							<div class="panel-body">
                                {if array_key_exists('opacAdmin', $userRoles)}
	                                <div class="adminMenuLink "><a href="/Admin/Modules">{translate text="Modules"}</a></div>
								{/if}
								{if array_key_exists('userAdmin', $userRoles)}
									<div class="adminMenuLink "><a href="/Admin/Administrators">{translate text="Administrators"}</a></div>
								{/if}
								{if array_key_exists('opacAdmin', $userRoles)}
									<div class="adminMenuLink"><a href="/Admin/DBMaintenance">{translate text="DB Maintenance"}</a></div>
									<div class="adminMenuLink"><a href="/Admin/UsageDashboard">{translate text="Usage Dashboard"}</a></div>
									<div class="adminMenuLink"><a href="/Admin/ErrorReport">{translate text="Error Report"}</a></div>
									<div class="adminMenuLink"><a href="/Admin/PerformanceReport">{translate text="Performance Report"}</a></div>
									<div class="adminMenuLink"><a href="/Admin/SendGridSettings">{translate text="SendGrid Settings"}</a></div>
									<div class="adminMenuLink"><a href="/Admin/Home">{translate text="Solr Information"}</a></div>
									<div class="adminMenuLink"><a href="/Admin/PHPInfo">{translate text="PHP Information"}</a></div>
									<div class="adminMenuLink"><a href="/Admin/Variables">{translate text="System Variables"}</a></div>
									<div class="adminMenuLink"><a href="/Admin/CronLog">{translate text="Cron Log"}</a></div>
								{/if}
							</div>
						</div>
					</div>
				{/if}

				{if (array_key_exists('libraryAdmin', $userRoles) || array_key_exists('opacAdmin', $userRoles) || array_key_exists('cataloging', $userRoles))}
					{if in_array($action, array('RecordGroupingLog', 'ReindexLog'))}
						{assign var="curSection" value=true}
					{else}
						{assign var="curSection" value=false}
					{/if}
					<div class="panel{if $curSection} active{/if}">
						<a href="#indexingMenuGroup" data-toggle="collapse" data-parent="#adminMenuAccordion">
							<div class="panel-heading">
								<div class="panel-title">
									{translate text="Indexing Information"}
								</div>
							</div>
						</a>
						<div id="indexingMenuGroup" class="panel-collapse collapse {if $curSection}in{/if}">
							<div class="panel-body">
								<div class="adminMenuLink"><a href="/Admin/RecordGroupingLog">{translate text="Record Grouping Log"}</a></div>
								<div class="adminMenuLink"><a href="/Admin/ReindexLog">{translate text="Grouped Work Index Log"}</a></div>
							</div>
						</div>
					</div>
				{/if}

				{if ($enableAspenMaterialsRequest && $materialRequestType == 1) && array_key_exists('library_material_requests', $userRoles)}
					{if in_array($action, array('ManageRequests', 'SummaryReport', 'UserReport', 'ManageStatuses'))}
						{assign var="curSection" value=true}
					{else}
						{assign var="curSection" value=false}
					{/if}
					<div class="panel{if $curSection} active{/if}">
						<a href="#materialsRequestMenu" data-toggle="collapse" data-parent="#adminMenuAccordion">
							<div class="panel-heading">
								<div class="panel-title">
									{translate text="Materials Requests"}
								</div>
							</div>
						</a>
						<div id="materialsRequestMenu" class="panel-collapse collapse {if $curSection}in{/if}">
							<div class="panel-body">
								<div class="adminMenuLink"><a href="/MaterialsRequest/ManageRequests">{translate text="Manage Requests"}</a></div>
								<div class="adminMenuLink"><a href="/MaterialsRequest/SummaryReport">{translate text="Summary Report"}</a></div>
								<div class="adminMenuLink"><a href="/MaterialsRequest/UserReport">{translate text="Report By User"}</a></div>
								<div class="adminMenuLink"><a href="/Admin/ManageStatuses">{translate text="Manage Statuses"}</a></div>
								<div class="adminMenuLink"><a href="https://docs.google.com/document/d/1s9qOhlHLfQi66qMMt5m-dJ0kGNyHiOjSrqYUbe0hEcA">{translate text="Documentation"}</a></div>
							</div>
						</div>
					</div>
				{/if}

				{if (array_key_exists('cataloging', $userRoles) || array_key_exists('opacAdmin', $userRoles))}
					{if in_array($action, array('MergedGroupedWorks', 'NonGroupedRecords', 'AuthorEnrichment', 'ARSettings'))}
						{assign var="curSection" value=true}
					{else}
						{assign var="curSection" value=false}
					{/if}
					<div class="panel{if $curSection} active{/if}">
						<a href="#catalogingMenu" data-toggle="collapse" data-parent="#adminMenuAccordion">
							<div class="panel-heading">
								<div class="panel-title">
									{translate text="Cataloging"}
								</div>
							</div>
						</a>
						<div id="catalogingMenu" class="panel-collapse collapse {if $curSection}in{/if}">
							<div class="panel-body">
								<div class="adminMenuLink"><a href="/Admin/MergedGroupedWorks">{translate text="Grouped Work Merging"}</a></div>
								<div class="adminMenuLink"><a href="/Admin/NonGroupedRecords">{translate text="Records To Not Group"}</a></div>
								<div class="adminMenuLink"><a href="/Admin/AuthorEnrichment">{translate text="Author Enrichment"}</a></div>
								{if array_key_exists('opacAdmin', $userRoles)}
									<div class="adminMenuLink"><a href="/RenaissanceLearning/ARSettings">{translate text="Accelerated Reader Settings"}</a></div>
								{/if}
							</div>
						</div>
					</div>
				{/if}

				{if (array_key_exists('libraryAdmin', $userRoles) || array_key_exists('opacAdmin', $userRoles) || array_key_exists('cataloging', $userRoles))}
					{if $module == 'ILS' && in_array($action, array('IndexingLog', 'TranslationMaps', 'IndexingProfiles', 'Dashboard', 'LoanRules', 'LoanRuleDeterminers'))}
						{assign var="curSection" value=true}
					{else}
						{assign var="curSection" value=false}
					{/if}
					<div class="panel{if $curSection} active{/if}">
						<a href="#ilsMenu" data-toggle="collapse" data-parent="#adminMenuAccordion">
							<div class="panel-heading">
								<div class="panel-title">
									{translate text="ILS Integration"}
								</div>
							</div>
						</a>
						<div id="ilsMenu" class="panel-collapse collapse {if $curSection}in{/if}">
							<div class="panel-body">
								<div class="adminMenuLink"><a href="/ILS/IndexingProfiles">{translate text="Indexing Profiles"}</a></div>
								<div class="adminMenuLink"><a href="/ILS/TranslationMaps">&nbsp;&raquo;&nbsp;{translate text="Translation Maps"}</a></div>
								{* Sierra/Millennium OPAC Admin Actions*}
								{if ($ils == 'Millennium' || $ils == 'Sierra')}
									<div class="adminMenuLink"><a href="/Admin/LoanRules">&nbsp;&raquo;&nbsp;{translate text="Loan Rules"}</a></div>
									<div class="adminMenuLink"><a href="/Admin/LoanRuleDeterminers">&nbsp;&raquo;&nbsp;{translate text="Loan Rule Determiners"}</a></div>
								{/if}
								<div class="adminMenuLink"><a href="/ILS/IndexingLog">{translate text="Indexing Log"}</a></div>
								{if ($ils == 'Millennium' || $ils == 'Sierra')}
									<div class="adminMenuLink"><a href="/Admin/SierraExportLog">{translate text="Sierra Export Log"}</a></div>
								{/if}
								<div class="adminMenuLink"><a href="/ILS/Dashboard">{translate text="Dashboard"}</a></div>
							</div>
						</div>
					</div>
				{/if}

				{if array_key_exists('OverDrive', $enabledModules) && (array_key_exists('libraryAdmin', $userRoles) || array_key_exists('opacAdmin', $userRoles) || array_key_exists('cataloging', $userRoles))}
					{if $module == 'OverDrive' && in_array($action, array('APIData', 'ExtractLog', 'Settings', 'Scopes', 'Dashboard'))}
						{assign var="curSection" value=true}
					{else}
						{assign var="curSection" value=false}
					{/if}
					<div class="panel{if $curSection} active{/if}">
						<a href="#overdriveMenu" data-toggle="collapse" data-parent="#adminMenuAccordion">
							<div class="panel-heading">
								<div class="panel-title">
									{translate text="OverDrive"}
								</div>
							</div>
						</a>
						<div id="overdriveMenu" class="panel-collapse collapse {if $curSection}in{/if}">
							<div class="panel-body">
								<div class="adminMenuLink"><a href="/OverDrive/Settings">{translate text="Settings"}</a></div>
								<div class="adminMenuLink">&nbsp;&raquo;&nbsp;<a href="/OverDrive/Scopes">{translate text="Scopes"}</a></div>
								<div class="adminMenuLink"><a href="/OverDrive/IndexingLog">{translate text="Indexing Log"}</a></div>
								<div class="adminMenuLink"><a href="/OverDrive/Dashboard">{translate text="Dashboard"}</a></div>
								<div class="adminMenuLink"><a href="/OverDrive/APIData">{translate text="API Information"}</a></div>
							</div>
						</div>
					</div>
				{/if}

				{if array_key_exists('Hoopla', $enabledModules) && (array_key_exists('libraryAdmin', $userRoles) || array_key_exists('opacAdmin', $userRoles) || array_key_exists('cataloging', $userRoles))}
					{if $module == 'Hoopla' && in_array($action, array('IndexingLog', 'Settings', 'Scopes', 'Dashboard'))}
						{assign var="curSection" value=true}
					{else}
						{assign var="curSection" value=false}
					{/if}
					<div class="panel{if $curSection} active{/if}">
						<a href="#hooplaMenu" data-toggle="collapse" data-parent="#adminMenuAccordion">
							<div class="panel-heading">
								<div class="panel-title">
									{translate text="Hoopla"}
								</div>
							</div>
						</a>
						<div id="hooplaMenu" class="panel-collapse collapse {if $curSection}in{/if}">
							<div class="panel-body">
								<div class="adminMenuLink"><a href="/Hoopla/Settings">{translate text="Settings"}</a></div>
								<div class="adminMenuLink">&nbsp;&raquo;&nbsp;<a href="/Hoopla/Scopes">{translate text="Scopes"}</a></div>
								<div class="adminMenuLink"><a href="/Hoopla/IndexingLog">{translate text="Indexing Log"}</a></div>
								<div class="adminMenuLink"><a href="/Hoopla/Dashboard">{translate text="Dashboard"}</a></div>
							</div>
						</div>
					</div>
				{/if}

				{if array_key_exists('RBdigital', $enabledModules) && (array_key_exists('libraryAdmin', $userRoles) || array_key_exists('opacAdmin', $userRoles) || array_key_exists('cataloging', $userRoles))}
					{if $module == 'RBdigital' && in_array($action, array('Settings', 'IndexingLog', 'Scopes', 'Dashboard'))}
						{assign var="curSection" value=true}
					{else}
						{assign var="curSection" value=false}
					{/if}
					<div class="panel{if $curSection} active{/if}">
						<a href="#rbdigitalMenu" data-toggle="collapse" data-parent="#adminMenuAccordion">
							<div class="panel-heading">
								<div class="panel-title">
									{translate text="RBdigital"}
								</div>
							</div>
						</a>
						<div id="rbdigitalMenu" class="panel-collapse collapse {if $curSection}in{/if}">
							<div class="panel-body">
								<div class="adminMenuLink"><a href="/RBdigital/Settings">{translate text="Settings"}</a></div>
								<div class="adminMenuLink">&nbsp;&raquo;&nbsp;<a href="/RBdigital/Scopes">{translate text="Scopes"}</a></div>
								<div class="adminMenuLink"><a href="/RBdigital/IndexingLog">{translate text="Indexing Log"}</a></div>
								<div class="adminMenuLink"><a href="/RBdigital/Dashboard">{translate text="Dashboard"}</a></div>
							</div>
						</div>
					</div>
				{/if}

                {if array_key_exists('Cloud Library', $enabledModules) && (array_key_exists('libraryAdmin', $userRoles) || array_key_exists('opacAdmin', $userRoles) || array_key_exists('cataloging', $userRoles))}
                    {if $module == 'CloudLibrary' && in_array($action, array('Settings', 'IndexingLog', 'Scopes', 'Dashboard'))}
                        {assign var="curSection" value=true}
                    {else}
                        {assign var="curSection" value=false}
                    {/if}
					<div class="panel{if $curSection} active{/if}">
						<a href="#cloudLibraryMenu" data-toggle="collapse" data-parent="#adminMenuAccordion">
							<div class="panel-heading">
								<div class="panel-title">
                                    {translate text="Cloud Library"}
								</div>
							</div>
						</a>
						<div id="cloudLibraryMenu" class="panel-collapse collapse {if $curSection}in{/if}">
							<div class="panel-body">
								<div class="adminMenuLink"><a href="/CloudLibrary/Settings">{translate text="Settings"}</a></div>
								<div class="adminMenuLink">&nbsp;&raquo;&nbsp;<a href="/CloudLibrary/Scopes">{translate text="Scopes"}</a></div>
								<div class="adminMenuLink"><a href="/CloudLibrary/IndexingLog">{translate text="Indexing Log"}</a></div>
								<div class="adminMenuLink"><a href="/CloudLibrary/Dashboard">{translate text="Dashboard"}</a></div>
							</div>
						</div>
					</div>
				{/if}

				{if array_key_exists('Side Loads', $enabledModules) && (array_key_exists('libraryAdmin', $userRoles) || array_key_exists('opacAdmin', $userRoles) || array_key_exists('cataloging', $userRoles))}
					{if $module == 'SideLoads' && in_array($action, array('IndexingLog', 'Scopes', 'SideLoads', 'Dashboard'))}
						{assign var="curSection" value=true}
					{else}
						{assign var="curSection" value=false}
					{/if}
					<div class="panel{if $curSection} active{/if}">
						<a href="#sideLoadMenu" data-toggle="collapse" data-parent="#adminMenuAccordion">
							<div class="panel-heading">
								<div class="panel-title">
									{translate text="Side Loaded eContent"}
								</div>
							</div>
						</a>
						<div id="sideLoadMenu" class="panel-collapse collapse {if $curSection}in{/if}">
							<div class="panel-body">
								<div class="adminMenuLink"><a href="/SideLoads/SideLoads">{translate text="Side Load Configurations"}</a></div>
								<div class="adminMenuLink"><a href="/SideLoads/Scopes">&nbsp;&raquo;&nbsp;{translate text="Scopes"}</a></div>
								<div class="adminMenuLink"><a href="/SideLoads/IndexingLog">{translate text="Indexing Log"}</a></div>
								<div class="adminMenuLink"><a href="/SideLoads/Dashboard">{translate text="Dashboard"}</a></div>
							</div>
						</div>
					</div>
                {/if}

				{if $islandoraEnabled && (array_key_exists('archives', $userRoles) || array_key_exists('opacAdmin', $userRoles))}
					{if in_array($action, array('ArchiveSubjects', 'ArchivePrivateCollections', 'ArchiveRequests', 'AuthorshipClaims', 'ClearArchiveCache', 'ArchiveUsage'))}
						{assign var="curSection" value=true}
					{else}
						{assign var="curSection" value=false}
					{/if}
					<div class="panel{if $curSection} active{/if}">
						<a href="#archivesMenu" data-toggle="collapse" data-parent="#adminMenuAccordion">
							<div class="panel-heading">
								<div class="panel-title">
									{translate text="Archives"}
								</div>
							</div>
						</a>
						<div id="archivesMenu" class="panel-collapse collapse {if $curSection}in{/if}">
							<div class="panel-body">
								<div class="adminMenuLink"><a href="/Admin/ArchiveRequests">{translate text="Archive Material Requests"}</a></div>
								<div class="adminMenuLink"><a href="/Admin/AuthorshipClaims">{translate text="Archive Authorship Claims"}</a></div>
								<div class="adminMenuLink"><a href="/Admin/ArchiveUsage">{translate text="Archive Usage"}</a></div>
								<div class="adminMenuLink"><a href="/Admin/ArchiveSubjects">{translate text="Archive Subject Control"}</a></div>
								{if array_key_exists('opacAdmin', $userRoles)}
									<div class="adminMenuLink"><a href="/Admin/ArchivePrivateCollections">{translate text="Archive Private Collections"}</a></div>
									<div class="adminMenuLink"><a href="/Admin/ClearArchiveCache">{translate text="Clear Cache"}</a></div>
								{/if}
							</div>
						</div>
					</div>
				{/if}

				{if array_key_exists('Open Archives', $enabledModules) && (array_key_exists('archives', $userRoles) || array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles))}
					{if $module == 'OpenArchives' && in_array($action, array('Collections', 'Dashboard', 'IndexingLog'))}
						{assign var="curSection" value=true}
					{else}
						{assign var="curSection" value=false}
					{/if}
					<div class="panel{if $curSection} active{/if}">
						<a href="#openArchivesMenu" data-toggle="collapse" data-parent="#adminMenuAccordion">
							<div class="panel-heading">
								<div class="panel-title">
									{translate text="Open Archives"}
								</div>
							</div>
						</a>
						<div id="openArchivesMenu" class="panel-collapse collapse {if $curSection}in{/if}">
							<div class="panel-body">
								<div class="adminMenuLink"><a href="/OpenArchives/Collections">{translate text="Collections"}</a></div>
								<div class="adminMenuLink"><a href="/OpenArchives/IndexingLog">{translate text="Indexing Log"}</a></div>
								<div class="adminMenuLink"><a href="/OpenArchives/Dashboard">{translate text="Dashboard"}</a></div>
							</div>
						</div>
					</div>
				{/if}

				{if array_key_exists('Web Indexer', $enabledModules) && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles))}
					{if $module == 'Websites' && in_array($action, array('Settings', 'Dashboard', 'IndexingLog'))}
						{assign var="curSection" value=true}
					{else}
						{assign var="curSection" value=false}
					{/if}
					<div class="panel{if $curSection} active{/if}">
						<a href="#websitesMenu" data-toggle="collapse" data-parent="#adminMenuAccordion">
							<div class="panel-heading">
								<div class="panel-title">
									{translate text="Website Indexing"}
								</div>
							</div>
						</a>
						<div id="websitesMenu" class="panel-collapse collapse {if $curSection}in{/if}">
							<div class="panel-body">
								<div class="adminMenuLink"><a href="/Websites/Settings">{translate text="Settings"}</a></div>
								<div class="adminMenuLink"><a href="/Websites/IndexingLog">{translate text="Indexing Log"}</a></div>
								<div class="adminMenuLink"><a href="/Websites/Dashboard">{translate text="Dashboard"}</a></div>
							</div>
						</div>
					</div>
				{/if}

				{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('circulationReports', $userRoles))}
					{if $module == 'Circa'}
						{assign var="curSection" value=true}
					{else}
						{assign var="curSection" value=false}
					{/if}
					<div class="panel{if $curSection} active{/if}">
						<a href="#circulationMenu" data-toggle="collapse" data-parent="#adminMenuAccordion">
							<div class="panel-heading">
								<div class="panel-title">
									{translate text="Circulation"}
								</div>
							</div>
						</a>
						<div id="circulationMenu" class="panel-collapse collapse {if $curSection}in{/if}">
							<div class="panel-body">
								<div class="adminMenuLink{if $action == "OfflineHoldsReport" && $module == "Circa"} active{/if}"><a href="/Circa/OfflineHoldsReport">{translate text="Offline Holds Report"}</a></div>
							</div>
						</div>
					</div>
				{/if}

			</div>

			{include file="library-links.tpl" libraryLinks=$libraryAccountLinks linksId='home-library-account-links' section='Account'}
		</div>
	{/if}
	{if $userHasCatalogConnection}
		<script type="text/javascript">
            AspenDiscovery.Account.loadMenuData();
		</script>
	{/if}
	<script type="text/javascript">
        AspenDiscovery.Account.loadListData();
        AspenDiscovery.Account.loadRatingsData();
	</script>
{/strip}
