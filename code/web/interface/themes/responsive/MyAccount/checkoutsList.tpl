{if $transList}
	<form id="renewForm_{$source}" action="/MyAccount/CheckedOut">
		<div id="pager" class="row">
			<div class="col-xs-6 form-inline">
				<label for="accountSort_{$source}" class="control-label">{translate text='Sort by'}&nbsp;</label>
				<select name="accountSort" id="accountSort_{$source}" class="form-control" onchange="AspenDiscovery.Account.loadCheckouts('{$source}', $('#accountSort_{$source} option:selected').val(), !$('#hideCovers_{$source}').is(':checked'));">
					{foreach from=$sortOptions item=sortDesc key=sortVal}
						<option value="{$sortVal}"{if $defaultSortOption == $sortVal} selected="selected"{/if}>{translate text=$sortDesc}</option>
					{/foreach}
				</select>
			</div>
			<div class="col-xs-6">
				<label for="hideCovers_{$source}" class="control-label checkbox pull-right"> {translate text="Hide Covers"} <input id="hideCovers_{$source}" type="checkbox" onclick="AspenDiscovery.Account.loadCheckouts('{$source}', $('#accountSort_{$source} option:selected').val(), !$('#hideCovers_{$source}').is(':checked'));" {if $showCovers == false}checked="checked"{/if}></label>
			</div>
		</div>

		{if count($transList) > 1 && ($source=='all' || $source=='ils')}
			<div class="row">
				<div class="col-xs-12">
					<label for="selectAll_{$source}" class="control-label checkbox"> {translate text="Select/Deselect All"} <input id="selectAll_{$source}" type="checkbox" onclick="$('#renewForm_{$source} .titleSelect').prop('checked', $('#selectAll_{$source}').is(':checked'));"></label>
				</div>
			</div>
		{/if}

		<div class="striped">
			{foreach from=$transList item=checkedOutTitle name=checkedOutTitleLoop key=checkedOutKey}
				{if $checkedOutTitle->type == 'ils'}
					{include file="MyAccount/ilsCheckedOutTitle.tpl" record=$checkedOutTitle resultIndex=$smarty.foreach.checkedOutTitleLoop.iteration}
				{elseif $checkedOutTitle->type == 'overdrive'}
					{include file="MyAccount/overdriveCheckedOutTitle.tpl" record=$checkedOutTitle resultIndex=$smarty.foreach.checkedOutTitleLoop.iteration}
				{elseif $checkedOutTitle->type == 'hoopla'}
					{include file="MyAccount/hooplaCheckedOutTitle.tpl" record=$checkedOutTitle resultIndex=$smarty.foreach.checkedOutTitleLoop.iteration}
				{elseif $checkedOutTitle->type == 'cloud_library'}
					{include file="MyAccount/cloudLibraryCheckedOutTitle.tpl" record=$checkedOutTitle resultIndex=$smarty.foreach.checkedOutTitleLoop.iteration}
				{elseif $checkedOutTitle->type == 'axis360'}
					{include file="MyAccount/axis360CheckedOutTitle.tpl" record=$checkedOutTitle resultIndex=$smarty.foreach.checkedOutTitleLoop.iteration}
				{else}
					<div class="row">
						{translate text="Unknown record source"} {$checkedOutTitle->type}
					</div>
				{/if}
			{/foreach}
		</div>

		<br/>

		<div class="btn-group">
			{if $renewableCheckouts >= 1}
				{if $source=='all' || $source=='ils'}
					<a href="#" onclick="AspenDiscovery.Account.renewSelectedTitles()" class="btn btn-sm btn-default">{translate text="Renew Selected Items"}</a>
					<a href="#" onclick="AspenDiscovery.Account.renewAll()" class="btn btn-sm btn-default">{translate text="Renew All"}</a>
				{/if}
			{/if}
			<a class="btn btn-sm btn-default" id="exportToExcel" onclick="return AspenDiscovery.Account.exportCheckouts('{$source}', $('#accountSort_{$source} option:selected').val());">{translate text="Export to Excel"}</a>
		</div>
	</form>
{else}
	{translate text='You do not have any items checked out'}.
{/if}
<script type="text/javascript">
    AspenDiscovery.Ratings.initializeRaters();
</script>