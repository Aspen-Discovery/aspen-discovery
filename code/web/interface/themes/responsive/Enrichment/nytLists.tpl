{strip}
	<h1>{translate text="Lists based on the New York Times API"}</h1>
	{if !empty($error)}
		<div class="alert alert-danger">{$error}</div>
	{/if}

	{if $successMessage}
		<div class="alert alert-info">{$successMessage}</div>
	{/if}

	<h2>{translate text="Create or Update a List"}</h2>

	<form action="" method="post" id="buildList">
		<div class="form-group">
		<label for="selectedList">Pick a New York Times list to build a list for in Aspen: </label>
		<!-- Give the user a list of all available lists from NYT -->
		<select name="selectedList" id="selectedList" class="form-control">
		{foreach from=$availableLists->results item="listInfo"}
			<option value="{$listInfo->list_name_encoded}" {if !empty($selectedListName) && ($selectedListName == $listInfo->list_name_encoded)}selected="selected"{/if}>{$listInfo->display_name} (Published {$listInfo->newest_published_date|date_format})</option>
		{/foreach}
		</select>
		</div>
		{*<input type="hidden" name="existingListId" id="existingListId" value="">*}
		<button type="submit" name="submit" class="btn btn-primary">Create/Update List</button>
	</form>

	{if !empty($existingLists)}
		<h2>Existing New York Times Lists</h2>
		<table class="table table-bordered table-hover">
			<tr>
				<th>
					Name
				</th>
				<th>Last Updated</th>
			</tr>
			{foreach from=$existingLists item="existingList"}
				<tr>
					<td>
						<a href="/MyAccount/MyList/{$existingList->id}">{$existingList->title} ({$existingList->numValidListItems()})</a>
					</td>
					<td>
						{$existingList->dateUpdated|date_format}
					</td>
				</tr>
			{/foreach}
		</table>
	{/if}
{/strip}