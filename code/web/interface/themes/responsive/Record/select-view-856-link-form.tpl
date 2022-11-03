{strip}
<form enctype="multipart/form-data" name="view856" id="view856" method="post" onsubmit="return AspenDiscovery.Record.view856Link();">
	<input type="hidden" name="id" id="id" value="{$id}"/>
	<div class="form-group">
		<div class="form-group">
			<label for="selected856Link">
				{translate text="Select a link to view" isPublicFacing=true}
			</label>
			<select name="selected856Link" id="selected856Link" class="form-control">
				{foreach from=$validUrls item=urlInfo key=id}
					<option value="{$id}">{$urlInfo.label}</option>
				{/foreach}
			</select>
		</div>
	</div>
</form>
{/strip}