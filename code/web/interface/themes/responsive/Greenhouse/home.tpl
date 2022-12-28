{strip}
	<div class="row">
		<div class="col-xs-12 col-md-9">
			<h1 id="pageTitle">{$pageTitleShort}</h1>
		</div>
	</div>

	<div id="adminSections" class="grid" data-colcade="columns: .grid-col, items: .grid-item">
		<!-- columns -->
		<div class="grid-col grid-col--1"></div>
		<div class="grid-col grid-col--2"></div>
		<!-- items -->
		<div class="adminSection grid-item" id="greenhouse-main">
			<div class="adminPanel">
				<div class="adminSectionLabel row"><div class="col-tn-12">{translate text=Greenhouse isAdminFacing=true}</div></div>
				<div class="adminSectionActions row">
					<div class="col-tn-12">
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Greenhouse/Sites" title="{translate text="Site Listing" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Greenhouse/Sites">{translate text="Site Listing"  isAdminFacing=true}</a></div>
							</div>
						</div>
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Greenhouse/UpdateCenter" title="{translate text="Update Center" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Greenhouse/UpdateCenter">{translate text="Update Center"  isAdminFacing=true}</a></div>
							</div>
						</div>
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Greenhouse/Settings" title="{translate text="Settings" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Greenhouse/Settings">{translate text="Settings"  isAdminFacing=true}</a></div>
							</div>
						</div>
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Greenhouse/ExternalRequestLog" title="{translate text="External Request Log" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Greenhouse/ExternalRequestLog">{translate text="External Request Log"  isAdminFacing=true}</a></div>
							</div>
						</div>
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Greenhouse/ObjectHistoryLog" title="{translate text="Object History Log" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Greenhouse/ObjectHistoryLog">{translate text="Object History Log"  isAdminFacing=true}</a></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="adminSection grid-item" id="greenhouse-stats-reports">
			<div class="adminPanel">
				<div class="adminSectionLabel row"><div class="col-tn-12">{translate text="Greenhouse Stats/Reports" isAdminFacing=true}</div></div>
				<div class="adminSectionActions row">
					<div class="col-tn-12">
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Greenhouse/SiteStatus" title="{translate text="Site Status" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Greenhouse/SiteStatus">{translate text="Site Status"  isAdminFacing=true}</a></div>
							</div>
						</div>
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Greenhouse/SiteStatDashboard" title="{translate text="Site Stats Dashboard" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Greenhouse/SiteStatDashboard">{translate text="Site Stats Dashboard"  isAdminFacing=true}</a></div>
							</div>
						</div>
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Greenhouse/SiteCpuUsage" title="{translate text="Site CPU Usage" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Greenhouse/SiteCpuUsage">{translate text="Site CPU Usage"  isAdminFacing=true}</a></div>
							</div>
						</div>
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Greenhouse/SiteMemoryUsage" title="{translate text="Site Memory Usage" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Greenhouse/SiteMemoryUsage">{translate text="Site Memory Usage"  isAdminFacing=true}</a></div>
							</div>
						</div>
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Greenhouse/SiteWaitTime" title="{translate text="Site Wait Time" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Greenhouse/SiteWaitTime">{translate text="Site Wait Time"  isAdminFacing=true}</a></div>
							</div>
						</div>
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Greenhouse/SitesByLocation" title="{translate text="Sites By Location" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Greenhouse/SitesByLocation">{translate text="Sites By Location"  isAdminFacing=true}</a></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="adminSection grid-item" id="greenhouse-ticketing">
			<div class="adminPanel">
				<div class="adminSectionLabel row"><div class="col-tn-12">{translate text="Ticketing" isAdminFacing=true}</div></div>
				<div class="adminSectionActions row">
					<div class="col-tn-12">
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Greenhouse/Tickets" title="{translate text="Tickets" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Greenhouse/Tickets">{translate text="Tickets"  isAdminFacing=true}</a></div>
							</div>
						</div>
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Greenhouse/TicketStatuses" title="{translate text="Ticket Statuses" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Greenhouse/TicketStatuses">{translate text="Ticket Statuses"  isAdminFacing=true}</a></div>
							</div>
						</div>
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Greenhouse/TicketQueues" title="{translate text="Ticket Queues" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Greenhouse/TicketQueues">{translate text="Ticket Queues"  isAdminFacing=true}</a></div>
							</div>
						</div>
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Greenhouse/TicketSeverities" title="{translate text="Ticket Severities" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Greenhouse/TicketSeverities">{translate text="Ticket Severities"  isAdminFacing=true}</a></div>
							</div>
						</div>
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Greenhouse/TicketComponents" title="{translate text="Ticket Components" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Greenhouse/TicketComponents">{translate text="Ticket Components"  isAdminFacing=true}</a></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="adminSection grid-item" id="greenhouse-ticket-stats">
			<div class="adminPanel">
				<div class="adminSectionLabel row"><div class="col-tn-12">{translate text="Ticket Stats" isAdminFacing=true}</div></div>
				<div class="adminSectionActions row">
					<div class="col-tn-12">
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Greenhouse/TicketsCreatedByDay" title="{translate text="Tickets Created By Day" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Greenhouse/TicketsCreatedByDay">{translate text="Tickets Created By Day"  isAdminFacing=true}</a></div>
							</div>
						</div>
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Greenhouse/TicketsCreatedByMonth" title="{translate text="Tickets Created By Month" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Greenhouse/TicketsCreatedByMonth">{translate text="Tickets Created By Month"  isAdminFacing=true}</a></div>
							</div>
						</div>
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Greenhouse/TicketsClosedByDay" title="{translate text="Tickets Closed By Day" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Greenhouse/TicketsClosedByDay">{translate text="Tickets Closed By Day"  isAdminFacing=true}</a></div>
							</div>
						</div>
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Greenhouse/TicketsClosedByMonth" title="{translate text="Tickets Closed By Month" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Greenhouse/TicketsClosedByMonth">{translate text="Tickets Closed By Month"  isAdminFacing=true}</a></div>
							</div>
						</div>
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Greenhouse/BugsBySeverityAndComponent" title="{translate text="Active Bugs By Severity" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Greenhouse/BugsBySeverityAndComponent">{translate text="Active Bugs By Severity"  isAdminFacing=true}</a></div>
							</div>
						</div>
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Greenhouse/TicketsByPartner" title="{translate text="Active Tickets By Partner" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Greenhouse/TicketsByPartner">{translate text="Active Tickets By Partner"  isAdminFacing=true}</a></div>
							</div>
						</div>
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Greenhouse/TicketsByComponent" title="{translate text="Active Tickets By Component" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Greenhouse/TicketsByComponent">{translate text="Active Tickets By Component"  isAdminFacing=true}</a></div>
							</div>
						</div>
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Greenhouse/PartnerTicketDashboard" title="{translate text="Partner Priorities" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Greenhouse/PartnerPriorities">{translate text="Partner Priorities"  isAdminFacing=true}</a></div>
							</div>
						</div>
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Greenhouse/PartnerTicketDashboard" title="{translate text="Partner Ticket Dashboard" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Greenhouse/PartnerTicketDashboard">{translate text="Partner Ticket Dashboard"  isAdminFacing=true}</a></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="adminSection grid-item" id="greenhouse-maintenance-tools">
			<div class="adminPanel">
				<div class="adminSectionLabel row"><div class="col-tn-12">{translate text="Maintenance Tools " isAdminFacing=true}</div></div>
				<div class="adminSectionActions row">
					<div class="col-tn-12">
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Greenhouse/ReadingHistoryReload" title="{translate text="Reload Reading History from ILS" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Greenhouse/ReadingHistoryReload">{translate text="Reload Reading History from ILS"  isAdminFacing=true}</a></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="adminSection grid-item" id="greenhouse-migration-tools">
			<div class="adminPanel">
				<div class="adminSectionLabel row"><div class="col-tn-12">{translate text="Migration Tools " isAdminFacing=true}</div></div>
				<div class="adminSectionActions row">
					<div class="col-tn-12">
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Greenhouse/ExportAspenData" title="{translate text="Export Aspen Data" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Greenhouse/ExportAspenData">{translate text="Export Aspen Data"  isAdminFacing=true}</a></div>
							</div>
						</div>
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Greenhouse/ImportAspenData" title="{translate text="Import Aspen Data" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Greenhouse/ImportAspenData">{translate text="Import Aspen Data"  isAdminFacing=true}</a></div>
							</div>
						</div>
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Greenhouse/CheckForDuplicateUsers" title="{translate text="Check for Duplicate Users" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Greenhouse/CheckForDuplicateUsers">{translate text="Check for Duplicate Users"  isAdminFacing=true}</a></div>
							</div>
						</div>
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Greenhouse/MapAndMergeUsers" title="{translate text="Map and Merge Users after migration" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Greenhouse/MapAndMergeUsers">{translate text="Map and Merge Users after migration"  isAdminFacing=true}</a></div>
							</div>
						</div>
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Greenhouse/UpdateKohaBorrowerNumbers" title="{translate text="Update Koha borrower numbers" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Greenhouse/UpdateKohaBorrowerNumbers">{translate text="Update Koha borrower numbers"  isAdminFacing=true}</a></div>
							</div>
						</div>
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Greenhouse/MergeDuplicateBarcodes" title="{translate text="Merge Duplicate Barcodes" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Greenhouse/MergeDuplicateBarcodes">{translate text="Merge Duplicate Barcodes"  isAdminFacing=true}</a></div>
							</div>
						</div>
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Greenhouse/MapBiblioNumbers" title="{translate text="Map Biblio Numbers" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Greenhouse/MapBiblioNumbers">{translate text="Map Biblio Numbers"  isAdminFacing=true}</a></div>
							</div>
						</div>
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Greenhouse/ClearAspenData" title="{translate text="Clear Aspen Data" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Greenhouse/ClearAspenData">{translate text="Clear Aspen Data"  isAdminFacing=true}</a></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="adminSection grid-item" id="greenhouse-development-tools">
			<div class="adminPanel">
				<div class="adminSectionLabel row"><div class="col-tn-12">{translate text="Development Planning " isAdminFacing=true}</div></div>
				<div class="adminSectionActions row">
					<div class="col-tn-12">
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Development/AspenReleases" title="{translate text="Aspen Releases" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Development/AspenReleases">{translate text="Aspen Releases"  isAdminFacing=true}</a></div>
							</div>
						</div>
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Development/Sprints" title="{translate text="Aspen Sprints" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Development/Sprints">{translate text="Aspen Sprints" isAdminFacing=true}</a></div>
							</div>
						</div>
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Development/Epics" title="{translate text="Epics" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Development/Epics">{translate text="Epics" isAdminFacing=true}</a></div>
							</div>
						</div>
						<div class="adminAction row">
							<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
								<a href="/Development/Tasks" title="{translate text="Development Tasks" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
							</div>
							<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
								<div class="adminActionLabel"><a href="/Development/Tasks">{translate text="Development Tasks" isAdminFacing=true}</a></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
{/strip}
