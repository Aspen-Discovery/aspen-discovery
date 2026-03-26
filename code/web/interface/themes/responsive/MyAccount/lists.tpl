{strip}
	{if !empty($accountMessages)}
		{include file='systemMessages.tpl' messages=$accountMessages}
	{/if}
	<h1>{translate text="Your Lists" isPublicFacing=true}</h1>
	{if !empty($listGroups)}
		<div class="row">
			<div class="form-group">
				<div class="col-xs-12" style="padding-bottom:.5em;">
					<select id="listGroupSelect" name="listGroupSelect" class="form-control" onchange="return AspenDiscovery.Account.loadListGroupData();">
	 	 	 	 	 	{foreach from=$listGroups item=listGroup}
							<option value="{$listGroup->id}" {if $listGroup->id == $activeListGroupDetails->id}selected{/if}>{$listGroup->title|escape:"html"} ({$listGroup->numValidLists()})</option>
	 	 	 	 	 	{/foreach}
						<option value="-1"{if $activeListGroupDetails->id == -1} selected="selected"{/if}>{translate text="Unassigned Lists" isPublicFacing=true} ({$numUnassignedLists})</option>
					</select>
				</div>
			</div>
		</div>
		<h2 id="activeListGroupTitle">{$activeListGroupDetails->title}</h2>
	 	<div class="row">
	 	 	{if $groupId != -1}
			<div class="col-xs-12">
				<div class="btn-toolbar" role="toolbar" style="padding-bottom:.5em;">
					<div class="btn-group btn-group-sm">
						<button class="btn btn-default" onclick="return AspenDiscovery.Account.showEditListGroupNameForm('{$activeListGroupDetails->id}')">{translate text="Rename List Group" isPublicFacing=true}</button>
						<button class="btn btn-default" onclick="return AspenDiscovery.Account.showEditListGroupParentForm('{$activeListGroupDetails->id}', '{$activeListGroupDetails->parentGroupId}')">{translate text="Move List Group" isPublicFacing=true}</button>
						<button class="btn btn-warning" onclick="return AspenDiscovery.Account.showDeleteListGroupForm('{$activeListGroupDetails->id}')">{translate text="Delete List Group" isPublicFacing=true}</button>
					</div>
				</div>
			</div>
	 	 	{/if}
			<div class="col-xs-12">
				<div style="padding-bottom:1em;">
                    {if !empty($userCanTransfer) && $userCanTransfer}
	                    <button class="btn btn-sm btn-default" onclick="return AspenDiscovery.Lists.listGroupTransferAction({$activeListGroupDetails->id})">{translate text='Transfer List Group' isPublicFacing=true}</button>
	                    <button class="btn btn-sm btn-default" onclick="return AspenDiscovery.Lists.listsTransferAction()">{translate text='Transfer All Lists' isPublicFacing=true}</button>
                    {/if}
					<button class="btn btn-sm btn-default" onclick="return AspenDiscovery.Account.showCreateListForm(undefined, undefined, '{$activeListGroupDetails->id}')">{translate text="Create a New List" isPublicFacing=true}</button>
					<button class="btn btn-default btn-sm" onclick="return AspenDiscovery.Account.showCreateListGroupForm('{$activeListGroupDetails->id}')">{translate text="Create a New List Group" isPublicFacing=true}</button>
					{if !empty($showConvertListsFromClassic)}
						<a href="/MyAccount/ImportListsFromClassic" class="btn btn-sm btn-default">{translate text="Import From Old Catalog" isPublicFacing=true}</a>
					{/if}
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-xs-12">
				{if empty($activeListGroup)}
					<div class="alert alert-info">
						{translate text="There are no lists in this group." isPublicFacing=true}
					</div>
				{else}
	 	 	 	 	{foreach from=$activeListGroup item=list key="resultIndex"}
 	 	 	 	 	 	<div class="row">
 	 	 	 	 	 	 	{include file='MyAccount/listDetails.tpl' list=$list}
 	 	 	 	 	 	</div>
 	 	 	 	 	{/foreach}
				{/if}
			</div>
		</div>
	{else}
 	 	{if empty($lists)}
			<div class="alert alert-info">
 	 	 	 	{translate text="You have not created any lists yet." isPublicFacing=true}
			</div>
			<div class="row">
				<div class="col-xs-12">
					<div class="btn-toolbar">
						<button class="btn btn-sm btn-default" onclick="return AspenDiscovery.Account.showCreateListForm()">{translate text="Create a New List" isPublicFacing=true}</button>
 	 	 	 	 	 	{if !empty($showConvertListsFromClassic)}
							<a class="btn btn-sm btn-default" onclick="return AspenDiscovery.Lists.importListsFromClassic();">{translate text="Import From Old Catalog" isPublicFacing=true}</a>
 	 	 	 	 	 	{/if}
					</div>
				</div>
			</div>

 	 	{else}
			<div class="row">
				<select id="results-sort" name="sort" aria-label="{translate text='Sort' isPublicFacing=true}" onchange="document.location.href = this.options[this.selectedIndex].value;" class="input-medium">
					<option value="?sort=title"{if $sortedBy == "title"} selected="selected"{/if}>{translate text='Sort by Title' isPublicFacing=true}</option>
					<option value="?sort=created"{if $sortedBy == "created"} selected="selected"{/if}>{translate text='Sort by Most Recently Created' isPublicFacing=true}</option>
					<option value="?sort=dateUpdated"{if $sortedBy == "dateUpdated"} selected="selected"{/if}>{translate text='Sort by Most Recently Updated' isPublicFacing=true}</option>
				</select>

				<div id="selected-browse-label">
					<div class="btn-group" id="hideSearchCoversSwitch"{if $displayMode != 'list'} style="display: none;"{/if}>
						<label for="hideCovers" class="checkbox{* control-label*}"> {translate text='Hide Covers' isPublicFacing=true}
							<input id="hideCovers" type="checkbox" onclick="AspenDiscovery.Account.toggleShowCovers(!$(this).is(':checked'))" {if $showCovers == false}checked="checked"{/if}>
						</label>
					</div>
				</div>
			</div>
			<div class="row" style="margin-bottom: 20px;">
				<div class="col-xs-12">
					<div class="btn-toolbar">
                        {if !empty($userCanTransfer) && $userCanTransfer}
							<button class="btn btn-sm btn-default" onclick="return AspenDiscovery.Lists.listsTransferAction({$listOwnerId})">{translate text='Transfer All Lists' isPublicFacing=true}</button>
                        {/if}
						<button class="btn btn-sm btn-default" onclick="return AspenDiscovery.Account.showCreateListForm()">{translate text="Create a New List" isPublicFacing=true}</button>
 	 	 	 	 	 	{if count($lists) > 0}
							<button id="deleteSelectedListsBtn" onclick="return AspenDiscovery.Account.deleteSelectedLists()" class="btn btn-sm btn-danger" disabled>{translate text="Delete Selected Lists" isPublicFacing=true}</button>
 	 	 	 	 	 	{/if}
						<button class="btn btn-sm btn-default" onclick="return AspenDiscovery.Account.showCreateListGroupForm('-1')">{translate text="Create a New List Group" isPublicFacing=true}</button>
 	 	 	 	 	 	{if !empty($showConvertListsFromClassic)}
							<a class="btn btn-sm btn-default" onclick="return AspenDiscovery.Lists.importListsFromClassic();">{translate text="Import From Old Catalog" isPublicFacing=true}</a>
 	 	 	 	 	 	{/if}
					</div>
				</div>
			</div>

 	 	 	{foreach from=$lists item="list" key="resultIndex"}
				<div class="row">
					<div class="selectList col-xs-12 col-sm-1">
						<input type="checkbox" name="selected[{$list->id}]" class="listSelect" id="selected{$list->id}" onchange="$('#deleteSelectedListsBtn').prop('disabled', $('.listSelect:checked').length === 0);">
					</div>
 	 	 	 	 	{include file='MyAccount/listDetails.tpl' list=$list}
				</div>
 	 	 	{/foreach}
 	 	 	{if !empty($pageLinks.all)}<div class="pagination">{$pageLinks.all}</div>{/if}
 	 	{/if}
	{/if}
{/strip}
