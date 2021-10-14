<form class="form-horizontal" id="save-to-list-form">
	<div>
		<input type="hidden" name="submit" value="1">
		<input type="hidden" name="sourceId" id="sourceId" value="{$sourceId|escape}">
		<input type="hidden" name="source" id="source" value="{$source|escape}">
		{if !empty($containingLists)}
			<p>
			{translate text='This item is already part of the following list/lists' isPublicFacing=true}<br>
			{foreach from=$containingLists item="list"}
				<a href="/MyAccount/MyList/{$list.id}">{$list.title|escape:"html"}</a><br>
			{/foreach}
			</p>
		{/if}

		{* Only display the list drop-down if the user has lists that do not contain
		 this item OR if they have no lists at all and need to create a default list *}
		{if (!empty($nonContainingLists) || (empty($containingLists) && empty($nonContainingLists))) }
			{assign var="showLists" value="true"}
		{/if}

		{if $showLists}
			<div class="form-group">
				<label for="addToList-list" class="col-sm-3">{translate text='Choose a List' isPublicFacing=true}</label>
				<div class="col-sm-9">
					<select name="list" id="addToList-list" class="form-control form-control-sm">
						{foreach from=$nonContainingLists item="list"}
							<option value="{$list.id}" {if $list.selected}selected{/if}>{$list.title|escape:"html"}</option>
						{foreachelse}
							<option value="">{translate text='My Favorites' isPublicFacing=true}</option>
						{/foreach}
					</select>
					&nbsp;or&nbsp;
					<button class="btn btn-sm btn-default" onclick="return AspenDiscovery.Account.showCreateListForm('{$source|escape:"url"}', '{$sourceId|escape:"url"}')">{translate text="Create a New List" isPublicFacing=true}</button>
				</div>
			</div>
		{else}
			<button class="btn btn-sm btn-default" onclick="return AspenDiscovery.Account.showCreateListForm('{$source|escape:"url"}', '{$sourceId|escape:"url"}')">{translate text="Create a New List" isPublicFacing=true}</button>
		{/if}

		{if $showLists}
			<div class="form-group">
				<label for="addToList-notes" class="col-sm-3">{translate text='Add a Note' isPublicFacing=true}</label>
				<div class="col-sm-9">
					<textarea name="notes" rows="3" cols="50" class="form-control" id="addToList-notes"></textarea>
				</div>
			</div>

		{/if}
	</div>
</form>
