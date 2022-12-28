{strip}
<div class="content">
	<form action="/MyAccount/HoldItems" method="POST" class="form">
		<input type="hidden" name="id" id="id" value="{$id}">
		<input type="hidden" name="patronId" id="patronId" value="{$patronId}">
		<input type="hidden" name="pickupBranch" id="pickupBranch" value="{$pickupBranch}">
		<input type="hidden" name="module" id="module" value="{$activeRecordProfileModule}">
		{if !empty($autologout)}{* If user originally chose autologout carry that choice back to server from this form *}
			<input type="hidden" name="autologout" id="autologout" checked> {* checked to preserve checkbox behavior in hold-popup.tpl *}
		{/if}

		{if count($items) == 0}
			<div class="alert alert-danger">{$message}</div>
		{else}
			<div class="alert alert-warning">{translate text="Please select the item you would like to place a hold on." isPublicFacing=true}</div>
			<ol class='hold_result_details'>
				<select id="selectedItem" name="selectedItem" class="form-control">
					{if array_key_exists('-1', $items) == false}
						<option class="hold_item" value="-1">{translate text="Select an item" isPublicFacing=true}</option>
					{/if}
					{foreach from=$items item=item_data}
						<option class="hold_item" value="{$item_data.itemNumber}">
							{$item_data.location}
							{if $item_data.itemType} - {$item_data.itemType} {/if}
							{if $item_data.callNumber} - {$item_data.callNumber} {/if}
							{if $item_data.volInfo} - {$item_data.volInfo} {/if}
							{if $item_data.status} - {$item_data.status}{/if}
						</option>
					{/foreach}
				</select>
			</ol>
		{/if}
	</form>
</div>
{/strip}