<div class = "content">
	{if $renew_message_data.Unrenewed === 0 && $renew_message_data.success}
		<div class="alert alert-success">All items were renewed successfully.</div>
	{elseif $renew_message_data.Unrenewed > 0}
		<div class="alert alert-warning"><strong>{$renew_message_data.Renewed} of {$renew_message_data.Total}</strong> items were renewed successfully.</div>
			{foreach from=$renew_message_data.message item=msg}
				<div class="alert alert-danger">{$msg}</div>
			{/foreach}
	{else}
		<div class="alert alert-danger">{$renew_message_data.message}</div>
	{/if}
	<p>
	Please take note of the new due date/s and return any items that could not be renewed. Items on Hold for another patron cannot be renewed.
	</p>
</div>
