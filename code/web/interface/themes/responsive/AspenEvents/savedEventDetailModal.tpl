{strip}
	{* $event here refers to an event instance. *}
	<input type="hidden" id="eventRegistrationUserId-{$event.sourceId}" value="{$userId}">
	{if $event.numberOfSeats !== null}{include file='AspenEvents/seats.tpl' numberOfSeats=$event.numberOfSeats availableSeats=$event.availableSeats isEventFull=$event.isEventFull}{/if}
	<section class="well">
		{include file='AspenEvents/registrationUserSelector.tpl' eventSourceId=$event.sourceId}
		{include file='AspenEvents/registrationUserDetails.tpl' eventSourceId=$event.sourceId}
		{if !empty($event.registeredByStaff)}
			<p class="text-info"><em>{translate text="You were registered for this event by a staff member." isPublicFacing=true}</em></p>
		{/if}
		{if $event.registrationAction  == 'registered' || $event.registrationAction  == 'registrationAvailable'}
			{include file='AspenEvents/customRegistrationForm.tpl' eventSourceId=$event.sourceId userIsRegistered=$event.registrationAction == 'registered' savedRegistrationFieldValues=$event.savedRegistrationFieldValues savedAttendeeCounts=$event.savedAttendeeCounts}
		{/if}
		{include file='AspenEvents/registrationToggleButton.tpl' eventSourceId=$event.sourceId registrationAction=$event.registrationAction userWaitingListPosition=$event.userWaitingListPosition}
	</section>
{/strip}
