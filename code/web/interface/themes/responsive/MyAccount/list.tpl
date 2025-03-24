{strip}

	<div class="row">
		<div class="col-xs-12">
			<form action="/MyAccount/MyList/{$userList->id}" id="myListFormHead">
				<div>
					<input type="hidden" name="myListActionHead" id="myListActionHead" class="form">
					<h1 id="listTitle">{$userList->title|escape:"html"}</h1>
					{if !empty($notes)}
						<div id="listNotes">
						{foreach from=$notes item="note"}
							<div class="listNote">{$note}</div>
						{/foreach}
						</div>
					{/if}

					{if $userList->deleted == 1}
						<p class="alert alert-danger">{translate text='Sorry, this list has been deleted.' isPublicFacing=true}</p>
					{else}
						<div class="row">
							<div class="col-md-6">
								<p class="text-muted">
									<small>{translate text='Created on' isPublicFacing=true}  {$dateCreated}</small>
								</p>
							</div>
							<div class="col-md-6">
								<p class="text-muted">
									<small>{translate text='Last Updated' isPublicFacing=true}  {$dateUpdated}</small>
								</p>
							</div>
						</div>
						{if $userList->getCleanDescription()}<div class="listDescription text-left" id="listDescription">{$userList->getCleanDescription()}</div>{/if}
						{if !empty($userList->nytListModified)}
							<div class="text-left">
								<p class="text-muted">
									<small>{translate text='Last Updated by New York Times on %1%' 1=$userList->nytListModified isPublicFacing=true}</small>
								</p>
							</div>
						{/if}
						{if !empty($allowEdit)}
							<div id="listEditControls" style="display:none" class="collapse">
								<div class="form-group">
									<label for="listTitleEdit" class="control-label">{translate text="Title" isPublicFacing=true}</label>
									{if empty($validListNames)}
										<input type="text" id="listTitleEdit" name="newTitle" value="{$userList->title|escape:"html"}" size="50" class="form-control">
									{else}
										<select id="listTitleEdit" name="titleSelect" class="form-control">
											{foreach from=$validListNames item=listName key=listNameIndex}
												<option value="{$listNameIndex}" {if $userList->title == $listName}selected{/if}>{$listName}</option>
											{/foreach}
										</select>
									{/if}
								</div>
								{if !empty($enableListDescriptions)}
									<div class="form-group">
										<label for="listDescriptionEdit" class="control-label">{translate text="Description" isPublicFacing=true}</label>&nbsp;
										<textarea name="newDescription" id="listDescriptionEdit" rows="3" cols="80" class="form-control">{$userList->getCleanDescription()|escape:"html"}</textarea>
									</div>
								{/if}
								<div class="form-group">
									<label for="public" class="col-sm-3 control-label">{translate text="Access" isPublicFacing=true}</label>
									<div class="col-sm-9">
										<input type='checkbox' name='public' id='public' data-on-text="Public" data-off-text="Private" {if $userList->public == 1}checked{/if} {if in_array('Include Lists In Search Results', $userPermissions)}onchange="if($(this).prop('checked') === true){ldelim}$('#searchableRow').show(){rdelim}else{ldelim}$('#searchableRow').hide(){rdelim}"{/if}/>
										<div class="form-text text-muted">
											<small>{translate text="Public lists can be shared with other people by copying the URL of the list or using the Email List button when viewing the list." isPublicFacing=true}</small>
										</div>
									</div>
								</div>
								{if in_array('Include Lists In Search Results', $userPermissions)}
									<div class="form-group" id="searchableRow" {if $userList->public == 0}style="display: none"{/if}>
										<label for="searchable" class="col-sm-3 control-label">{translate text="Show in search results" isPublicFacing=true}</label>
										<div class="col-sm-9">
											<input type='checkbox' name='searchable' id='searchable' data-on-text="Yes" data-off-text="No" {if $userList->searchable == 1}checked{/if}/>
											<div class="form-text text-muted">
												<small>{translate text="If enabled, this list can be found by searching user lists. It must have at least 3 titles to be shown." isPublicFacing=true}</small>
											</div>
										</div>
									</div>
								{/if}
								{if in_array('Include Lists In Search Results', $userPermissions)}
								<div class="form-group" id="displayListAuthorRow" {if $userList->public == 0}style="display: none"{/if}>
									<label for="displayListAuthor" class="col-sm-3 control-label">{translate text="Show list author in search results" isPublicFacing=true}</label>
									<div class="col-sm-9">
										<input type='checkbox' name='displayListAuthor' id='displayListAuthor' data-on-text="Yes" data-off-text="No" {if $userList->displayListAuthor == 1}checked{/if}/>
										<div class="form-text text-muted">
											<small>{translate text="If enabled, your name will be displayed as the author of this public list." isPublicFacing=true}</small>
										</div>
									</div>
								</div>
							{/if}
								{if in_array('Upload List Covers', $userPermissions)}
									<div class="form-group" id="searchableRow" {if $userList->public == 0}style="display: none"{/if}>
										<label for="searchable" class="col-sm-3 control-label">{translate text="Upload custom list cover" isPublicFacing=true}</label>
										<div class="col-sm-9">
											<button onclick="return AspenDiscovery.Lists.getUploadListCoverForm({$userList->id})" class="btn btn-sm btn-default">{translate text="Upload List Cover from Computer" isPublicFacing=true}</button>
											<button onclick="return AspenDiscovery.Lists.getUploadListCoverFormByURL('{$userList->id}')" class="btn btn-sm btn-default">{translate text="Upload List Cover by URL" isPublicFacing=true}</button>
											{if $hasUploadedCover}
												<button onclick="return AspenDiscovery.Lists.removeUploadedListCover('{$userList->id}')" class="btn btn-sm btn-danger">{translate text="Remove Uploaded Cover" isPublicFacing=true}</button>
											{/if}
										</div>
									</div>
								{/if}
							</div>
							<script type="text/javascript">
							{literal}
								$(document).ready(function(){
									$('#public').bootstrapSwitch();
									$('#searchable').bootstrapSwitch();
									$('#displayListAuthor').bootstrapSwitch();
								});
							{/literal}</script>
						{/if}
						<div id="listTopButtons" class="btn-toolbar row">
							<div class="col-sm-12">
								{if !empty($allowEdit)}
									<div class="btn-group btn-group-sm">
										<button value="editList" id="FavEdit" class="btn btn-sm btn-info listViewButton" onclick="return AspenDiscovery.Lists.editListAction()">{translate text='Edit' isPublicFacing=true}</button>
									</div>
									<div class="btn-group btn-group-sm">
										<button value="saveList" id="FavSave" class="btn btn-sm btn-primary listEditButton" style="display:none" onclick='return AspenDiscovery.Lists.updateListAction()'>{translate text='Update' isPublicFacing=true}</button>
										<button value="cancelEditList" id="cancelEditList" class="btn btn-sm btn-default listEditButton" style="display:none" onclick='return AspenDiscovery.Lists.cancelEditListAction()'>{translate text='Cancel' isPublicFacing=true}</button>
									</div>
									<div class="btn-group btn-group-sm">
										<button value="batchAdd" id="FavBatchAdd" class="btn btn-sm btn-default listViewButton" onclick='return AspenDiscovery.Lists.batchAddToListAction({$userList->id})'>{translate text='Add Multiple Titles' isPublicFacing=true}</button>
									</div>
								{/if}
								{if $userList->public == 1 && $loggedIn && (in_array('Administer All Collection Spotlights', $userPermissions) || in_array('Administer Library Collection Spotlights', $userPermissions) || in_array('Administer All Browse Categories', $userPermissions) || in_array('Administer Library Browse Categories', $userPermissions) || in_array('Administer Selected Browse Category Groups', $userPermissions))}
									<div class="btn-group btn-group-sm">
										{if (in_array('Administer All Collection Spotlights', $userPermissions) || in_array('Administer Library Collection Spotlights', $userPermissions))}
											&nbsp;&nbsp;<a href="#" class="button btn btn-sm btn-default listViewButton" id="FavCreateSpotlight" onclick="return AspenDiscovery.CollectionSpotlights.createSpotlightFromList('{$userList->id}')">{translate text='Create Spotlight' isAdminFacing=true}</a>
										{/if}
										{if (in_array('Administer All Browse Categories', $userPermissions) || in_array('Administer Library Browse Categories', $userPermissions) || in_array('Administer Selected Browse Category Groups', $userPermissions))}
											<a href="#" id="FavHome" class="btn btn-sm btn-default listViewButton" onclick="return AspenDiscovery.Lists.addToHomePage('{$userList->id}')">{translate text='Add To Browse' isAdminFacing=true}</a>
										{/if}
									</div>
								{/if}

								<div class="btn-group btn-group-sm">
									{if !empty($showEmailThis)}
									<button value="emailList" id="FavEmail" class="btn btn-sm btn-default listViewButton" onclick='return AspenDiscovery.Lists.emailListAction("{$userList->id}")'>{translate text='Email List' isPublicFacing=true}</button>
									{/if}
									<button value="printList" id="FavPrint" class="btn btn-sm btn-default listViewButton" onclick='return AspenDiscovery.Lists.printListAction()'>{translate text='Print List' isPublicFacing=true}</button>
									<a id="FavExport" class="btn btn-sm btn-default listViewButton" href="/MyAccount/AJAX?method=exportUserList&listId={$userList->id}">{translate text='Export List to CSV' isPublicFacing=true}</a>
									<a id="FavExportRis" class="btn btn-sm btn-default listViewButton" href="/MyAccount/AJAX?method=exportUserListRIS&listId={$userList->id}">{translate text='Export List to RIS' isPublicFacing=true}</a>
									<button value="citeList" id="FavCite" class="btn btn-sm btn-default listViewButton" onclick='return AspenDiscovery.Lists.citeListAction("{$userList->id}")'>{translate text='Generate Citations' isPublicFacing=true}</button>

									<div class="btn-group" role="group">
										<button type="button" class="btn btn-sm btn-default btn-info dropdown-toggle listViewButton" data-toggle="dropdown" aria-expanded="false">{translate text='Sort by' isPublicFacing=true}&nbsp;<span class="caret"></span></button>
										<ul class="dropdown-menu dropdown-menu-right" role="menu">
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

								</div>
								{if !empty($allowEdit)}
									<div class="btn-group btn-group-sm">
										<button value="deleteList" id="FavDelete" class="btn btn-sm btn-danger listViewButton" onclick='return AspenDiscovery.Lists.deleteListAction();'>{translate text='Delete List' isPublicFacing=true}</button>
									</div>
								{/if}
							</div>
						</div>
					{/if}
				</div>
			</form>
		</div>
	</div>

	{if $userList->deleted == 0}
		{if !empty($resourceList)}
			<div class="row">
				<div class="col-xs-12">
					<form class="navbar form-inline">
						{if $recordCount > 20}
						<label for="pageSize" class="control-label">{translate text='Records Per Page' isPublicFacing=true}</label>&nbsp;
						<select id="pageSize" class="pageSize form-control-sm" onchange="AspenDiscovery.changePageSize()">
							<option value="20"{if $recordsPerPage == 20} selected="selected"{/if}>20</option>
							{if $recordCount > 20}
							<option value="40"{if $recordsPerPage == 40} selected="selected"{/if}>40</option>
							{/if}
							{if $recordCount > 40}
							<option value="60"{if $recordsPerPage == 60} selected="selected"{/if}>60</option>
							{/if}
							{if $recordCount > 60}
							<option value="80"{if $recordsPerPage == 80} selected="selected"{/if}>80</option>
							{/if}
							{if $recordCount > 80}
							<option value="100"{if $recordsPerPage == 100} selected="selected"{/if}>100</option>
							{/if}
						</select>
						{/if}
						<label for="hideCovers" class="control-label checkbox pull-right"> {translate text='Hide Covers' isPublicFacing=true} <input id="hideCovers" type="checkbox" onclick="AspenDiscovery.Account.toggleShowCovers(!$(this).is(':checked'))" {if $showCovers == false}checked="checked"{/if}></label>
					</form>
				</div>
			</div>

			<input type="hidden" name="myListActionItem" id="myListActionItem">
			<div id="UserList">{*Keep only list entries in div for custom sorting functions*}
				{foreach from=$resourceList item=resource name="recordLoop" key=resourceId}
					<div class="result{if ($smarty.foreach.recordLoop.iteration % 2) == 0} alt{/if}">
						{* This is raw HTML -- do not escape it: *}
						{$resource}
					</div>
				{/foreach}
			</div>
			<div class="btn-group">
				{if !empty($listEditAllowed)}
					<button onclick="return AspenDiscovery.Account.deleteSelected({$listSelected})" class="btn btn-sm btn-danger">{translate text="Delete Selected Items" isPublicFacing=true}</button>
					<button onclick="return AspenDiscovery.Account.deleteAll({$listSelected})" class="btn btn-sm btn-danger">{translate text="Delete All Items" isPublicFacing=true}</button>
				{/if}
			</div>
			{if !empty($userSort)}
				<script type="text/javascript">
					{literal}
					$(function(){
						$('#UserList').sortable({
							handle: 'i.fas.fa-arrows-alt-v',
							start: function(e,ui){
								$(ui.item).find('.related-manifestations').fadeOut()
							},
							stop: function(e,ui){
								$(ui.item).find('.related-manifestations').fadeIn()
							},
							update: function (e, ui){
								var updates = [];
								var firstItemOnPage = {/literal}{$recordStart}{literal};
								$('#UserList .listEntry').each(function(currentOrder){
									var id = $(this).data('list_entry_id');
									var originalOrder = $(this).data('order');
									var change = currentOrder+firstItemOnPage-originalOrder;
									var newOrder = originalOrder+change;
									updates.push({'id':id, 'newOrder':newOrder});
								});
								$.getJSON('/MyAccount/AJAX',
									{
										method:'setListEntryPositions'
										,updates:updates
										,listID:{/literal}{$userList->id}{literal}
									}
									, function(response){
										if (response.success) {
											updates.forEach(function(e){
												var listEntry = $('#listEntry'+ e.id);
												if (listEntry.length > 0) {
													listEntry
														.data('order', e.newOrder)
														.find('span.result-index')
														.text(e.newOrder + ')');
												}
											})
										}
									}
								);
							}
						});
					});
					{/literal}
				</script>
			{/if}

			{if strlen($pageLinks.all) > 0}<div class="text-center">{$pageLinks.all}</div>{/if}
		{else}
			{translate text='You do not have any saved resources' isPublicFacing=true}
		{/if}
	{/if}
{/strip}
