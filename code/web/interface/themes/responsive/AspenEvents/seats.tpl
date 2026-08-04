{strip}
	<div class="alert {if $availableSeats > 0}alert-info{else}alert-danger{/if}" style="margin-bottom: 10px;">
		{if $isEventFull}
			{translate text='This event is full. No seats available.' isPublicFacing=true}
		{elseif $numberOfSeats === null}
			{translate text='Seats Available' isPublicFacing=true}
		{elseif $availableSeats == 1}
			{translate text='1 Seat Remaining' isPublicFacing=true}
		{else}
			{translate text='%1% Seats Remaining' 1=$availableSeats isPublicFacing=true}
		{/if}
	</div>
{/strip}
