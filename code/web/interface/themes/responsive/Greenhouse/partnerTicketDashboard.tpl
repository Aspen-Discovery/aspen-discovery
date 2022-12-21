{strip}
	<div id="main-content" class="col-sm-12">
		<h1>{translate text="Aspen Site Stats Dashboard" isAdminFacing=true}</h1>
		{include file="Greenhouse/selectSiteForm.tpl"}

		<h2>{translate text="Summary" isAdminFacing=true}</h2>
		<div class="row">
			<div class="result-label col-md-3">{translate text='Number of tickets closed' isPublicFacing=true}</div>
			<div class="col-md-9 result-value">
				{$totalTicketsClosed}
			</div>
		</div>
		<div class="row">
			<div class="result-label col-md-3">{translate text='Number of priority tickets closed' isPublicFacing=true}</div>
			<div class="col-md-9 result-value">
				{$totalPriorityTicketsClosed}
			</div>
		</div>

		<div class="row">
			<div class="result-label col-md-3">{translate text='Last Priority One Ticket Closed' isPublicFacing=true}</div>
			<div class="col-md-9 result-value">
				{if !empty($lastPriority1Closed)}
					{$lastPriority1Closed->dateClosed|date_format:"%D"}
				{else}
					{translate text='N/A' isAdminFacing=true}
				{/if}
			</div>
		</div>
		<div class="row">
			<div class="result-label col-md-3">{translate text='Last Priority Two Ticket Closed' isPublicFacing=true}</div>
			<div class="col-md-9 result-value">
				{if !empty($lastPriority2Closed)}
					{$lastPriority2Closed->dateClosed|date_format:"%D"}
				{else}
					{translate text='N/A' isAdminFacing=true}
				{/if}
			</div>
		</div>
		<div class="row">
			<div class="result-label col-md-3">{translate text='Last Priority Three Ticket Closed' isPublicFacing=true}</div>
			<div class="col-md-9 result-value">
				{if !empty($lastPriority3Closed)}
					{$lastPriority3Closed->dateClosed|date_format:"%D"}
				{else}
					{translate text='N/A' isAdminFacing=true}
				{/if}
			</div>
		</div>

		<h2>{translate text="Priorities" isAdminFacing=true}</h2>
		<div class="row">
			<div class="result-label col-md-2">{translate text='Priority 1' isPublicFacing=true}</div>
			{if !empty($priority1Ticket)}
				<div class="col-xs-8">
					<a href="/Greenhouse/Tickets?objectAction=edit&id={$priority1Ticket->id}">{$priority1Ticket->ticketId} {$priority1Ticket->title}</a>
				</div>
				<div class="col-xs-2">
					<a href="https://ticket.bywatersolutions.com/Ticket/Display.html?id={$priority1Ticket->ticketId}" class="btn btn-default btn-sm" aria-label="Open in RT for Ticket {$priority1Ticket->ticketId}" target="_blank"><i class="fas fa-external-link-alt"></i>  Open in RT</a>
				</div>
			{else}
				<div class="col-md-9 result-value">
					N/A
				</div>
			{/if}
		</div>
		<div class="row">
			<div class="result-label col-md-2">{translate text='Priority 2' isPublicFacing=true}</div>
			{if !empty($priority2Ticket)}
				<div class="col-xs-8">
					<a href="/Greenhouse/Tickets?objectAction=edit&id={$priority2Ticket->id}">{$priority2Ticket->ticketId} {$priority2Ticket->title}</a>
				</div>
				<div class="col-xs-2">
					<a href="https://ticket.bywatersolutions.com/Ticket/Display.html?id={$priority2Ticket->ticketId}" class="btn btn-default btn-sm" aria-label="Open in RT for Ticket {$priority2Ticket->ticketId}" target="_blank"><i class="fas fa-external-link-alt"></i>  Open in RT</a>
				</div>
			{else}
				<div class="col-md-9 result-value">
					N/A
				</div>
			{/if}
		</div>
		<div class="row">
			<div class="result-label col-md-2">{translate text='Priority 3' isPublicFacing=true}</div>
			{if !empty($priority3Ticket)}
				<div class="col-xs-8">
					<a href="/Greenhouse/Tickets?objectAction=edit&id={$priority3Ticket->id}">{$priority3Ticket->ticketId} {$priority3Ticket->title}</a>
				</div>
				<div class="col-xs-2">
					<a href="https://ticket.bywatersolutions.com/Ticket/Display.html?id={$priority3Ticket->ticketId}" class="btn btn-default btn-sm" aria-label="Open in RT for Ticket {$priority3Ticket->ticketId}" target="_blank"><i class="fas fa-external-link-alt"></i>  Open in RT</a>
				</div>
			{else}
				<div class="col-md-9 result-value">
					N/A
				</div>
			{/if}
		</div>

		<h2>{translate text="Tickets" isAdminFacing=true}</h2>
		<div class="panel-group">
			<div class="panel">
				<a href="#open-support-tickets" data-toggle="collapse">
					<div class="panel-heading">
						<div class="panel-title">
							{translate text="Open Support Tickets" isAdminFacing=true} ({$openSupportTickets|@count})
						</div>
					</div>
				</a>
				<div id="open-support-tickets" class="panel-collapse collapse">
					<div class="panel-body">
						{foreach from=$openSupportTickets item=ticket}
							<div class="row striped-{cycle values="odd,even"}">
								<div class="col-xs-2">
									{$ticket->dateCreated|date_format:"%D"}
								</div>
								<div class="col-xs-8">
									<a href="/Greenhouse/Tickets?objectAction=edit&id={$ticket->id}">{$ticket->ticketId} {$ticket->title}</a>
								</div>
								<div class="col-xs-2">
									<a href="https://ticket.bywatersolutions.com/Ticket/Display.html?id={$ticket->ticketId}" class="btn btn-default btn-sm" aria-label="Open in RT for Ticket {$ticket->ticketId}" target="_blank"><i class="fas fa-external-link-alt"></i>  Open in RT</a>
								</div>
							</div>
						{/foreach}
					</div>
				</div>
			</div>

			<div class="panel">
				<a href="#open-bugs" data-toggle="collapse">
					<div class="panel-heading">
						<div class="panel-title">
							{translate text="Open Bugs" isAdminFacing=true} ({$openBugs|@count})
						</div>
					</div>
				</a>
				<div id="open-bugs" class="panel-collapse collapse">
					<div class="panel-body">
						{foreach from=$openBugs item=ticket}
							<div class="row striped-{cycle values="odd,even"}">
								<div class="col-xs-2">
									{$ticket->dateCreated|date_format:"%D"}
								</div>
								<div class="col-xs-2">
									{$ticket->severity}
								</div>
								<div class="col-xs-6">
									<a href="/Greenhouse/Tickets?objectAction=edit&id={$ticket->id}">{$ticket->ticketId} {$ticket->title}</a>
								</div>
								<div class="col-xs-2">
									<a href="https://ticket.bywatersolutions.com/Ticket/Display.html?id={$ticket->ticketId}" class="btn btn-default btn-sm" aria-label="Open in RT for Ticket {$ticket->ticketId}" target="_blank"><i class="fas fa-external-link-alt"></i>  Open in RT</a>
								</div>
							</div>
						{/foreach}
					</div>
				</div>
			</div>

			<div class="panel">
				<a href="#open-development" data-toggle="collapse">
					<div class="panel-heading">
						<div class="panel-title">
							{translate text="Open Development Tickets" isAdminFacing=true} ({$openDevelopments|@count})
						</div>
					</div>
				</a>
				<div id="open-development" class="panel-collapse collapse">
					<div class="panel-body">
						{foreach from=$openDevelopments item=ticket}
							<div class="row striped-{cycle values="odd,even"}">
								<div class="col-xs-2">
									{$ticket->dateCreated|date_format:"%D"}
								</div>
								<div class="col-xs-8">
									<a href="/Greenhouse/Tickets?objectAction=edit&id={$ticket->id}">{$ticket->ticketId} {$ticket->title}</a>
								</div>
								<div class="col-xs-2">
									<a href="https://ticket.bywatersolutions.com/Ticket/Display.html?id={$ticket->ticketId}" class="btn btn-default btn-sm" aria-label="Open in RT for Ticket {$ticket->ticketId}" target="_blank"><i class="fas fa-external-link-alt"></i>  Open in RT</a>
								</div>
							</div>
						{/foreach}
					</div>
				</div>
			</div>

			<div class="panel">
				<a href="#open-implementation" data-toggle="collapse">
					<div class="panel-heading">
						<div class="panel-title">
							{translate text="Open Implementation Tickets" isAdminFacing=true} ({$openImplementationTickets|@count})
						</div>
					</div>
				</a>
				<div id="open-implementation" class="panel-collapse collapse">
					<div class="panel-body">
						{foreach from=$openImplementationTickets item=ticket}
							<div class="row">
								<div class="col-xs-2">
									{$ticket->dateCreated|date_format:"%D"}
								</div>
								<div class="col-xs-8">
									<a href="/Greenhouse/Tickets?objectAction=edit&id={$ticket->id}">{$ticket->ticketId} {$ticket->title}</a>
								</div>
								<div class="col-xs-2">
									<a href="https://ticket.bywatersolutions.com/Ticket/Display.html?id={$ticket->ticketId}" class="btn btn-default btn-sm" aria-label="Open in RT for Ticket {$ticket->ticketId}" target="_blank"><i class="fas fa-external-link-alt"></i>  Open in RT</a>
								</div>
							</div>
						{/foreach}
					</div>
				</div>
			</div>

			<div class="panel">
				<a href="#closed-priority" data-toggle="collapse">
					<div class="panel-heading">
						<div class="panel-title">
							{translate text="Closed Priority Tickets" isAdminFacing=true} ({$closedPriorityTickets|@count})
						</div>
					</div>
				</a>
				<div id="closed-priority" class="panel-collapse collapse">
					<div class="panel-body">
						{foreach from=$closedPriorityTickets item=ticket}
							<div class="row striped-{cycle values="odd,even"}">
								<div class="col-xs-2">
									{translate text="Priority %1%" 1=$ticket->partnerPriority isAdminFacing=true}
								</div>
								<div class="col-xs-2">
									{$ticket->dateClosed|date_format:"%D"}
								</div>
								<div class="col-xs-6">
									<a href="/Greenhouse/Tickets?objectAction=edit&id={$ticket->id}">{$ticket->ticketId} {$ticket->title}</a>
								</div>
								<div class="col-xs-2">
									<a href="https://ticket.bywatersolutions.com/Ticket/Display.html?id={$ticket->ticketId}" class="btn btn-default btn-sm" aria-label="Open in RT for Ticket {$ticket->ticketId}" target="_blank"><i class="fas fa-external-link-alt"></i>  Open in RT</a>
								</div>
							</div>
						{/foreach}
					</div>
				</div>
			</div>

			<div class="panel">
				<a href="#closed-tickets" data-toggle="collapse">
					<div class="panel-heading">
						<div class="panel-title">
							{translate text="Closed Tickets" isAdminFacing=true} ({$closedTickets|@count})
						</div>
					</div>
				</a>
				<div id="closed-tickets" class="panel-collapse collapse">
					<div class="panel-body">
						{foreach from=$closedTickets item=ticket}
							<div class="row striped-{cycle values="odd,even"}">
								<div class="col-xs-2">
									{$ticket->dateClosed|date_format:"%D"}
								</div>
								<div class="col-xs-2">
				                    {$ticket->queue}
								</div>
								<div class="col-xs-6">
									<a href="/Greenhouse/Tickets?objectAction=edit&id={$ticket->id}">{$ticket->ticketId} {$ticket->title}</a>
								</div>
								<div class="col-xs-2">
									<a href="https://ticket.bywatersolutions.com/Ticket/Display.html?id={$ticket->ticketId}" class="btn btn-default btn-sm" aria-label="Open in RT for Ticket {$ticket->ticketId}" target="_blank"><i class="fas fa-external-link-alt"></i>  Open in RT</a>
								</div>
							</div>
						{/foreach}
					</div>
				</div>
			</div>
		</div>

	</div>
{/strip}