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
								<div class="myAccountLink{if $action=="CheckedOut"} active{/if}">
									<a href="{$path}/MyAccount/CheckedOut" id="checkedOut">
										{translate text="Checked Out Titles"} {if !$offline}<span class="checkouts-placeholder"><img src="{$path}/images/loading.gif" alt="loading"></span>{/if}
									</a>
								</div>
								<div class="myAccountLink{if $action=="Holds"} active{/if}">
									<a href="{$path}/MyAccount/Holds" id="holds">
										{translate text="Titles On Hold"} {if !$offline}<span class="holds-placeholder"><img src="{$path}/images/loading.gif" alt="loading"></span>{/if}
									</a>
								</div>
								{if $enableMaterialsBooking}
									<div class="myAccountLink{if $action=="Bookings"} active{/if}">
										<a href="{$path}/MyAccount/Bookings" id="bookings">
											{translate text="Scheduled Items"} {if !$offline}<span class="bookings-placeholder"><img src="{$path}/images/loading.gif" alt="loading"></span>{/if}
										</a>
									</div>
								{/if}
								<div class="myAccountLink{if $action=="ReadingHistory"} active{/if}">
									<a href="{$path}/MyAccount/ReadingHistory">
										{translate text="Reading History"} {if !$offline}<span class="readingHistory-placeholder"><img src="{$path}/images/loading.gif" alt="loading"></span>{/if}
									</a>
								</div>
								{if $showFines}
									<div class="myAccountLink{if $action=="Fines"} active{/if}" title="Fines and account messages">
										<a href="{$path}/MyAccount/Fines">{translate text='Fines and Messages'}</a>
									</div>
								{/if}
							{/if}
							{if $materialRequestType == 1 && $enableAspenMaterialsRequest}
								<div class="myAccountLink{if $pageTemplate=="myMaterialRequests.tpl"} active{/if}" title="{translate text='Materials Requests' inAttribute=true}">
									<a href="{$path}/MaterialsRequest/MyRequests">{translate text='Materials Requests'} <span class="materialsRequests-placeholder"><img src="{$path}/images/loading.gif" alt="loading"></span></a>
								</div>
							{elseif $materialRequestType == 2 && $userHasCatalogConnection}
								<div class="myAccountLink{if $pageTemplate=="ilsMaterialRequests.tpl"} active{/if}" title="{translate text='Materials Requests' inAttribute=true}">
									<a href="{$path}/MaterialsRequest/IlsRequests">{translate text='Materials Requests'} <span class="materialsRequests-placeholder"><img src="{$path}/images/loading.gif" alt="loading"></span></a>
								</div>
							{/if}
							{if $showRatings}
								<hr class="menu">
								<div class="myAccountLink{if $action=="MyRatings"} active{/if}"><a href="{$path}/MyAccount/MyRatings">{translate text='Titles You Rated'} <span class="ratings-placeholder"><img src="{$path}/images/loading.gif" alt="loading"></span></a></div>
								{if $user->disableRecommendations == 0}
									<div class="myAccountLink{if $action=="SuggestedTitles"} active{/if}"><a href="{$path}/MyAccount/SuggestedTitles">{translate text='Recommended For You'} <span class="recommendations-placeholder"><img src="{$path}/images/loading.gif" alt="loading"></span></a></div>
								{/if}
							{/if}
							<hr class="menu">
							<div class="myAccountLink{if $pageTemplate=="profile.tpl"} active{/if}"><a href="{$path}/MyAccount/Profile">{translate text='Account Settings'}</a></div>
							{* Only highlight saved searches as active if user is logged in: *}
							<div class="myAccountLink{if $user && $pageTemplate=="history.tpl"} active{/if}"><a href="{$path}/Search/History?require_login">{translate text='history_saved_searches'}</a></div>
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
							{if $showConvertListsFromClassic}
								<div class="myAccountLink"><a href="{$path}/MyAccount/ImportListsFromClassic" class="btn btn-sm btn-default">Import Existing Lists</a></div>
								<br>
							{/if}

							<div id="lists-placeholder"><img src="{$path}/images/loading.gif" alt="loading"></div>

							<a href="#" onclick="return AspenDiscovery.Account.showCreateListForm();" class="btn btn-sm btn-primary">{translate text='Create a New List'}</a>
						</div>
					</div>
				</div>

				{* Admin Functionality if Available *}
				{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('contentEditor', $userRoles) || array_key_exists('libraryManager', $userRoles) || array_key_exists('locationManager', $userRoles) || array_key_exists('translator', $userRoles))}
					{if in_array($action, array('Themes', 'Libraries', 'Locations', 'IPAddresses', 'ListWidgets', 'BrowseCategories', 'PTypes', 'LoanRules', 'LoanRuleDeterminers', 'AccountProfiles', 'NYTLists', 'BlockPatronAccountLinks', 'Languages'))}
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
								{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles))}
									<div class="adminMenuLink{if $action == "Themes"} active{/if}"><a href="{$path}/Admin/Themes">{translate text="Themes"}</a></div>
								{/if}
								{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('translator', $userRoles))}
									<div class="adminMenuLink{if $action == "Languages"} active{/if}"><a href="{$path}/Translation/Languages">{translate text="Languages"}</a></div>
								{/if}
								{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('translator', $userRoles))}
									<div class="adminMenuLink{if $action == "Translations"} active{/if}"><a href="{$path}/Translation/Translations">{translate text="Translations"}</a></div>
								{/if}
								{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('libraryManager', $userRoles))}
									<div class="adminMenuLink{if $action == "Libraries"} active{/if}"><a href="{$path}/Admin/Libraries">{translate text="Library Systems"}</a></div>
								{/if}
								{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('libraryManager', $userRoles) || array_key_exists('locationManager', $userRoles))}
									<div class="adminMenuLink{if $action == "Locations"} active{/if}"><a href="{$path}/Admin/Locations">{translate text="Locations"}</a></div>
								{/if}
								{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('libraryManager', $userRoles) || array_key_exists('locationManager', $userRoles))}
									<div class="adminMenuLink{if $action == "BlockPatronAccountLinks"} active{/if}"><a href="{$path}/Admin/BlockPatronAccountLinks">{translate text="Block Patron Account Linking"}</a></div>
								{/if}

								{* OPAC Admin Actions*}
								{if array_key_exists('opacAdmin', $userRoles)}
									<div class="adminMenuLink{if $action == "IPAddresses"} active{/if}"><a href="{$path}/Admin/IPAddresses">{translate text="IP Addresses"}</a></div>
								{/if}

								{* Content Editor Actions *}
								{if array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('contentEditor', $userRoles) || array_key_exists('libraryManager', $userRoles) || array_key_exists('locationManager', $userRoles)}
									<div class="adminMenuLink{if $action == "ListWidgets"} active{/if}"><a href="{$path}/Admin/ListWidgets">{translate text="List Widgets"}</a></div>
								{/if}
								{if array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('contentEditor', $userRoles) || array_key_exists('libraryManager', $userRoles) || array_key_exists('locationManager', $userRoles)}
									<div class="adminMenuLink{if $action == "BrowseCategories"} active{/if}"><a href="{$path}/Admin/BrowseCategories">{translate text="Browse Categories"}</a></div>
								{/if}
								{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('libraryManager', $userRoles) || array_key_exists('contentEditor', $userRoles))}
									<div class="adminMenuLink{if $action == "NYTLists"} active{/if}"><a href="{$path}/Admin/NYTLists">{translate text="NY Times Lists"}</a></div>
								{/if}

								{* OPAC Admin Actions*}
								{if array_key_exists('opacAdmin', $userRoles)}
									{* Sierra/Millennium OPAC Admin Actions*}
									{if ($ils == 'Millennium' || $ils == 'Sierra' || $ils == 'Horizon')}
										<div class="adminMenuLink{if $action == "PTypes"} active{/if}"><a href="{$path}/Admin/PTypes">{translate text="P-Types"}</a></div>
									{/if}
									{if ($ils == 'Millennium' || $ils == 'Sierra')}
										<div class="adminMenuLink{if $action == "LoanRules"} active{/if}"><a href="{$path}/Admin/LoanRules">{translate text="Loan Rules"}</a></div>
										<div class="adminMenuLink{if $action == "LoanRuleDeterminers"} active{/if}"><a href="{$path}/Admin/LoanRuleDeterminers">{translate text="Loan Rule Determiners"}</a></div>
									{/if}
									{* OPAC Admin Actions*}
									<div class="adminMenuLink{if $action == "AccountProfiles"} active{/if}"><a href="{$path}/Admin/AccountProfiles">{translate text="Account Profiles"}</a></div>
								{/if}

							</div>
						</div>
					</div>
				{/if}

				{if (array_key_exists('userAdmin', $userRoles) || array_key_exists('opacAdmin', $userRoles))}
					{if in_array($action, array('Administrators', 'DBMaintenance', 'UsageDashboard', 'SlownessReport', 'ErrorReport', 'PHPInfo', 'OpCacheInfo', 'Variables', 'CronLog'))
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
								{if array_key_exists('userAdmin', $userRoles)}
									<div class="adminMenuLink {if $action == "Administrators"} active{/if}"><a href="{$path}/Admin/Administrators">{translate text="Administrators"}</a></div>
								{/if}
								{if array_key_exists('opacAdmin', $userRoles)}
									<div class="adminMenuLink{if $action == "DBMaintenance"} active{/if}"><a href="{$path}/Admin/DBMaintenance">{translate text="DB Maintenance"}</a></div>
									<div class="adminMenuLink{if $action == "UsageDashboard"} active{/if}"><a href="{$path}/Admin/UsageDashboard">{translate text="Usage Dashboard"}</a></div>
									<div class="adminMenuLink{if $action == "ErrorReport"} active{/if}"><a href="{$path}/Admin/ErrorReport">{translate text="Error Report"}</a></div>
									<div class="adminMenuLink{if $action == "SlownessReport"} active{/if}"><a href="{$path}/Admin/SlownessReport">{translate text="Slowness Report"}</a></div>
									<div class="adminMenuLink{if $action == "SendGridSettings"} active{/if}"><a href="{$path}/Admin/SendGridSettings">{translate text="SendGrid Settings"}</a></div>
									<div class="adminMenuLink{if $module == 'Admin' && $action == "Home"} active{/if}"><a href="{$path}/Admin/Home">{translate text="Solr Information"}</a></div>
									<div class="adminMenuLink{if $action == "PHPInfo"} active{/if}"><a href="{$path}/Admin/PHPInfo">{translate text="PHP Information"}</a></div>
									{*								<div class="adminMenuLink{if $action == "OpCacheInfo"} active{/if}"><a href="{$path}/Admin/OpCacheInfo">OpCache Information</a></div>*}
									<div class="adminMenuLink{if $action == "Variables"} active{/if}"><a href="{$path}/Admin/Variables">{translate text="System Variables"}</a></div>
									<div class="adminMenuLink{if $action == "CronLog"} active{/if}"><a href="{$path}/Admin/CronLog">{translate text="Cron Log"}</a></div>
								{/if}
							</div>
						</div>
					</div>
				{/if}

				{if (array_key_exists('libraryAdmin', $userRoles) || array_key_exists('opacAdmin', $userRoles) || array_key_exists('cataloging', $userRoles))}
					{if in_array($action, array('RecordGroupingLog', 'ReindexLog', 'IndexingStats', 'TranslationMaps'))}
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

								<div class="adminMenuLink{if $action == "IndexingStats"} active{/if}"><a href="{$path}/Admin/IndexingStats">{translate text="Indexing Statistics"}</a></div>
								<div class="adminMenuLink{if $action == "RecordGroupingLog"} active{/if}"><a href="{$path}/Admin/RecordGroupingLog">{translate text="Record Grouping Log"}</a></div>
								<div class="adminMenuLink{if $action == "ReindexLog"} active{/if}"><a href="{$path}/Admin/ReindexLog">{translate text="Grouped Work Index Log"}</a></div>

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
								<div class="adminMenuLink{if $action == "ManageRequests"} active{/if}"><a href="{$path}/MaterialsRequest/ManageRequests">{translate text="Manage Requests"}</a></div>
								<div class="adminMenuLink{if $action == "SummaryReport"} active{/if}"><a href="{$path}/MaterialsRequest/SummaryReport">{translate text="Summary Report"}</a></div>
								<div class="adminMenuLink{if $action == "UserReport"} active{/if}"><a href="{$path}/MaterialsRequest/UserReport">{translate text="Report By User"}</a></div>
								<div class="adminMenuLink{if $action == "ManageStatuses"} active{/if}"><a href="{$path}/Admin/ManageStatuses">{translate text="Manage Statuses"}</a></div>
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
								<div class="adminMenuLink{if $action == "MergedGroupedWorks"} active{/if}"><a href="{$path}/Admin/MergedGroupedWorks">{translate text="Grouped Work Merging"}</a></div>
								<div class="adminMenuLink{if $action == "NonGroupedRecords"} active{/if}"><a href="{$path}/Admin/NonGroupedRecords">{translate text="Records To Not Group"}</a></div>
								<div class="adminMenuLink{if $action == "AuthorEnrichment"} active{/if}"><a href="{$path}/Admin/AuthorEnrichment">{translate text="Author Enrichment"}</a></div>
								{if array_key_exists('opacAdmin', $userRoles)}
									<div class="adminMenuLink{if $action == "ARSettings"} active{/if}"><a href="{$path}/RenaissanceLearning/ARSettings">{translate text="Accelerated Reading Settings"}</a></div>
								{/if}
							</div>
						</div>
					</div>
				{/if}

				{if (array_key_exists('libraryAdmin', $userRoles) || array_key_exists('opacAdmin', $userRoles) || array_key_exists('cataloging', $userRoles))}
					{if $module == 'ILS' && in_array($action, array('IndexingLog', 'TranslationMaps', 'IndexingProfiles', 'Dashboard'))}
						{assign var="curSection" value=true}
					{else}
						{assign var="curSection" value=false}
					{/if}
					<div class="panel{if $curSection} active{/if}">
						<a href="#ilsMenu" data-toggle="collapse" data-parent="#adminMenuAccordion">
							<div class="panel-heading">
								<div class="panel-title">
									{translate text="ILS &amp; Side Loads"}
								</div>
							</div>
						</a>
						<div id="ilsMenu" class="panel-collapse collapse {if $curSection}in{/if}">
							<div class="panel-body">
								<div class="adminMenuLink{if $action == "IndexingProfiles"} active{/if}"><a href="{$path}/ILS/IndexingProfiles">{translate text="Indexing Profiles"}</a></div>
								<div class="adminMenuLink{if $action == "TranslationMaps"} active{/if}"><a href="{$path}/ILS/TranslationMaps">{translate text="Translation Maps"}</a></div>
								<div class="adminMenuLink{if $action == "IndexingLog"} active{/if}"><a href="{$path}/ILS/IndexingLog">{translate text="Indexing Log"}</a></div>
								{if ($ils == 'Millennium' || $ils == 'Sierra')}
									<div class="adminMenuLink{if $action == "SierraExportLog"} active{/if}"><a href="{$path}/Admin/SierraExportLog">{translate text="Sierra Export Log"}</a></div>
								{/if}
								<div class="adminMenuLink{if $action == "Dashboard"} active{/if}"><a href="{$path}/ILS/Dashboard">{translate text="Dashboard"}</a></div>
							</div>
						</div>
					</div>
				{/if}

				{if (array_key_exists('libraryAdmin', $userRoles) || array_key_exists('opacAdmin', $userRoles) || array_key_exists('cataloging', $userRoles))}
					{if $module == 'OverDrive' && in_array($action, array('APIData', 'ExtractLog', 'Settings', 'Dashboard'))}
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
								<div class="adminMenuLink{if $action == "Settings"} active{/if}"><a href="{$path}/OverDrive/Settings">{translate text="Settings"}</a></div>
								<div class="adminMenuLink{if $action == "IndexingLog"} active{/if}"><a href="{$path}/OverDrive/IndexingLog">{translate text="Indexing Log"}</a></div>
								<div class="adminMenuLink{if $action == "Dashboard"} active{/if}"><a href="{$path}/OverDrive/Dashboard">{translate text="Dashboard"}</a></div>
								<div class="adminMenuLink{if $action == "APIData"} active{/if}"><a href="{$path}/OverDrive/APIData">{translate text="API Information"}</a></div>
							</div>
						</div>
					</div>
				{/if}

				{if (array_key_exists('libraryAdmin', $userRoles) || array_key_exists('opacAdmin', $userRoles) || array_key_exists('cataloging', $userRoles))}
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
								<div class="adminMenuLink{if $action == "Settings"} active{/if}"><a href="{$path}/Hoopla/Settings">{translate text="Settings"}</a></div>
								<div class="adminMenuLink{if $action == "Scopes"} active{/if}"><a href="{$path}/Hoopla/Scopes">{translate text="Scopes"}</a></div>
								<div class="adminMenuLink{if $action == "IndexingLog"} active{/if}"><a href="{$path}/Hoopla/IndexingLog">{translate text="Indexing Log"}</a></div>
								<div class="adminMenuLink{if $action == "Dashboard"} active{/if}"><a href="{$path}/Hoopla/Dashboard">{translate text="Dashboard"}</a></div>
							</div>
						</div>
					</div>
				{/if}

				{if (array_key_exists('libraryAdmin', $userRoles) || array_key_exists('opacAdmin', $userRoles) || array_key_exists('cataloging', $userRoles))}
					{if $module == 'Rbdigital' && in_array($action, array('Settings', 'IndexingLog', 'Dashboard'))}
						{assign var="curSection" value=true}
					{else}
						{assign var="curSection" value=false}
					{/if}
					<div class="panel{if $curSection} active{/if}">
						<a href="#rbdigitalMenu" data-toggle="collapse" data-parent="#adminMenuAccordion">
							<div class="panel-heading">
								<div class="panel-title">
									{translate text="Rbdigital"}
								</div>
							</div>
						</a>
						<div id="rbdigitalMenu" class="panel-collapse collapse {if $curSection}in{/if}">
							<div class="panel-body">
								<div class="adminMenuLink{if $action == "Settings"} active{/if}"><a href="{$path}/Rbdigital/Settings">{translate text="Settings"}</a></div>
								<div class="adminMenuLink{if $action == "IndexingLog"} active{/if}"><a href="{$path}/Rbdigital/IndexingLog">{translate text="Indexing Log"}</a></div>
								<div class="adminMenuLink{if $action == "Dashboard"} active{/if}"><a href="{$path}/Rbdigital/Dashboard">{translate text="Dashboard"}</a></div>
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
								<div class="adminMenuLink{if $action == "ArchiveRequests"} active{/if}"><a href="{$path}/Admin/ArchiveRequests">{translate text="Archive Material Requests"}</a></div>
								<div class="adminMenuLink{if $action == "AuthorshipClaims"} active{/if}"><a href="{$path}/Admin/AuthorshipClaims">{translate text="Archive Authorship Claims"}</a></div>
								<div class="adminMenuLink{if $action == "ArchiveUsage"} active{/if}"><a href="{$path}/Admin/ArchiveUsage">{translate text="Archive Usage"}</a></div>
								<div class="adminMenuLink{if $action == "ArchiveSubjects"} active{/if}"><a href="{$path}/Admin/ArchiveSubjects">{translate text="Archive Subject Control"}</a></div>
								{if array_key_exists('opacAdmin', $userRoles)}
									<div class="adminMenuLink{if $action == "ArchivePrivateCollections"} active{/if}"><a href="{$path}/Admin/ArchivePrivateCollections">{translate text="Archive Private Collections"}</a></div>
									<div class="adminMenuLink{if $action == "ClearArchiveCache"} active{/if}"><a href="{$path}/Admin/ClearArchiveCache">{translate text="Clear Cache"}</a></div>
								{/if}
							</div>
						</div>
					</div>
				{/if}

				{if (array_key_exists('archives', $userRoles) || array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles))}
					{if $module == 'OpenArchives' && in_array($action, array('Collections', 'Dashboard'))}
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
								<div class="adminMenuLink{if $action == "Collections"} active{/if}"><a href="{$path}/OpenArchives/Collections">{translate text="Collections"}</a></div>
								<div class="adminMenuLink{if $action == "Dashboard"} active{/if}"><a href="{$path}/OpenArchives/Dashboard">{translate text="Dashboard"}</a></div>
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
								<div class="adminMenuLink{if $action == "OfflineHoldsReport" && $module == "Circa"} active{/if}"><a href="{$path}/Circa/OfflineHoldsReport">{translate text="Offline Holds Report"}</a></div>
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
	{else}
		<script type="text/javascript">
            AspenDiscovery.Account.loadListData();
            AspenDiscovery.Account.loadRatingsData();
		</script>
	{/if}
{/strip}
