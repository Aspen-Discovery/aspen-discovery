{strip}
	<form enctype="multipart/form-data" name="viewItem" id="viewItem" method="post" onsubmit="return AspenDiscovery.Record.viewItemLink({$variationId});">
		<input type="hidden" name="id" id="id" value="{$id}"/>
		<div class="form-group">
			<div class="form-group">
				<label for="selectedItem">
                    {translate text="Select a link to view" isPublicFacing=true}
				</label>
				<select name="selectedItem" id="selectedItem" class="form-control">
                    {foreach from=$items item=item}
						<option value="{$item->itemId}">{$item->shelfLocation}</option>
                    {/foreach}
				</select>
			</div>
		</div>
	</form>
{/strip}