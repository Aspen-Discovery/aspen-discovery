{strip}
	{* $event here refers to an event instance. *}
	<input type="hidden" id="eventRegistrationUserId-{$event.sourceId}" value="{$userId}">
	{if $event.numberOfSeats !== null}{include file='AspenEvents/seats.tpl' numberOfSeats=$event.numberOfSeats availableSeats=$event.availableSeats isEventFull=$event.isEventFull}{/if}
	<section class="well">
		{include file='AspenEvents/registrationUserSelector.tpl' eventSourceId=$event.sourceId}
		{include file='AspenEvents/registrationUserDetails.tpl' eventSourceId=$event.sourceId}
		{include file='AspenEvents/registrationToggleButton.tpl' eventSourceId=$event.sourceId isRegistered=$event.isRegistered}
	</section>
{/strip}
