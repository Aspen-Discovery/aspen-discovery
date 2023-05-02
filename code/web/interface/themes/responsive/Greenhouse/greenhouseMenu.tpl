{strip}
	{if !empty($loggedIn)}
		<div id="account-menu-label" class="sidebar-label row">
			<div class="col-xs-12">{translate text='Aspen Greenhouse' isAdminFacing=true}</div>
		</div>
		<div id="home-account-links" class="sidebar-links row">
			<div class="panel-group accordion" id="account-link-accordion">
				<div class="panel active">
					<a href="#greenhouseConfigurationGroup" data-toggle="collapse" data-parent="#adminMenuAccordion" aria-label="{translate text="Greenhouse Configuration" inAttribute=true isAdminFacing=true}">
						<div class="panel-heading">
							<div class="panel-title">
								{translate text="Greenhouse Configuration" isAdminFacing=true}
							</div>
						</div>
					</a>
					<div id="greenhouseConfigurationGroup" class="panel-collapse collapse in">
						<div class="panel-body">
							<div class="adminMenuLink "><a href="/Greenhouse/Sites">{translate text="Site Listing" isAdminFacing=true}</a></div>
							<div class="adminMenuLink "><a href="/Greenhouse/Settings">{translate text="Settings" isAdminFacing=true}</a></div>
						</div>
					</div>
				</div>

				<div class="panel active">
					<a href="#greenhouseConfigurationGroup" data-toggle="collapse" data-parent="#adminMenuAccordion" aria-label="{translate text="Greenhouse Configuration" inAttribute=true isAdminFacing=true}">
						<div class="panel-heading">
							<div class="panel-title">
								{translate text="Logging" isAdminFacing=true}
							</div>
						</div>
					</a>
					<div id="greenhouseConfigurationGroup" class="panel-collapse collapse in">
						<div class="panel-body">
							<div class="adminMenuLink "><a href="/Greenhouse/ExternalRequestLog">{translate text="External Request Log" isAdminFacing=true}</a></div>
							<div class="adminMenuLink "><a href="/Greenhouse/ObjectHistoryLog">{translate text="Object History Log" isAdminFacing=true}</a></div>
						</div>
					</div>
				</div>

				<div class="panel active">
					<a href="#maintenanceToolsGroup" data-toggle="collapse" data-parent="#adminMenuAccordion" aria-label="{translate text="Maintenance Tools" inAttribute=true isAdminFacing=true}">
						<div class="panel-heading">
							<div class="panel-title">
								{translate text="Maintenance Tools" isAdminFacing=true}
							</div>
						</div>
					</a>
					<div id="maintenanceToolsGroup" class="panel-collapse collapse in">
						<div class="panel-body">
							<div class="adminMenuLink "><a href="/Greenhouse/ScheduledUpdates">{translate text="Scheduled Updates" isAdminFacing=true}</a></div>
							<div class="adminMenuLink "><a href="/Greenhouse/ReadingHistoryReload">{translate text="Reload Reading History from ILS" isAdminFacing=true}</a></div>
						</div>
					</div>
				</div>

				<div class="panel active">
					<a href="#migrationToolsGroup" data-toggle="collapse" data-parent="#adminMenuAccordion" aria-label="{translate text="Migration Tools" inAttribute=true isAdminFacing=true}">
						<div class="panel-heading">
							<div class="panel-title">
								{translate text="Migration Tools" isAdminFacing=true}
							</div>
						</div>
					</a>
					<div id="migrationToolsGroup" class="panel-collapse collapse in">
						<div class="panel-body">
							<div class="adminMenuLink "><a href="/Greenhouse/ExportAspenData">{translate text="Export Aspen Data" isAdminFacing=true}</a></div>
							<div class="adminMenuLink "><a href="/Greenhouse/ImportAspenData">{translate text="Import Aspen Data" isAdminFacing=true}</a></div>
							<div class="adminMenuLink "><a href="/Greenhouse/CheckForDuplicateUsers">{translate text="Check for Duplicate Users" isAdminFacing=true}</a></div>
							<div class="adminMenuLink "><a href="/Greenhouse/MapAndMergeUsers">{translate text="Map and Merge Users After Migration" isAdminFacing=true}</a></div>
							<div class="adminMenuLink "><a href="/Greenhouse/UpdateKohaBorrowerNumbers">{translate text="Update Koha Borrower Numbers" isAdminFacing=true}</a></div>
							<div class="adminMenuLink "><a href="/Greenhouse/MergeDuplicateBarcodes">{translate text="Merge Duplicate Barcodes" isAdminFacing=true}</a></div>
							<div class="adminMenuLink "><a href="/Greenhouse/MapBiblioNumbers">{translate text="Map Biblio Numbers" isAdminFacing=true}</a></div>
							<div class="adminMenuLink "><a href="/Greenhouse/ClearAspenData">{translate text="Clear Aspen Data" isAdminFacing=true}</a></div>
						</div>
					</div>
				</div>

				<div class="panel active">
					<a href="#greenhouseStatsReportsGroup" data-toggle="collapse" data-parent="#adminMenuAccordion" aria-label="{translate text="Greenhouse Stats/Reports" inAttribute=true isAdminFacing=true}">
						<div class="panel-heading">
							<div class="panel-title">
								{translate text="Greenhouse Partner Maintenance & Reports" isAdminFacing=true}
							</div>
						</div>
					</a>
					<div id="greenhouseStatsReportsGroup" class="panel-collapse collapse in">
						<div class="panel-body">
							<div class="adminMenuLink "><a href="/Greenhouse/UpdateCenter">{translate text="Update Center" isAdminFacing=true}</a></div>
							<div class="adminMenuLink "><a href="/Greenhouse/SiteStatus">{translate text="Site Status" isAdminFacing=true}</a></div>
							<div class="adminMenuLink "><a href="/Greenhouse/SiteStatDashboard">{translate text="Site Stats Dashboard" isAdminFacing=true}</a></div>
							<div class="adminMenuLink "><a href="/Greenhouse/SiteCpuUsage">{translate text="Site CPU Usage" isAdminFacing=true}</a></div>
							<div class="adminMenuLink "><a href="/Greenhouse/SiteMemoryUsage">{translate text="Site Memory Usage" isAdminFacing=true}</a></div>
							<div class="adminMenuLink "><a href="/Greenhouse/SiteWaitTime">{translate text="Site Wait Time" isAdminFacing=true}</a></div>
							<div class="adminMenuLink "><a href="/Greenhouse/SitesByLocation">{translate text="Sites By Location" isAdminFacing=true}</a></div>
						</div>
					</div>
				</div>

				<div class="panel active">
					<a href="#communityGroup" data-toggle="collapse" data-parent="#adminMenuAccordion" aria-label="{translate text="Community" inAttribute=true isAdminFacing=true}">
						<div class="panel-heading">
							<div class="panel-title">
								{translate text="Community" isAdminFacing=true}
							</div>
						</div>
					</a>
					<div id="communityGroup" class="panel-collapse collapse in">
						<div class="panel-body">
							<div class="adminMenuLink "><a href="/Community/SharedContent">{translate text="Shared Content" isAdminFacing=true}</a></div>
						</div>
					</div>
				</div>

				<div class="panel active">
					<a href="#aspenLiDAGroup" data-toggle="collapse" data-parent="#adminMenuAccordion" aria-label="{translate text="Aspen LiDA" inAttribute=true isAdminFacing=true}">
						<div class="panel-heading">
							<div class="panel-title">
								{translate text="Aspen LiDA" isAdminFacing=true}
							</div>
						</div>
					</a>
					<div id="aspenLiDAGroup" class="panel-collapse collapse in">
						<div class="panel-body">
							<div class="adminMenuLink "><a href="/Greenhouse/AspenLiDABuildTracker">{translate text="Aspen LiDA Build Tracker" isAdminFacing=true}</a></div>
							<div class="adminMenuLink "><a href="/Greenhouse/AspenLiDASiteListingCache">{translate text="Aspen LiDA Site Listing Cache" isAdminFacing=true}</a></div>
						</div>
					</div>
				</div>
			</div>
		</div>
	{/if}
{/strip}