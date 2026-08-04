{* Shared capacity display for events. Pass compact=true for modal/inline style *}
<strong>{translate text="Capacity" isAdminFacing=true}:</strong>{" "}
{if $numberOfSeats}
	{$registrationCount} / {$numberOfSeats}
	{if !$compact}
		{if $availableSeats == 0}
			<span class="label label-danger">{translate text="Full" isAdminFacing=true}</span>
		{elseif $availableSeats < 5}
			<span class="label label-warning">{$availableSeats} {translate text="seats left" isAdminFacing=true}</span>
		{/if}
	{else}
		({$availableSeats} {translate text="available" isAdminFacing=true})
	{/if}
{else}
	{$registrationCount} {translate text="registered (unlimited)" isAdminFacing=true}
{/if}
