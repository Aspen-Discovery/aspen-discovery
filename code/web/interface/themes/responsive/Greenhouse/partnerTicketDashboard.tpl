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
			<div class="result-label col-md-3">{translate text='Last Priority One Ticket Closed' isPublicFacing=true}</div>
			<div class="col-md-9 result-value">
				{if !empty($lastPriority1Closed)}
					{$lastPriority1Closed->dateClosed|date_format:"%D %T"} {$lastPriority1Closed->ticketId} {$lastPriority1Closed->title}
				{else}
					{translate text='N/A' isAdminFacing=true}
				{/if}
			</div>
		</div>
		<div class="row">
			<div class="result-label col-md-3">{translate text='Last Priority Two Ticket Closed' isPublicFacing=true}</div>
			<div class="col-md-9 result-value">
				{if !empty($lastPriority2Closed)}
					{$lastPriority2Closed->dateClosed|date_format:"%D %T"} {$lastPriority2Closed->ticketId} {$lastPriority2Closed->title}
				{else}
					{translate text='N/A' isAdminFacing=true}
				{/if}
			</div>
		</div>
		<div class="row">
			<div class="result-label col-md-3">{translate text='Last Priority Three Ticket Closed' isPublicFacing=true}</div>
			<div class="col-md-9 result-value">
				{if !empty($lastPriority3Closed)}
					{$lastPriority3Closed->dateClosed|date_format:"%D %T"} {$lastPriority3Closed->ticketId} {$lastPriority3Closed->title}
				{else}
					{translate text='N/A' isAdminFacing=true}
				{/if}
			</div>
		</div>

		<h2>{translate text="Open Support Tickets" isAdminFacing=true}</h2>

		<h2>{translate text="Open Bugs" isAdminFacing=true}</h2>

		<h2>{translate text="Open Development Tickets" isAdminFacing=true}</h2>

		<h2>{translate text="Open Implementation Tickets" isAdminFacing=true}</h2>

		<h2>{translate text="Closed Tickets" isAdminFacing=true}</h2>

	</div>
{/strip}