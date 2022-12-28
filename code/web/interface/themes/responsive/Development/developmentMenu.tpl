{strip}
    {if !empty($loggedIn)}
		<div id="account-menu-label" class="sidebar-label row">
			<div class="col-xs-12">{translate text='Aspen Developments' isAdminFacing=true}</div>
		</div>
		<div id="home-account-links" class="sidebar-links row">
			<div class="panel-group accordion" id="account-link-accordion">
				<div class="panel">
					<a href="#developmentGroup" data-toggle="collapse" data-parent="#adminMenuAccordion" aria-label="{translate text="Development Menu" inAttribute=true isAdminFacing=true}">
						<div class="panel-heading">
							<div class="panel-title">
								{translate text="Development Menu" isAdminFacing=true}
							</div>
						</div>
					</a>
					<div id="developmentGroup" class="panel-collapse collapse in">
						<div class="panel-body">
							<div class="adminMenuLink "><a href="/Development/AspenReleases">{translate text="Aspen Releases" isAdminFacing=true}</a></div>
							<div class="adminMenuLink "><a href="/Development/Sprints">{translate text="Aspen Sprints" isAdminFacing=true}</a></div>
							<div class="adminMenuLink "><a href="/Development/Epics">{translate text="Development Epics" isAdminFacing=true}</a></div>
							<div class="adminMenuLink "><a href="/Development/Tasks">{translate text="Development Tasks" isAdminFacing=true}</a></div>
						</div>
					</div>
				</div>

				<div class="panel">
					<a href="#supportGroup" data-toggle="collapse" data-parent="#adminMenuAccordion" aria-label="{translate text="Support Menu" inAttribute=true isAdminFacing=true}">
						<div class="panel-heading">
							<div class="panel-title">
								{translate text="Support Menu" isAdminFacing=true}
							</div>
						</div>
					</a>
					<div id="supportGroup" class="panel-collapse collapse in">
						<div class="panel-body">
							<div class="adminMenuLink "><a href="/Greenhouse/Tickets">{translate text="Tickets" isAdminFacing=true}</a></div>
							<div class="adminMenuLink "><a href="/Greenhouse/TicketStatuses">{translate text="Ticket Statuses" isAdminFacing=true}</a></div>
							<div class="adminMenuLink "><a href="/Greenhouse/TicketQueues">{translate text="Ticket Queues" isAdminFacing=true}</a></div>
							<div class="adminMenuLink "><a href="/Greenhouse/TicketSeverities">{translate text="Ticket Severities" isAdminFacing=true}</a></div>
							<div class="adminMenuLink "><a href="/Greenhouse/TicketComponents">{translate text="Ticket Components" isAdminFacing=true}</a></div>
						</div>
					</div>
				</div>

				<div class="panel">
					<a href="#supportGroup" data-toggle="collapse" data-parent="#adminMenuAccordion" aria-label="{translate text="Support Menu" inAttribute=true isAdminFacing=true}">
						<div class="panel-heading">
							<div class="panel-title">
                                {translate text="Ticket Stats" isAdminFacing=true}
							</div>
						</div>
					</a>
					<div id="supportGroup" class="panel-collapse collapse in">
						<div class="panel-body">
							<div class="adminMenuLink "><a href="/Greenhouse/TicketsCreatedByDay">{translate text="Tickets Created By Day" isAdminFacing=true}</a></div>
							<div class="adminMenuLink "><a href="/Development/TicketsCreatedByMonth">{translate text="Tickets Created By Month" isAdminFacing=true}</a></div>
							<div class="adminMenuLink "><a href="/Development/TicketsClosedByDay">{translate text="Tickets Closed By Day" isAdminFacing=true}</a></div>
							<div class="adminMenuLink "><a href="/Development/TicketsClosedByMonth">{translate text="Tickets Closed By Month" isAdminFacing=true}</a></div>
							<div class="adminMenuLink "><a href="/Greenhouse/TicketsByPartner">{translate text="Active Tickets By Partner" isAdminFacing=true}</a></div>
							<div class="adminMenuLink "><a href="/Greenhouse/PartnerTicketDashboard">{translate text="Partner Ticket Dashboard" isAdminFacing=true}</a></div>
							<div class="adminMenuLink "><a href="/Greenhouse/TicketsByComponent">{translate text="Active Tickets By Partner" isAdminFacing=true}</a></div>
						</div>
					</div>
				</div>

				<div class="panel">
					<a href="#supportGroup" data-toggle="collapse" data-parent="#adminMenuAccordion" aria-label="{translate text="Support Menu" inAttribute=true isAdminFacing=true}">
						<div class="panel-heading">
							<div class="panel-title">
                                {translate text="Partners & Users" isAdminFacing=true}
							</div>
						</div>
					</a>
					<div id="supportGroup" class="panel-collapse collapse in">
						<div class="panel-body">
							<div class="adminMenuLink "><a href="/Development/Partners">{translate text="Partners" isAdminFacing=true}</a></div>
							<div class="adminMenuLink "><a href="/Development/Users">{translate text="Users" isAdminFacing=true}</a></div>
						</div>
					</div>
				</div>
			</div>
		</div>
    {/if}
{/strip}