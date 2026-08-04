{strip}
	<div id="list{$location}Buttons" class="btn-toolbar row">
		<div class="col-sm-12">
			<div class="btn-group btn-group-sm" role="group">
				<button type="button" class="btn btn-sm btn-default dropdown-toggle listViewButton" data-toggle="dropdown" aria-expanded="false">{translate text='Sort by' isPublicFacing=true}&nbsp;<span class="caret"></span></button>
				<ul class="dropdown-menu dropdown-menu-left" role="menu">
					{foreach from=$sortList item=sortData}
						<li>
							<a{if empty($sortData.selected)} href="{$sortData.sortUrl|escape}"{/if}> {* only add link on un-selected options *}
								{translate text=$sortData.desc isPublicFacing=true}
								{if !empty($sortData.selected)} <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>{/if}
							</a>
						</li>
					{/foreach}
				</ul>
			</div>

			{if !empty($userCanTransfer) && $userCanTransfer}
				&ensp;
				<div class="btn-group btn-group-sm">
					<button value="transferList" id="transferList" class="btn btn-sm btn-default listTransferButton" onclick="return AspenDiscovery.Lists.listTransferAction({$userList->id})">{translate text='Transfer List' isPublicFacing=true}</button>
				</div>
			{/if}

			{if !empty($allowEdit)}
				&ensp;
				<div class="btn-group btn-group-sm">
					<button value="editList" id="FavEdit" class="btn btn-sm btn-default listViewButton" onclick="return AspenDiscovery.Lists.editListAction()">{translate text='Edit' isPublicFacing=true}</button>
				</div>
				<div class="btn-group btn-group-sm">
					<button value="saveList" id="FavSave" class="btn btn-sm btn-primary listEditButton" style="display:none" onclick='return AspenDiscovery.Lists.updateListAction()'>{translate text='Update' isPublicFacing=true}</button>
					<button value="cancelEditList" id="cancelEditList" class="btn btn-sm btn-default listEditButton" style="display:none" onclick='return AspenDiscovery.Lists.cancelEditListAction()'>{translate text='Cancel' isPublicFacing=true}</button>
				</div>
				&ensp;
				<div class="btn-group btn-group-sm">
					<button value="batchAdd" id="FavBatchAdd" class="btn btn-sm btn-default listViewButton" onclick='return AspenDiscovery.Lists.batchAddToListAction({$userList->id})'>{translate text='Add Multiple Titles' isPublicFacing=true}</button>
				</div>
			{/if}

			&ensp;
			<div class="btn-group btn-group-sm">
				<button value="printOptions" id="printOptions" class="btn btn-sm btn-default" onclick='return AspenDiscovery.Lists.getPrintListOptions("{$userList->id}","{$selectedResourceTypes|escape:"javascript"}","{$activeFilters|escape:"javascript"}")'>{translate text='Print' isPublicFacing=true}</button>
			</div>
			&ensp;
			<div class="btn-group btn-group-sm">
				<button type="button" class="btn btn-sm btn-default dropdown-toggle listToolsButton" data-toggle="dropdown" aria-expanded="false">{translate text='List Tools' isPublicFacing=true}&nbsp;<span class="caret"></span></button>
				<ul class="dropdown-menu dropdown-menu-right" role="menu">
					{if !empty($showEmailThis)}
						<li id="FavEmail"><a onclick='return AspenDiscovery.Lists.emailListAction("{$userList->id}","{$selectedResourceTypes|escape:"javascript"}","{$activeFilters|escape:"javascript"}")'>{translate text='Email List' isPublicFacing=true}</a></li>
					{/if}
					<li id="FavCite"><a onclick='return AspenDiscovery.Lists.citeListAction("{$userList->id}","{$selectedResourceTypes|escape:"javascript"}","{$activeFilters|escape:"javascript"}")'>{translate text='Generate Citations' isPublicFacing=true}</a></li>
					<li id="FavExport"><a onclick='return AspenDiscovery.Lists.exportToCSV("{$userList->id}","{$selectedResourceTypes|escape:"javascript"}","{$activeFilters|escape:"javascript"}")'>{translate text='Export to CSV' isPublicFacing=true}</a></li>
					<li id="FavExportRis"><a onclick='return AspenDiscovery.Lists.exportToRIS("{$userList->id}","{$selectedResourceTypes|escape:"javascript"}","{$activeFilters|escape:"javascript"}")'>{translate text='Export to RIS' isPublicFacing=true}</a></li>
					{if !$listHasFiltersApplied && $userList->public == 1 && $loggedIn && (in_array('Administer All Collection Spotlights', $userPermissions) || in_array('Administer Library Collection Spotlights', $userPermissions) || in_array('Administer All Browse Categories', $userPermissions) || in_array('Administer Library Browse Categories', $userPermissions) || in_array('Administer Selected Browse Category Groups', $userPermissions))}
						{if (in_array('Administer All Collection Spotlights', $userPermissions) || in_array('Administer Library Collection Spotlights', $userPermissions))}
							<li id="FavCreateSpotlight"><a href="#" id="FavCreateSpotlight" onclick='return AspenDiscovery.CollectionSpotlights.createSpotlightFromList("{$userList->id}")'>{translate text='Create Spotlight' isAdminFacing=true}</a></li>
						{/if}
						{if (in_array('Administer All Browse Categories', $userPermissions) || in_array('Administer Library Browse Categories', $userPermissions) || in_array('Administer Selected Browse Category Groups', $userPermissions))}
							<li id="FavHome"><a href="#" id="FavHome" onclick='return AspenDiscovery.Lists.addToHomePage("{$userList->id}")'>{translate text='Add To Browse' isAdminFacing=true}</a></li>
						{/if}
					{/if}
				</ul>
			</div>

			{if !empty($allowEdit)}
				&ensp;
				<div class="btn-group btn-group-sm" role="group">
					<button type="button" id="FavDelete" class="btn btn-sm btn-danger dropdown-toggle listViewButton" data-toggle="dropdown" aria-expanded="false">{translate text='Delete' isPublicFacing=true}&nbsp;<span class="caret"></span></button>
					<ul class="dropdown-menu dropdown-menu-right" role="menu">
						<li>
							<a onclick="return AspenDiscovery.Account.deleteSelectedListTitles({$listSelected})">{translate text="Selected Items" isPublicFacing=true}</a>
						</li>
						{if !$listHasFiltersApplied}
							<li>
								<a onclick="return AspenDiscovery.Account.deleteAllListTitles({$listSelected})">{translate text="All Items" isPublicFacing=true}</a>
							</li>
						{/if}
						<li>
							<a onclick="return AspenDiscovery.Lists.deleteListAction();">{translate text="Entire List" isPublicFacing=true}</a>
						</li>
					</ul>
				</div>
			{/if}
		</div>
	</div>
{/strip}
