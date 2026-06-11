{strip}
<div id="main-content" class="col-md-12">
	<h1>{translate text="Event Attendance Management" isAdminFacing=true}</h1>

	{if !empty($featureDisabled)}
		<div class="alert alert-warning">
			{translate text="Staff registration for events is not enabled for this library. Please contact your administrator to enable this feature in Library Settings." isAdminFacing=true}
		</div>
	{elseif !empty($error)}
		<div class="alert alert-danger">{$error}</div>
	{elseif !empty($showEventSelector)}
		<div class="well">
			<h2>{translate text="Select an Event" isAdminFacing=true}</h2>
			<p>{translate text="Choose an event instance to view registrations and manage attendance." isAdminFacing=true}</p>

			{if !empty($eventInstances)}
				<table class="table table-striped table-bordered">
					<thead>
						<tr>
							<th>{translate text="Event" isAdminFacing=true}</th>
							<th>{translate text="Date" isAdminFacing=true}</th>
							<th>{translate text="Time" isAdminFacing=true}</th>
							<th>{translate text="Location" isAdminFacing=true}</th>
							<th>{translate text="Registrations" isAdminFacing=true}</th>
							<th>{translate text="Actions" isAdminFacing=true}</th>
						</tr>
					</thead>
					<tbody>
						{foreach from=$eventInstances item=event}
							<tr>
								<td>{$event.title|escape}</td>
								<td>{$event.date|date_format:"%B %e, %Y"}</td>
								<td>{$event.time}</td>
								<td>{$event.location|escape}</td>
								<td>
									{if !empty($event.attendeeCategoryBreakdown)}
										<table style="margin:0; border-collapse:collapse;">
											{assign var=catTotal value=0}
											{foreach from=$event.attendeeCategoryBreakdown item=cat}
												<tr>
													<td style="padding:0 8px 0 0;">{$cat.name|escape}:</td>
													<td style="padding:0; text-align:right;" colspan="2">{$cat.count}</td>
												</tr>
												{assign var=catTotal value=$catTotal+$cat.count}
											{/foreach}
											<tr>
												<td style="padding:2px 8px 0 0; border-top:1px solid #ddd;"><strong>{translate text="Total" isAdminFacing=true}:</strong></td>
												<td style="padding:2px 0 0 0; text-align:right; border-top:1px solid #ddd;"><strong>{$catTotal}</strong></td>
												<td style="padding:2px 0 0 0; border-top:1px solid #ddd;">{if $event.numberOfSeats}<strong> / {$event.numberOfSeats}</strong>{/if}</td>
											</tr>
										</table>
									{else}
										{$event.registrationCount}
										{if $event.numberOfSeats}
											/ {$event.numberOfSeats}
										{/if}
									{/if}
								</td>
								<td>
									<a href="/Events/AttendanceManagement?eventInstanceId={$event.instanceId}" class="btn btn-sm btn-primary">
										{translate text="Manage" isAdminFacing=true}
									</a>
								</td>
							</tr>
						{/foreach}
					</tbody>
				</table>
			{else}
				<div class="alert alert-info">
					{translate text="No upcoming events with registration enabled found." isAdminFacing=true}
				</div>
			{/if}
		</div>
	{else}
		<div class="row">
			<div class="col-md-12">
				<div class="well">
					<h2>{$eventTitle|escape}</h2>
					<div class="row">
						<div class="col-md-6">
							<p><strong>{translate text="Date" isAdminFacing=true}:</strong> {$eventDate|date_format:"%B %e, %Y"}</p>
							<p><strong>{translate text="Time" isAdminFacing=true}:</strong> {$eventTime}</p>
							<p><strong>{translate text="Location" isAdminFacing=true}:</strong> {$eventLocation|escape}</p>
						</div>
						<div class="col-md-6">
							<p>
								{include file="Events/capacityDisplay.tpl" compact=false}
							</p>
						</div>
					</div>

					<div class="btn-group" style="margin-bottom: 15px;">
						{if $canManageEventRegistration}
							<button type="button" class="btn btn-primary" onclick="AspenDiscovery.Events.showStaffRegistrationModal({$eventInstanceId});">
								<i class="fas fa-user-plus"></i> {translate text="Register Patron" isAdminFacing=true}
							</button>
						{/if}
						<a href="/Events/AttendanceManagement" class="btn btn-default">
							<i class="fas fa-arrow-left"></i> {translate text="Back to Event List" isAdminFacing=true}
						</a>
					</div>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-md-12">
				<h3>{translate text="Registered Patrons" isAdminFacing=true}</h3>
				<div id="registrationsList">
					{if !empty($registrations)}
						<div style="margin-bottom: 15px;">
							 <form method="get" action="" style="display: inline-block; margin-right: 10px;">
								<input type="hidden" name="eventInstanceId" value="{$eventInstanceId}">
								<button type="submit" class="btn btn-sm btn-default" name="download_list" value="true">
									{translate text="Download as List" isAdminFacing=true}
								</button>
							</form>

							<form method="get" action="" style="display: inline-block;">
								<input type="hidden" name="eventInstanceId" value="{$eventInstanceId}">
								<button type="submit" class="btn btn-sm btn-default" name="download_csv" value="true">
									{translate text="Download as CSV" isAdminFacing=true}
								</button>
							</form>
						</div>
						<table class="table table-striped table-bordered" id="registrationsTable">
							<thead>
								<tr>
									<th>{translate text="Patron Name" isAdminFacing=true}</th>
									<th>{translate text="Barcode" isAdminFacing=true}</th>
									<th>{translate text="Email" isAdminFacing=true}</th>
									<th>{translate text="Registered By" isAdminFacing=true}</th>
									<th>{translate text="Date Registered" isAdminFacing=true}</th>
									<th style="text-align: center;">{translate text="Attended" isAdminFacing=true}</th>
									<th>{translate text="Actions" isAdminFacing=true}</th>
								</tr>
							</thead>
							<tbody>
								{foreach from=$registrations item=reg}
									<tr id="registration-row-{$reg.id}">
										<td>{$reg.userName|escape}</td>
										<td>{$reg.userBarcode|escape}</td>
										<td>{$reg.userEmail|escape}</td>
										<td>
											{if $reg.registeredByStaff}
												{$reg.staffName|escape}
											{else}
												<em>{translate text="Self" isAdminFacing=true}</em>
											{/if}
										</td>
										<td>{$reg.dateRegistered|default:"-"}</td>
										<td style="text-align: center;">
											<input type="checkbox" id="attended-{$reg.id}" {if $reg.attended}checked {/if}onchange="AspenDiscovery.Events.toggleAttendance({$reg.id}, this.checked);">
										</td>
										<td>
											<button type="button" class="btn btn-xs btn-danger" onclick="AspenDiscovery.Events.staffUnregisterUser({$eventInstanceId}, {$reg.userId});">
												<i class="fas fa-times"></i> {translate text="Cancel" isAdminFacing=true}
											</button>
										</td>
									</tr>
								{/foreach}
							</tbody>
						</table>
					{else}
						<div class="alert alert-info">
							{translate text="No registrations yet." isAdminFacing=true}
						</div>
					{/if}
				</div>
			</div>
		</div>
	{/if}
</div>
{/strip}
