{strip}
<div class="contents">
	{if $hold_message_data.showItemForm}
	<form action='{$path}/MyAccount/HoldItems' method="POST">
		<input type='hidden' name='pickupBranch' value='{$hold_message_data.pickupBranch}' />
	{/if}
	{if $hold_message_data.error}
		<div class='alert alert-danger'>{$hold_message_data.error}</div>
	{else}
		{if $hold_message_data.successful == 'all'}
			<div class='alert alert-success'>
			{if count($hold_message_data.titles) > 1}
				All hold requests were successful.
			{else}
				Your hold request was successful.
			{/if}
			</div>
		{elseif $hold_message_data.successful == 'partial'}
			<div class='alert alert-warning'>Some hold requests need additional information.</div>
		{else}
			<div class='alert alert-warning'>Your hold request{if count($hold_message_data.titles) > 1}s{/if} need{if count($hold_message_data.titles) <=1 }s{/if} additional information.</div>
		{/if}
	{/if}
		<ol class='hold_result_details'>
			{foreach from=$hold_message_data.titles item=title_data}
			<li class='title_hold_result'>
				<span class='hold_result_item_title'> {if $title_data.title} {$title_data.title} {else} {$title_data.bid} {/if} </span> <br />
				<span class='{if $title_data.success == true}hold_result_title_ok{else}hold_result_title_failed{/if}'>{$title_data.message}</span>
				{if $title_data.items}
					<select name="title[{$title_data.bid}]">
						{if !array_key_exists($title_data.items, -1)}
							<option class='hold_item' value="-1">Select an item</option>
						{/if}
						{foreach from=$title_data.items item=item_data}
							<option class='hold_item' value="{$item_data.itemNumber}">{$item_data.location}- {$item_data.callNumber} - {$item_data.status}</option>
						{/foreach}
					</select>
				{/if}
			</li>
			{/foreach}
		</ol>
		{if $hold_message_data.showItemForm} <input type='submit' value='Place Item Holds' />
	</form>
		{/if}
	<div class='hold_result_notes'>It may take up to 45 seconds for new holds to appear on your account.</div>
</div>
{/strip}
