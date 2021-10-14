<div class = "content">
	{if $renew_message_data.NotRenewed === 0 && $renew_message_data.success}
		<div class="alert alert-success">{translate text="All items were renewed successfully." isPublicFacing=true}</div>
	{elseif $renew_message_data.NotRenewed > 0}
		<div class="alert alert-warning">{translate text="%1% of %2% items were renewed successfully." 1=$renew_message_data.Renewed 2=$renew_message_data.Total isPublicFacing=true}</div>
		{foreach from=$renew_message_data.message item=msg}
			<div class="alert alert-danger">{$msg}</div>
		{/foreach}
	{else}
		{if is_array($renew_message_data.message)}
            {foreach from=$renew_message_data.message item=msg}
				<div class="alert alert-danger">{$msg}</div>
            {/foreach}
		{else}
			<div class="alert alert-danger">{$renew_message_data.message}</div>
		{/if}
	{/if}
	{if !empty($renewResults.Total)}
		<p>
		{translate text="Please take note of the new due date/s and return any items that could not be renewed. Items on Hold for another patron cannot be renewed." isPublicFacing=true}
		</p>
	{/if}
</div>
