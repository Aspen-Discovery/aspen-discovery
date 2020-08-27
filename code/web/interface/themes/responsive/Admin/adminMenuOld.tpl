{strip}
	{if $loggedIn}
		<div id="home-account-links" class="sidebar-links row">
			<div class="panel-group accordion" id="account-link-accordion">
				{if (array_key_exists('userAdmin', $userRoles) || array_key_exists('opacAdmin', $userRoles))}
					{if in_array($action, array('Modules', 'Administrators', 'DBMaintenance', 'PHPInfo', 'Variables', 'SystemVariables'))
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
								{/if}
								{if array_key_exists('userAdmin', $userRoles)}
									<div class="adminMenuLink "><a href="/Admin/Administrators">{translate text="Administrators"}</a></div>
								{/if}
								{if array_key_exists('opacAdmin', $userRoles)}
									<div class="adminMenuLink"><a href="/Admin/DBMaintenance">{translate text="DB Maintenance"}</a></div>
									<div class="adminMenuLink"><a href="/Admin/SendGridSettings">{translate text="SendGrid Settings"}</a></div>
									<div class="adminMenuLink"><a href="/Admin/PHPInfo">{translate text="PHP Information"}</a></div>
									<div class="adminMenuLink"><a href="/Admin/Variables">{translate text="Variables"}</a></div>
									<div class="adminMenuLink"><a href="/Admin/SystemVariables">{translate text="System Variables"}</a></div>
								{/if}
							</div>
						</div>
					</div>
				{/if}

				{if array_key_exists('opacAdmin', $userRoles) || array_key_exists('cataloging', $userRoles) || array_key_exists('superCataloger', $userRoles)}
					{if in_array($action, array('UsageDashboard', 'PerformanceReport', 'ErrorReport', 'CronLog'))}
						{assign var="curSection" value=true}
					{else}
						{assign var="curSection" value=false}
					{/if}
					<div class="panel{if $curSection} active{/if}">
						<a href="#reportsMenuGroup" data-toggle="collapse" data-parent="#adminMenuAccordion">
							<div class="panel-heading">
								<div class="panel-title">
									{translate text="System Reports"}
								</div>
							</div>
						</a>
						<div id="reportsMenuGroup" class="panel-collapse collapse {if $curSection}in{/if}">
							<div class="panel-body">
								<div class="adminMenuLink"><a href="/Admin/SiteStatus">{translate text="Site Status"}</a></div>
                                {if array_key_exists('opacAdmin', $userRoles)}
									<div class="adminMenuLink"><a href="/Admin/UsageDashboard">{translate text="Usage Dashboard"}</a></div>
								{/if}
								{if array_key_exists('opacAdmin', $userRoles) || array_key_exists('cataloging', $userRoles) || array_key_exists('superCataloger', $userRoles)}
									<div class="adminMenuLink"><a href="/Admin/ReindexLog">{translate text="Nightly Index Log"}</a></div>
								{/if}
								{if array_key_exists('opacAdmin', $userRoles)}
									<div class="adminMenuLink"><a href="/Admin/CronLog">{translate text="Cron Log"}</a></div>
									<div class="adminMenuLink"><a href="/Admin/ErrorReport">{translate text="Error Report"}</a></div>
									<div class="adminMenuLink"><a href="/Admin/PerformanceReport">{translate text="Performance Report"}</a></div>
								{/if}
							</div>
						</div>
					</div>
				{/if}

				{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles))}
					{if in_array($action, array('Themes', 'GroupedWorkDisplay', 'LayoutSettings', 'GroupedWorkFacets', 'BrowseCategoryGroups', 'BrowseCategories'))}
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
								{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles))}
									<div class="adminMenuLink"><a href="/Admin/BrowseCategoryGroups">{translate text="Browse Category Groups"}</a></div>
								{/if}
								{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles))}
									<div class="adminMenuLink">&nbsp;&raquo;&nbsp;<a href="/Admin/BrowseCategories">{translate text="Browse Categories"}</a></div>
								{/if}
							</div>
						</div>
					</div>
				{/if}

				{* Admin Functionality if Available *}
				{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('libraryManager', $userRoles) || array_key_exists('locationManager', $userRoles) || array_key_exists('translator', $userRoles))}
					{if in_array($action, array('Libraries', 'Locations', 'IPAddresses', 'PTypes', 'AccountProfiles', 'BlockPatronAccountLinks', 'Languages'))}
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

				{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('contentEditor', $userRoles) || array_key_exists('libraryManager', $userRoles) || array_key_exists('locationManager', $userRoles) || array_key_exists('catalogging', $userRoles) || array_key_exists('superCataloger', $userRoles))}
					{if in_array($action, array('Placards', 'NYTLists', 'CollectionSpotlights', 'BrowseCategories', 'NovelistSettings', 'AuthorEnrichment', 'ARSettings', 'CoceServerSettings', 'ContentCafeSettings', 'GoogleApiSettings', 'SyndeticsSettings', 'DPLASettings', 'OMDBSettings', 'NewYorkTimesSettings', 'RecaptchaSettings', 'RosenLevelUPSettings'))}
						{assign var="curSection" value=true}
					{else}
						{assign var="curSection" value=false}
					{/if}
					<div class="panel{if $curSection} active{/if}">
						<a href="#enrichmentMenuGroup" data-toggle="collapse" data-parent="#adminMenuAccordion">
							<div class="panel-heading">
								<div class="panel-title">
									{translate text="Enrichment"}
								</div>
							</div>
						</a>
						<div id="enrichmentMenuGroup" class="panel-collapse collapse {if $curSection}in{/if}">
							<div class="panel-body">
								{* Content Editor Actions *}
								{if array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('contentEditor', $userRoles) || array_key_exists('libraryManager', $userRoles) || array_key_exists('locationManager', $userRoles)}
									<div class="adminMenuLink"><a href="/Admin/BrowseCategories">{translate text="Browse Categories"}</a></div>
								{/if}
								{if array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('contentEditor', $userRoles) || array_key_exists('libraryManager', $userRoles) || array_key_exists('locationManager', $userRoles)}
									<div class="adminMenuLink"><a href="/Admin/CollectionSpotlights">{translate text="Collection Spotlights"}</a></div>
								{/if}
								{if array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('contentEditor', $userRoles) || array_key_exists('libraryManager', $userRoles) || array_key_exists('locationManager', $userRoles)}
									<div class="adminMenuLink"><a href="/Admin/Placards">{translate text="Placards"}</a></div>
								{/if}
								<hr class="menu"/>
								{if array_key_exists('opacAdmin', $userRoles) || array_key_exists('cataloging', $userRoles) || array_key_exists('superCataloger', $userRoles)}
 								    <div class="adminMenuLink"><a href="/Admin/AuthorEnrichment">{translate text="Author Enrichment"}</a></div>
								{/if}
 								{if array_key_exists('opacAdmin', $userRoles)}
									<div class="adminMenuLink"><a href="/RenaissanceLearning/ARSettings">{translate text="Accelerated Reader Settings"}</a></div>
								{/if}
								{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles))}
									<div class="adminMenuLink"><a href="/Enrichment/CoceServerSettings">{translate text="Coce Server Settings"}</a></div>
								{/if}
								{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles))}
									<div class="adminMenuLink"><a href="/Enrichment/ContentCafeSettings">{translate text="ContentCafe Settings"}</a></div>
								{/if}
								{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles))}
									<div class="adminMenuLink"><a href="/Enrichment/DPLASettings">{translate text="DP.LA Settings"}</a></div>
								{/if}
								{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles))}
									<div class="adminMenuLink"><a href="/Enrichment/GoogleApiSettings">{translate text="Google Api Settings"}</a></div>
								{/if}
								{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles))}
									<div class="adminMenuLink"><a href="/Enrichment/NewYorkTimesSettings">{translate text="NY Times Settings"}</a></div>
								{/if}
								{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('libraryManager', $userRoles) || array_key_exists('contentEditor', $userRoles))}
									<div class="adminMenuLink">{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles))}&nbsp;&raquo;&nbsp;{/if}<a href="/Admin/NYTLists">{translate text="NY Times Lists"}</a></div>
								{/if}
								{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles))}
									<div class="adminMenuLink"><a href="/Enrichment/NovelistSettings">{translate text="Novelist Settings"}</a></div>
								{/if}
								{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles))}
									<div class="adminMenuLink"><a href="/Enrichment/OMDBSettings">{translate text="OMDB Settings"}</a></div>
								{/if}
								{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles))}
									<div class="adminMenuLink"><a href="/Enrichment/RecaptchaSettings">{translate text="reCAPTCHA Settings"}</a></div>
								{/if}
								{if (array_key_exists('opacAdmin', $userRoles))}
									<div class="adminMenuLink"><a href="/Rosen/RosenLevelUPSettings">{translate text="Rosen LevelUP Settings"}</a></div>
								{/if}
								{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles))}
									<div class="adminMenuLink"><a href="/Enrichment/SyndeticsSettings">{translate text="Syndetics Settings"}</a></div>
								{/if}
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
								<div class="adminMenuLink"><a href="/MaterialsRequest/ManageStatuses">{translate text="Manage Statuses"}</a></div>
							</div>
						</div>
					</div>
				{/if}

				{if (array_key_exists('cataloging', $userRoles) || array_key_exists('opacAdmin', $userRoles) || array_key_exists('superCataloger', $userRoles))}
					{if in_array($action, array('NonGroupedRecords'))}
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
								<div class="adminMenuLink"><a href="/Admin/NonGroupedRecords">{translate text="Records To Not Group"}</a></div>
							</div>
						</div>
					</div>
				{/if}

				{if (array_key_exists('libraryAdmin', $userRoles) || array_key_exists('opacAdmin', $userRoles) || array_key_exists('cataloging', $userRoles) || array_key_exists('superCataloger', $userRoles))}
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
								{if array_key_exists('opacAdmin', $userRoles) || array_key_exists('superCataloger', $userRoles)}
									<div class="adminMenuLink"><a href="/ILS/TranslationMaps">&nbsp;&raquo;&nbsp;{translate text="Translation Maps"}</a></div>
									{* Sierra/Millennium OPAC Admin Actions*}
									{if ($ils == 'Millennium' || $ils == 'Sierra')}
										<div class="adminMenuLink"><a href="/ILS/LoanRules">&nbsp;&raquo;&nbsp;{translate text="Loan Rules"}</a></div>
										<div class="adminMenuLink"><a href="/ILS/LoanRuleDeterminers">&nbsp;&raquo;&nbsp;{translate text="Loan Rule Determiners"}</a></div>
									{/if}
								{/if}
								<div class="adminMenuLink"><a href="/ILS/IndexingLog">{translate text="Indexing Log"}</a></div>
								<div class="adminMenuLink"><a href="/ILS/Dashboard">{translate text="Dashboard"}</a></div>
							</div>
						</div>
					</div>
				{/if}

				{if array_key_exists('OverDrive', $enabledModules) && (array_key_exists('libraryAdmin', $userRoles) || array_key_exists('opacAdmin', $userRoles) || array_key_exists('cataloging', $userRoles) || array_key_exists('superCataloger', $userRoles))}
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

				{if array_key_exists('Hoopla', $enabledModules) && (array_key_exists('libraryAdmin', $userRoles) || array_key_exists('opacAdmin', $userRoles) || array_key_exists('cataloging', $userRoles) || array_key_exists('superCataloger', $userRoles))}
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

				{if array_key_exists('RBdigital', $enabledModules) && (array_key_exists('libraryAdmin', $userRoles) || array_key_exists('opacAdmin', $userRoles) || array_key_exists('cataloging', $userRoles) || array_key_exists('superCataloger', $userRoles))}
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

				{if array_key_exists('Axis 360', $enabledModules) && (array_key_exists('libraryAdmin', $userRoles) || array_key_exists('opacAdmin', $userRoles) || array_key_exists('cataloging', $userRoles) || array_key_exists('superCataloger', $userRoles))}
					{if $module == 'Axis360' && in_array($action, array('Settings', 'IndexingLog', 'Scopes', 'Dashboard'))}
						{assign var="curSection" value=true}
					{else}
						{assign var="curSection" value=false}
					{/if}
					<div class="panel{if $curSection} active{/if}">
						<a href="#axis360Menu" data-toggle="collapse" data-parent="#adminMenuAccordion">
							<div class="panel-heading">
								<div class="panel-title">
									{translate text="Axis 360"}
								</div>
							</div>
						</a>
						<div id="axis360Menu" class="panel-collapse collapse {if $curSection}in{/if}">
							<div class="panel-body">
								<div class="adminMenuLink"><a href="/Axis360/Settings">{translate text="Settings"}</a></div>
								<div class="adminMenuLink">&nbsp;&raquo;&nbsp;<a href="/Axis360/Scopes">{translate text="Scopes"}</a></div>
								<div class="adminMenuLink"><a href="/Axis360/IndexingLog">{translate text="Indexing Log"}</a></div>
								<div class="adminMenuLink"><a href="/Axis360/Dashboard">{translate text="Dashboard"}</a></div>
							</div>
						</div>
					</div>
				{/if}

				{if array_key_exists('Cloud Library', $enabledModules) && (array_key_exists('libraryAdmin', $userRoles) || array_key_exists('opacAdmin', $userRoles) || array_key_exists('cataloging', $userRoles) || array_key_exists('superCataloger', $userRoles))}
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

				{if array_key_exists('Side Loads', $enabledModules) && (array_key_exists('libraryAdmin', $userRoles) || array_key_exists('opacAdmin', $userRoles) || array_key_exists('cataloging', $userRoles) || array_key_exists('superCataloger', $userRoles))}
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

				{if array_key_exists('EBSCO EDS', $enabledModules) && (array_key_exists('libraryAdmin', $userRoles) || array_key_exists('opacAdmin', $userRoles))}
					{if $module == 'EBSCO' && in_array($action, array('Settings', 'EDSDashboard'))}
						{assign var="curSection" value=true}
					{else}
						{assign var="curSection" value=false}
					{/if}
					<div class="panel{if $curSection} active{/if}">
						<a href="#ebscoMenu" data-toggle="collapse" data-parent="#adminMenuAccordion">
							<div class="panel-heading">
								<div class="panel-title">
									{translate text="EBSCO"}
								</div>
							</div>
						</a>
						<div id="ebscoMenu" class="panel-collapse collapse {if $curSection}in{/if}">
							<div class="panel-body">
								<div class="adminMenuLink"><a href="/EBSCO/EDSSettings">{translate text="EDS Settings"}</a></div>
								<div class="adminMenuLink"><a href="/EBSCO/EDSDashboard">{translate text="EDS Dashboard"}</a></div>
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

				{if array_key_exists('Events', $enabledModules) && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles))}
					{if $module == 'Events' && in_array($action, array('LMLibraryCalendarSettings', 'Dashboard', 'IndexingLog'))}
						{assign var="curSection" value=true}
					{else}
						{assign var="curSection" value=false}
					{/if}
					<div class="panel{if $curSection} active{/if}">
						<a href="#eventsMenu" data-toggle="collapse" data-parent="#adminMenuAccordion">
							<div class="panel-heading">
								<div class="panel-title">
									{translate text="Events"}
								</div>
							</div>
						</a>
						<div id="eventsMenu" class="panel-collapse collapse {if $curSection}in{/if}">
							<div class="panel-body">
								<div class="adminMenuLink"><a href="/Events/LMLibraryCalendarSettings">{translate text="Library Market - Calendar Settings"}</a></div>
								<div class="adminMenuLink"><a href="/Events/IndexingLog">{translate text="Indexing Log"}</a></div>
								{*<div class="adminMenuLink"><a href="/Events/Dashboard">{translate text="Dashboard"}</a></div>*}
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

				{if (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles))}
					{if $module == 'Admin' && in_array($action, array('ReleaseNotes', 'SubmitTicket'))}
						{assign var="curSection" value=true}
					{else}
						{assign var="curSection" value=false}
					{/if}
					<div class="panel{if $curSection} active{/if}">
						<a href="#aspenHelpMenu" data-toggle="collapse" data-parent="#adminMenuAccordion">
							<div class="panel-heading">
								<div class="panel-title">
									{translate text="Aspen Discovery Help"}
								</div>
							</div>
						</a>
						<div id="aspenHelpMenu" class="panel-collapse collapse {if $curSection}in{/if}">
							<div class="panel-body">
								<div class="adminMenuLink"><a href="/Admin/HelpManual?page=table_of_contents">{translate text="Help Manual"}</a></div>
								<div class="adminMenuLink"><a href="/Admin/ReleaseNotes">{translate text="Release Notes"}</a></div>
								{if $showSubmitTicket}
								<div class="adminMenuLink"><a href="/Admin/SubmitTicket">{translate text="Submit Ticket"}</a></div>
								{/if}
							</div>
						</div>
					</div>
				{/if}
			</div>
		</div>
	{/if}
{/strip}