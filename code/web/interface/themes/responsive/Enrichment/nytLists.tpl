{strip}
	<h1>{translate text="Lists based on the New York Times API"}</h1>
	{if !empty($error)}
		<div class="alert alert-danger">{$error}</div>
	{/if}

	{if $successMessage}
		<div class="alert alert-info">{$successMessage}</div>
	{/if}

	<h2>{translate text="Create or Update a List" isAdminFacing=true}</h2>

	<form action="" method="post" id="buildList">
		<div class="form-group">
		<label for="selectedList">{translate text="Pick a New York Times list to build a list for in Aspen" isAdminFacing=true}</label>
		<!-- Give the user a list of all available lists from NYT -->
		<select name="selectedList" id="selectedList" class="form-control">
		{foreach from=$availableLists item="listInfo"}
			<option value="{$listInfo->list_name_encoded}" {if !empty($selectedListName) && ($selectedListName == $listInfo->list_name_encoded)}selected="selected"{/if}>{$listInfo->display_name} (Published {$listInfo->newest_published_date|date_format})</option>
		{/foreach}
		</select>
		</div>
		{*<input type="hidden" name="existingListId" id="existingListId" value="">*}
		<button type="submit" name="submit" class="btn btn-primary">{translate text="Create/Update List" isAdminFacing=true}</button>
	</form>

	{if !empty($existingLists)}
		<h2>{translate text="Existing New York Times Lists" isAdminFacing=true}</h2>
		<table class="table table-bordered table-hover">
			<tr>
				<th>{translate text="Name" isAdminFacing=true}</th>
				<th>{translate text="Last Updated" isAdminFacing=true}</th>
				<th>{translate text="Delete?" isAdminFacing=true}</th>
			</tr>
			{foreach from=$existingLists item="existingList"}
				<tr>
					<td>
						<a href="/MyAccount/MyList/{$existingList->id}">{$existingList->title} ({$existingList->numValidListItems()})</a><br>
					</td>
					<td>
						{$existingList->dateUpdated|date_format}<br>
						{if !empty($existingList->nytListModified)}<small>({$existingList->nytListModified})</small>{/if}
					</td>
					<td>
						<button onclick="return AspenDiscovery.Admin.deleteNYTList({$existingList->id})" class="btn btn-danger">{translate text="Delete" isAdminFacing=true}</button>
					</td>
				</tr>
			{/foreach}
		</table>
	{/if}
{/strip}