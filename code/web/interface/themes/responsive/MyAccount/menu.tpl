{strip}
{if $loggedIn}
	{* Setup the accoridon *}
	<div id="home-account-links" class="sidebar-links row"{if $displaySidebarMenu} style="display: none"{/if}>
		<div class="panel-group accordion" id="account-link-accordion">
			{* My Account *}
			<a id="account-menu"></a>
			{if $module == 'MyAccount' || $module == 'MyResearch' || ($module == 'Search' && $action == 'Home') || ($module == 'MaterialsRequest' && $action == 'MyRequests')}
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

						<div class="myAccountLink{if $action=="CheckedOut"} active{/if}">
							<a href="{$path}/MyAccount/CheckedOut" id="checkedOut">
								Checked Out Titles {if !$offline}<span class="checkouts-placeholder"><img src="{$path}/images/loading.gif" alt="loading"></span>{/if}
							</a>
						</div>
						<div class="myAccountLink{if $action=="Holds"} active{/if}">
							<a href="{$path}/MyAccount/Holds" id="holds">
								Titles On Hold {if !$offline}<span class="holds-placeholder"><img src="{$path}/images/loading.gif" alt="loading"></span>{/if}
							</a>
						</div>

						{if $enableMaterialsBooking}
						<div class="myAccountLink{if $action=="Bookings"} active{/if}">
							<a href="{$path}/MyAccount/Bookings" id="bookings">
								Scheduled Items  {if !$offline}<span class="bookings-placeholder"><img src="{$path}/images/loading.gif" alt="loading"></span>{/if}
							</a>
						</div>
						{/if}
						<div class="myAccountLink{if $action=="ReadingHistory"} active{/if}">
							<a href="{$path}/MyAccount/ReadingHistory">
								Reading History {if !$offline}<span class="readingHistory-placeholder"><img src="{$path}/images/loading.gif" alt="loading"></span>{/if}
							</a>
						</div>

						{if $showFines}
							<div class="myAccountLink{if $action=="Fines"} active{/if}" title="Fines and account messages"><a href="{$path}/MyAccount/Fines">{translate text='Fines and Messages'}</a></div>
						{/if}
						{if $enableMaterialsRequest}
							<div class="myAccountLink{if $pageTemplate=="myMaterialRequests.tpl"} active{/if}" title="{translate text='Materials_Request_alt'}s">
								<a href="{$path}/MaterialsRequest/MyRequests">{translate text='Materials_Request_alt'}s <span class="materialsRequests-placeholder"><img src="{$path}/images/loading.gif" alt="loading"></span></a>
							</div>
						{/if}
						{if $showRatings}
							<hr class="menu">
							<div class="myAccountLink{if $action=="MyRatings"} active{/if}"><a href="{$path}/MyAccount/MyRatings">{translate text='Titles You Rated'}</a></div>
							{if $user->disableRecommendations == 0}
								<div class="myAccountLink{if $action=="SuggestedTitles"} active{/if}"><a href="{$path}/MyAccount/SuggestedTitles">{translate text='Recommended For You'}</a></div>
							{/if}
						{/if}
						<hr class="menu">
						<div class="myAccountLink{if $pageTemplate=="profile.tpl"} active{/if}"><a href="{$path}/MyAccount/Profile">Account Settings</a></div>
						{* Only highlight saved searches as active if user is logged in: *}
						<div class="myAccountLink{if $user && $pageTemplate=="history.tpl"} active{/if}"><a href="{$path}/Search/History?require_login">{translate text='history_saved_searches'}</a></div>
						{if $allowMasqueradeMode && !$masqueradeMode}
							{if $canMasquerade}
								<hr class="menu">
								<div class="myAccountLink"><a onclick="VuFind.Account.getMasqueradeForm();" href="#">Masquerade</a></div>
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
								My Lists
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

							<a href="#" onclick="return VuFind.Account.showCreateListForm();" class="btn btn-sm btn-primary">Create a New List</a>
						</div>
					</div>
				</div>
			{*{/if}*}

			<span id="tagsMenu-placeholder"></span>

			{* Admin Functionality if Available *}
			{if $loggedIn && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('contentEditor', $userRoles) || array_key_exists('libraryManager', $userRoles) || array_key_exists('locationManager', $userRoles))}
				{if in_array($action, array('Libraries', 'Locations', 'IPAddresses', 'ListWidgets', 'BrowseCategories', 'PTypes', 'LoanRules', 'LoanRuleDeterminers', 'AccountProfiles', 'NYTLists', 'BlockPatronAccountLinks'))}
					{assign var="curSection" value=true}
				{else}
					{assign var="curSection" value=false}
				{/if}
				<div class="panel{if $curSection} active{/if}">
					<a href="#vufindMenuGroup" data-toggle="collapse" data-parent="#adminMenuAccordion">
						<div class="panel-heading">
							<div class="panel-title">
								Pika Configuration
							</div>
						</div>
					</a>
					<div id="vufindMenuGroup" class="panel-collapse collapse {if $curSection}in{/if}">
						<div class="panel-body">
							{* Library Admin Actions *}
							{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('libraryManager', $userRoles))}
								<div class="adminMenuLink{if $action == "Libraries"} active{/if}"><a href="{$path}/Admin/Libraries">Library Systems</a></div>
							{/if}
							{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('libraryManager', $userRoles) || array_key_exists('locationManager', $userRoles))}
								<div class="adminMenuLink{if $action == "Locations"} active{/if}"><a href="{$path}/Admin/Locations">Locations</a></div>
							{/if}
							{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('libraryManager', $userRoles) || array_key_exists('locationManager', $userRoles))}
								<div class="adminMenuLink{if $action == "BlockPatronAccountLinks"} active{/if}"><a href="{$path}/Admin/BlockPatronAccountLinks">Block Patron Account Linking</a></div>
							{/if}

							{* OPAC Admin Actions*}
							{if array_key_exists('opacAdmin', $userRoles)}
								<div class="adminMenuLink{if $action == "IPAddresses"} active{/if}"><a href="{$path}/Admin/IPAddresses">IP Addresses</a></div>
							{/if}

							{* Content Editor Actions *}
							<div class="adminMenuLink{if $action == "ListWidgets"} active{/if}"><a href="{$path}/Admin/ListWidgets">List Widgets</a></div>
							<div class="adminMenuLink{if $action == "BrowseCategories"} active{/if}"><a href="{$path}/Admin/BrowseCategories">Browse Categories</a></div>
							{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('libraryManager', $userRoles) || array_key_exists('contentEditor', $userRoles))}
								<div class="adminMenuLink{if $action == "NYTLists"} active{/if}"><a href="{$path}/Admin/NYTLists">NY Times Lists</a></div>
							{/if}

							{* OPAC Admin Actions*}
							{if array_key_exists('opacAdmin', $userRoles)}
								{* Sierra/Millennium OPAC Admin Actions*}
								{if ($ils == 'Millennium' || $ils == 'Sierra' || $ils == 'Horizon')}
								<div class="adminMenuLink{if $action == "PTypes"} active{/if}"><a href="{$path}/Admin/PTypes">P-Types</a></div>
								{/if}
								{if ($ils == 'Millennium' || $ils == 'Sierra')}
								<div class="adminMenuLink{if $action == "LoanRules"} active{/if}"><a href="{$path}/Admin/LoanRules">Loan Rules</a></div>
								<div class="adminMenuLink{if $action == "LoanRuleDeterminers"} active{/if}"><a href="{$path}/Admin/LoanRuleDeterminers">Loan Rule Determiners</a></div>
								{/if}
								{* OPAC Admin Actions*}
								<div class="adminMenuLink{if $action == "AccountProfiles"} active{/if}"><a href="{$path}/Admin/AccountProfiles">Account Profiles</a></div>
							{/if}

						</div>
					</div>
				</div>
			{/if}

			{if $loggedIn && (array_key_exists('userAdmin', $userRoles) || array_key_exists('opacAdmin', $userRoles))}
				{if in_array($action, array('Administrators', 'DBMaintenance', 'DBMaintenanceEContent', 'PHPInfo', 'OpCacheInfo', 'Variables', 'CronLog', 'MemCacheInfo'))
				|| ($module == 'Admin' && $action == 'Home')}
					{assign var="curSection" value=true}
				{else}
					{assign var="curSection" value=false}
				{/if}
				<div class="panel{if $curSection} active{/if}">
					<a href="#adminMenuGroup" data-toggle="collapse" data-parent="#adminMenuAccordion">
						<div class="panel-heading">
							<div class="panel-title">
								System Administration
							</div>
						</div>
					</a>
					<div id="adminMenuGroup" class="panel-collapse collapse {if $curSection}in{/if}">
						<div class="panel-body">
							{if array_key_exists('userAdmin', $userRoles)}
								<div class="adminMenuLink {if $action == "Administrators"} active{/if}"><a href="{$path}/Admin/Administrators">Administrators</a></div>
							{/if}
							{if array_key_exists('opacAdmin', $userRoles)}
								<div class="adminMenuLink{if $action == "DBMaintenance"} active{/if}"><a href="{$path}/Admin/DBMaintenance">DB Maintenance - Pika</a></div>
								<div class="adminMenuLink{if $action == "DBMaintenanceEContent"} active{/if}"><a href="{$path}/Admin/DBMaintenanceEContent">DB Maintenance - EContent</a></div>
								<div class="adminMenuLink{if $module == 'Admin' && $action == "Home"} active{/if}"><a href="{$path}/Admin/Home">Solr Information</a></div>
								<div class="adminMenuLink{if $action == "PHPInfo"} active{/if}"><a href="{$path}/Admin/PHPInfo">PHP Information</a></div>
								<div class="adminMenuLink{if $action == "MemCacheInfo"} active{/if}"><a href="{$path}/Admin/MemCacheInfo">MemCache Information</a></div>
								<div class="adminMenuLink{if $action == "OpCacheInfo"} active{/if}"><a href="{$path}/Admin/OpCacheInfo">OpCache Information</a></div>
								<div class="adminMenuLink{if $action == "Variables"} active{/if}"><a href="{$path}/Admin/Variables">System Variables</a></div>
								<div class="adminMenuLink{if $action == "CronLog"} active{/if}"><a href="{$path}/Admin/CronLog">Cron Log</a></div>
							{/if}
						</div>
					</div>
				</div>
			{/if}

			{if $loggedIn && (array_key_exists('libraryAdmin', $userRoles) || array_key_exists('opacAdmin', $userRoles) || array_key_exists('cataloging', $userRoles))}
				{if in_array($action, array('RecordGroupingLog', 'ReindexLog', 'OverDriveExtractLog', 'HooplaExportLog', 'SierraExportLog', 'IndexingStats', 'IndexingProfiles', 'TranslationMaps'))}
					{assign var="curSection" value=true}
				{else}
					{assign var="curSection" value=false}
				{/if}
				<div class="panel{if $curSection} active{/if}">
					<a href="#indexingMenuGroup" data-toggle="collapse" data-parent="#adminMenuAccordion">
						<div class="panel-heading">
							<div class="panel-title">
								Indexing Information
							</div>
						</div>
					</a>
					<div id="indexingMenuGroup" class="panel-collapse collapse {if $curSection}in{/if}">
						<div class="panel-body">
							<div class="adminMenuLink{if $action == "IndexingStats"} active{/if}"><a href="{$path}/Admin/IndexingStats">Indexing Statistics</a></div>
							<div class="adminMenuLink{if $action == "RecordGroupingLog"} active{/if}"><a href="{$path}/Admin/RecordGroupingLog">Record Grouping Log</a></div>
							<div class="adminMenuLink{if $action == "ReindexLog"} active{/if}"><a href="{$path}/Admin/ReindexLog">Reindex Log</a></div>
							<div class="adminMenuLink{if $action == "OverDriveExtractLog"} active{/if}"><a href="{$path}/Admin/OverDriveExtractLog">OverDrive Extract Log</a></div>
							{if ($ils == 'Millennium' || $ils == 'Sierra')}
								<div class="adminMenuLink{if $action == "SierraExportLog"} active{/if}"><a href="{$path}/Admin/SierraExportLog">Sierra Export Log</a></div>
							{/if}
							<div class="adminMenuLink{if $action == "HooplaExportLog"} active{/if}"><a href="{$path}/Admin/HooplaExportLog">Hoopla Export Log</a></div>
							<div class="adminMenuLink{if $action == "IndexingProfiles"} active{/if}"><a href="{$path}/Admin/IndexingProfiles">Indexing Profiles</a></div>
							<div class="adminMenuLink{if $action == "TranslationMaps"} active{/if}"><a href="{$path}/Admin/TranslationMaps">Translation Maps</a></div>
						</div>
					</div>
				</div>
			{/if}

			{if $loggedIn && $enableMaterialsRequest && array_key_exists('library_material_requests', $userRoles)}
				{if in_array($action, array('ManageRequests', 'SummaryReport', 'UserReport', 'ManageStatuses'))}
					{assign var="curSection" value=true}
				{else}
					{assign var="curSection" value=false}
				{/if}
				<div class="panel{if $curSection} active{/if}">
					<a href="#materialsRequestMenu" data-toggle="collapse" data-parent="#adminMenuAccordion">
						<div class="panel-heading">
							<div class="panel-title">
								Materials Requests
							</div>
						</div>
					</a>
					<div id="materialsRequestMenu" class="panel-collapse collapse {if $curSection}in{/if}">
						<div class="panel-body">
							<div class="adminMenuLink{if $action == "ManageRequests"} active{/if}"><a href="{$path}/MaterialsRequest/ManageRequests">Manage Requests</a></div>
							<div class="adminMenuLink{if $action == "SummaryReport"} active{/if}"><a href="{$path}/MaterialsRequest/SummaryReport">Summary Report</a></div>
							<div class="adminMenuLink{if $action == "UserReport"} active{/if}"><a href="{$path}/MaterialsRequest/UserReport">Report By User</a></div>
							<div class="adminMenuLink{if $action == "ManageStatuses"} active{/if}"><a href="{$path}/Admin/ManageStatuses">Manage Statuses</a></div>
							<div class="adminMenuLink"><a href="https://docs.google.com/document/d/1s9qOhlHLfQi66qMMt5m-dJ0kGNyHiOjSrqYUbe0hEcA">Documentation</a></div>
						</div>
					</div>
				</div>
			{/if}

			{if $loggedIn && (array_key_exists('cataloging', $userRoles) || array_key_exists('opacAdmin', $userRoles))}
				{if in_array($action, array('MergedGroupedWorks', 'NonGroupedRecords', 'AuthorEnrichment', 'OverDriveAPIData'))}
					{assign var="curSection" value=true}
				{else}
					{assign var="curSection" value=false}
				{/if}
				<div class="panel{if $curSection} active{/if}">
					<a href="#catalogingRequestMenu" data-toggle="collapse" data-parent="#adminMenuAccordion">
						<div class="panel-heading">
							<div class="panel-title">
								Cataloging
							</div>
						</div>
					</a>
					<div id="catalogingRequestMenu" class="panel-collapse collapse {if $curSection}in{/if}">
						<div class="panel-body">
							<div class="adminMenuLink{if $action == "MergedGroupedWorks"} active{/if}"><a href="{$path}/Admin/MergedGroupedWorks">Grouped Work Merging</a></div>
							<div class="adminMenuLink{if $action == "NonGroupedRecords"} active{/if}"><a href="{$path}/Admin/NonGroupedRecords">Records To Not Merge</a></div>
							<div class="adminMenuLink{if $action == "OverDriveAPIData"} active{/if}"><a href="{$path}/Admin/OverDriveAPIData">OverDrive API Data</a></div>
							<div class="adminMenuLink{if $action == "AuthorEnrichment"} active{/if}"><a href="{$path}/Admin/AuthorEnrichment">Author Enrichment</a></div>

						</div>
					</div>
				</div>
			{/if}

			{if $loggedIn && (array_key_exists('archives', $userRoles) || array_key_exists('opacAdmin', $userRoles))}
				{if in_array($action, array('ArchiveSubjects', 'ArchivePrivateCollections', 'ArchiveRequests', 'AuthorshipClaims', 'ClearArchiveCache', 'ArchiveUsage'))}
					{assign var="curSection" value=true}
				{else}
					{assign var="curSection" value=false}
				{/if}
				<div class="panel{if $curSection} active{/if}">
					<a href="#archivesMenu" data-toggle="collapse" data-parent="#adminMenuAccordion">
						<div class="panel-heading">
							<div class="panel-title">
								Archives
							</div>
						</div>
					</a>
					<div id="archivesMenu" class="panel-collapse collapse {if $curSection}in{/if}">
						<div class="panel-body">
							<div class="adminMenuLink{if $action == "ArchiveRequests"} active{/if}"><a href="{$path}/Admin/ArchiveRequests">Archive Material Requests</a></div>
							<div class="adminMenuLink{if $action == "AuthorshipClaims"} active{/if}"><a href="{$path}/Admin/AuthorshipClaims">Archive Authorship Claims</a></div>
							<div class="adminMenuLink{if $action == "ArchiveUsage"} active{/if}"><a href="{$path}/Admin/ArchiveUsage">Archive Usage</a></div>
							<div class="adminMenuLink{if $action == "ArchiveSubjects"} active{/if}"><a href="{$path}/Admin/ArchiveSubjects">Archive Subject Control</a></div>
							{if array_key_exists('opacAdmin', $userRoles)}
								<div class="adminMenuLink{if $action == "ArchivePrivateCollections"} active{/if}"><a href="{$path}/Admin/ArchivePrivateCollections">Archive Private Collections</a></div>
								<div class="adminMenuLink{if $action == "ClearArchiveCache"} active{/if}"><a href="{$path}/Admin/ClearArchiveCache">Clear Cache</a></div>
							{/if}
						</div>
					</div>
				</div>
			{/if}

			{if $loggedIn && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('circulationReports', $userRoles))}
				{if $module == 'Circa'}
					{assign var="curSection" value=true}
				{else}
					{assign var="curSection" value=false}
				{/if}
				<div class="panel{if $curSection} active{/if}">
					<a href="#circulationMenu" data-toggle="collapse" data-parent="#adminMenuAccordion">
						<div class="panel-heading">
							<div class="panel-title">
								Circulation
							</div>
						</div>
					</a>
					<div id="circulationMenu" class="panel-collapse collapse {if $curSection}in{/if}">
						<div class="panel-body">
							{if array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles)}
								<div class="adminMenuLink{if $action == "Home" && $module == "Circa"} active{/if}"><a href="{$path}/Circa/Home">Inventory</a></div>
							{/if}
							<div class="adminMenuLink{if $action == "OfflineCirculation" && $module == "Circa"} active{/if}"><a href="{$path}/Circa/OfflineCirculation">Offline Circulation</a></div>
							<div class="adminMenuLink{if $action == "OfflineHoldsReport" && $module == "Circa"} active{/if}"><a href="{$path}/Circa/OfflineHoldsReport">Offline Holds Report</a></div>
							<div class="adminMenuLink{if $action == "OfflineCirculationReport" && $module == "Circa"} active{/if}"><a href="{$path}/Circa/OfflineCirculationReport">Offline Circulation Report</a></div>
						</div>
					</div>
				</div>
			{/if}

			{if $loggedIn && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('contentEditor', $userRoles))}
				{if $module == "EditorialReview"}
					{assign var="curSection" value=true}
				{else}
					{assign var="curSection" value=false}
				{/if}
				<div class="panel{if $curSection} active{/if}">
					<a href="#editorialReviewMenu" data-toggle="collapse" data-parent="#adminMenuAccordion">
						<div class="panel-heading">
							<div class="panel-title">
								Editorial Reviews
							</div>
						</div>
					</a>
					<div id="editorialReviewMenu" class="panel-collapse collapse {if $curSection}in{/if}">
						<div class="panel-body">
							<div class="adminMenuLink{if $action == "Edit" && $module == "EditorialReview"} active{/if}"><a href="{$path}/EditorialReview/Edit">New Review</a></div>
							<div class="adminMenuLink{if $action == "Search" && $module == "EditorialReview"} active{/if}"><a href="{$path}/EditorialReview/Search">Search Existing Reviews</a></div>
						</div>
					</div>
				</div>
			{/if}

			{if $loggedIn && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('locationReports', $userRoles) || array_key_exists('contentEditor', $userRoles))}
				{if in_array($action, array('StudentReport'))}
					{assign var="curSection" value=true}
				{else}
					{assign var="curSection" value=false}
				{/if}
				<div class="panel{if $curSection} active{/if}">
					<a href="#reportsMenu" data-toggle="collapse" data-parent="#adminMenuAccordion">
						<div class="panel-heading">
							<div class="panel-title">
								Reports
							</div>
						</div>
					</a>
					<div id="reportsMenu" class="panel-collapse collapse {if $curSection}in{/if}">
						<div class="panel-body">
							{if ($ils == 'CarlX' || $ils == 'Sierra') && $loggedIn && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('locationReports', $userRoles))}
								<div class="adminMenuLink{if $action == "StudentReport"} active{/if}"><a href="{$path}/Report/StudentReport">Student Reports</a></div>
							{/if}
						</div>
					</div>
				</div>
			{/if}
		</div>

		{include file="library-links.tpl" libraryLinks=$libraryAccountLinks linksId='home-library-account-links' section='Account'}
	</div>
{/if}
<script type="text/javascript">
	VuFind.Account.loadMenuData();
</script>
{/strip}
