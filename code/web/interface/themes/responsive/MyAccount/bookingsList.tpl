{foreach from=$recordList item=sectionData key=sectionKey}
	<h2>
		{if $sectionKey == 'active'}{translate text="Active Bookings" isPublicFacing=true}
		{elseif $sectionKey == 'past'}{translate text="Past Bookings" isPublicFacing=true}
		{/if}
	</h2>
	{if !is_array($recordList.$sectionKey) || !count($recordList.$sectionKey) > 0}
		{if $sectionKey == 'active'}
			{translate text='You do not have any active bookings.' isPublicFacing=true}
		{/if}
	{else}
		<div class="striped">
			{foreach from=$recordList.$sectionKey item=record name="recordLoop"}
				{include file="MyAccount/ilsBooking.tpl" record=$record section=$sectionKey resultIndex=$smarty.foreach.recordLoop.iteration}
			{/foreach}
		</div>
	{/if}
{/foreach}
