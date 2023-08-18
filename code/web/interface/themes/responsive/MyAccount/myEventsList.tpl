{strip}
	{if !empty($loggedIn)}

	<div class="resultHead">
		<div class="page">
			{if !empty($events)}
				<table class="table table-striped" id="myEventsTable">
					<thead>
					<tr>
						<th>{translate text='Event Date' isPublicFacing=true}</th>
						<th>{translate text='Start Time' isPublicFacing=true}</th>
						<th>{translate text='Event Name' isPublicFacing=true}</th>
						<th>{translate text='Location' isPublicFacing=true}</th>
						<th>{translate text='Registration Status' isPublicFacing=true}</th>
						<th>&nbsp;</th>
					</tr>
					</thead>
					<tbody>
						{foreach from=$events name="recordLoop" key=recordKey item=event}
							<tr id="myEvent{$event.sourceId|escape}" class="result {if ($smarty.foreach.recordLoop.iteration % 2) == 0}alt{/if} record{$smarty.foreach.recordLoop.iteration}">
								<td>
									{if isset($event.eventDate)}
										{$event.eventDate|date_format:"%B %d, %Y"}
									{/if}
								</td>
								<td>
									{if isset($event.eventDate)}
										{$event.eventDate|date_format:"%l:%M %p"}
									{/if}
								</td>
								<td class="myAccountCell">
									{if ($event.link != null)}
										<a href='{$event.link}'>{$event.title}</a>
									{else}
										{$event.title}
									{/if}
								</td>
								<td class="myAccountCell">
									{$event.location}
								</td>
								<td class="myAccountCell">
									{if ($event.regRequired == 1)}
										{if $event.externalLink != null && !($event.isRegistered)}
											{if !empty($event.regModalBody)}
												<a class="btn btn-xs btn-action" onclick="return AspenDiscovery.Account.regInfoModal(this, 'Events', '{$event.id}', '{$event.vendor}', '{$event.externalLink}');"><i class="fas fa-external-link-alt"></i> {translate text="Check Registration" isPublicFacing=true}
												</a>
											{else}
												<a href="{$event.externalLink}" class="btn btn-xs btn-action" target="_blank"><i class="fas fa-external-link-alt"></i> {translate text="Check Registration" isPublicFacing=true}</a>
											{/if}
										{elseif $event.isRegistered}
											<a href="{$event.externalLink}" class="btn btn-xs btn-action" target="_blank"><i class="fas fa-external-link-alt"></i> {translate text=" You Are Registered" isPublicFacing=true}</a>
										{else}
											<span>{translate text="Event Has Passed" isPublicFacing=true}</span>
										{/if}
									{/if}
								</td>
								<td class="myAccountCell">
									<span class="btn btn-xs btn-warning" onclick="return AspenDiscovery.Account.deleteSavedEvent('{$event.sourceId}', {$page}, '{$eventsFilter|escape}');">{translate text="Remove" isPublicFacing=true}</span>
								</td>
							</tr>
						{/foreach}
					</tbody>
				</table>
				{if !empty($pageLinks.all)}
					<div class="text-center">{$pageLinks.all}</div>
				{/if}
			{else}
				{if $eventsFilter == 'upcoming'}
					{translate text="You have no saved upcoming events." isPublicFacing=true}
				{/if}
				{if $eventsFilter == 'past'}
					{translate text="You have no saved past events." isPublicFacing=true}
				{/if}
				{if $eventsFilter == 'all'}
				{translate text="You have not saved any events yet." isPublicFacing=true}
				{/if}
			{/if}
		</div>
	</div>
	{else}
	<div class="page">
		{translate text="You must sign in to view this information." isPublicFacing=true}<a href='/MyAccount/Login' class="btn btn-primary">{translate text="Sign In" isPublicFacing=true}</a>
	</div>
	{/if}
{/strip}