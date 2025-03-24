<form id="copyEventForm" class="form-horizontal" role="form">
	<input type="hidden" name="eventId" id="eventId" value="{$eventId}"/>
	<div class="form-group col-xs-12">
		<label for="eventName" class="control-label">{translate text="Name for New Event" isAdminFacing=true}</label>
		{if empty($eventTitle)}
			<input type="text" id="eventName" name="eventName" class="form-control">
		{else}
			<input type="text" id="eventName" name="eventName" class="form-control" value="{$eventTitle}" readonly>
		{/if}
		<label for="eventLocation" class="control-label">{translate text="Location for New Event" isAdminFacing=true}</label>
		<select id="eventLocation" name="eventLocation" class="form-control" onchange="AspenDiscovery.Events.getEventTypesForLocation(this.value)">
			{foreach from=$locationList item=id key=location}
				<option value="{$id}">{$location}</option>
			{/foreach}
		</select>
		<label for="sublocationIdSelect" class="control-label">{translate text="Sublocation" isAdminFacing=true}</label>
		<select id="sublocationIdSelect" name="eventSublocation" class="form-control">
			{foreach from=$sublocationList key=id item=sublocation}
				<option value="{$id}">{$sublocation}</option>
			{/foreach}
		</select>
		<label for="eventDate" class="control-label">{translate text="Date for New Event" isAdminFacing=true}</label>
		<input type="date" id="eventDate" name="eventDate" class="form-control" min="{$smarty.now|date_format:"%Y-%m-%d"}">
		<span class="help-block">
			<small>
				<i class="fas fa-info-circle"></i>
				{translate text="If this is a repeating event, please edit the copy to set the correct dates. They will not be created automatically." isAdminFacing=true}
			</small>
		</span>
	</div>
</form>
