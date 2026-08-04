<table class="table table-striped curbside-pickup-table">
	<thead>
	<tr>
		<th class="curbside-date-col">{translate text="Date & Time" isPublicFacing=true}</th>
		<th class="curbside-location-col">{translate text="Location" isPublicFacing=true}</th>
		{if !empty($useNote)}<th class="curbside-note-col">{translate text=$noteLabel isPublicFacing=true isAdminEnteredData=true}</th>{/if}
		<th class="curbside-actions-col">{translate text="Actions" isPublicFacing=true}</th>
	</tr>
	</thead>
	<tbody>
	{foreach from=$currentCurbsidePickups.pickups item=pickup name="pickupLoop"}
		<tr>
			<td class="curbside-date-cell">
				<div>{$pickup['scheduled_pickup_datetime']|date_format:"%b %e, %Y at %l:%M %p"}</div>
				{if $pickup['staged_datetime']}
					<span class="badge badge-success curbside-status-badge">{translate text="Ready" isPublicFacing=true}</span>
				{else}
					<span class="badge badge-warning curbside-status-badge">{translate text="Pending" isPublicFacing=true}</span>
				{/if}
			</td>
			<td class="curbside-location-cell">{$pickup['branchname']}</td>
			{if !empty($useNote)}
				<td class="curbside-note-cell" lang="{$userLang->code}"><small class="text-muted"><i>{$pickup['notes']}</i></small></td>
			{/if}
			<td class="curbside-actions-cell">
				{if empty($pickup['arrival_datetime'])}
					{if !empty($allowCheckIn)}
						{* Show check-in button only when items are staged and ready. *}
						{if $pickup['isReady']}
							<button class="btn btn-primary btn-sm curbside-checkin-btn mb-1" onclick="return AspenDiscovery.CurbsidePickup.checkInCurbsidePickup('{$patronId}', '{$pickup['id']}')">
								<i class="fas fa-check mr-1"></i> {translate text="I'm here" isPublicFacing=true inAttribute=true}
							</button>
						{/if}
					{else}
						{* Display custom instructions popover when in instruction lead window or ready. *}
						{if $pickup['isReady'] || $pickup['withinTime'] || $timeAllowedBeforeCheckIn == -1}
							<a role="button" tabindex="0" class="btn btn-primary btn-sm curbside-checkin-btn mb-1" data-toggle="popover" data-trigger="focus" data-placement="left" data-title="{translate text='Check-In Instructions' isPublicFacing=true}" data-content="{translate text=$pickupInstructions isPublicFacing=true isAdminEnteredData=true}" data-html="true">
								<i class="fas fa-info-circle mr-1"></i> {translate text="View Instructions" isPublicFacing=true inAttribute=true}
							</a>
						{else}
							{* Not yet within instruction lead time. *}
							<span title="{translate text="Instructions will display %1% minutes before your scheduled pickup time." isPublicFacing=true 1=$timeAllowedBeforeCheckIn}">
								<button class="btn btn-secondary btn-sm curbside-checkin-btn mb-1" disabled>
									<i class="fas fa-info-circle mr-1"></i> {translate text="Instructions Not Available Yet" isPublicFacing=true}
								</button>
							</span>
						{/if}
					{/if}
				{/if}
				<button class="btn btn-outline-danger btn-sm curbside-cancel-btn" onclick="return AspenDiscovery.CurbsidePickup.getCancelCurbsidePickup('{$patronId}', '{$pickup['id']}')">
					{translate text="Cancel Pickup" isPublicFacing=true inAttribute=true}
				</button>
			</td>
		</tr>
		{foreachelse}
		<tr>
			<td colspan="4" class="text-center curbside-empty-message">
				{translate text="You don't have any scheduled curbside pickups." isPublicFacing=true}
			</td>
		</tr>
	{/foreach}
	</tbody>
</table>