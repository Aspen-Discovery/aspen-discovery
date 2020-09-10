{strip}
	<div class="row">
		<div class="col-xs-12">
			<form action="/MyAccount/MyList/{$userList->id}" id="myListFormHead">
				<div>
					<input type="hidden" name="myListActionHead" id="myListActionHead" class="form">
					<h1 id="listTitle">{$userList->title|escape:"html"}</h1>
					{if $notes}
						<div id="listNotes">
						{foreach from=$notes item="note"}
							<div class="listNote">{$note}</div>
						{/foreach}
						</div>
					{/if}

					{if $userList->deleted == 1}
						<p class="alert alert-danger">{translate text='Sorry, this list has been deleted.'}</p>
					{else}
						{if $userList->getCleanDescription()}<div class="listDescription text-left" id="listDescription">{$userList->getCleanDescription()}</div>{/if}
						{if $allowEdit}
							<div id="listEditControls" style="display:none" class="collapse">
								<div class="form-group">
									<label for="listTitleEdit" class="control-label">{translate text="Title"}</label>
									<input type="text" id="listTitleEdit" name="newTitle" value="{$userList->title|escape:"html"}" maxlength="255" size="80" class="form-control">
								</div>
								<div class="form-group">
									<label for="listDescriptionEdit" class="control-label">{translate text="Description"}</label>&nbsp;
									<textarea name="newDescription" id="listDescriptionEdit" rows="3" cols="80" class="form-control">{$userList->getCleanDescription()|escape:"html"}</textarea>
								</div>
								<div class="form-group">
									<label for="public" class="col-sm-3 control-label">{translate text="Access"}</label>
									<div class="col-sm-9">
										<input type='checkbox' name='public' id='public' data-on-text="Public" data-off-text="Private" {if $userList->public == 1}checked{/if} {if in_array('Include Lists In Search Results', $userPermissions)}onchange="if($(this).prop('checked') === true){ldelim}$('#searchableRow').show(){rdelim}else{ldelim}$('#searchableRow').hide(){rdelim}"{/if}/>
										<div class="form-text text-muted">
											<small>{translate text="nonindexed_public_list_description" defaultText="Public lists can be shared with other people by copying the URL of the list or using the Email List button when viewing the list."}</small>
										</div>
									</div>
								</div>
								{if in_array('Include Lists In Search Results', $userPermissions)}
									<div class="form-group" id="searchableRow" {if $userList->public == 0}style="display: none"{/if}>
										<label for="searchable" class="col-sm-3 control-label">{translate text="Show in search results"}</label>
										<div class="col-sm-9">
											<input type='checkbox' name='searchable' id='searchable' data-on-text="Yes" data-off-text="No" {if $userList->searchable == 1}checked{/if}/>
											<div class="form-text text-muted">
												<small>{translate text="searchable_list_description" defaultText="If enabled, this list can be found by searching user lists. It must have at least 3 titles to be shown."}</small>
											</div>
										</div>
									</div>
								{/if}
							</div>
							<script type="text/javascript">{literal}
								$(document).ready(function(){
									$('#public').bootstrapSwitch();
									$('#searchable').bootstrapSwitch();
								});
							{/literal}</script>
						{/if}
						<div class="clearer"></div>
						<div id="listTopButtons" class="btn-toolbar">
							{if $allowEdit}
								<div class="btn-group btn-group-sm">
									<button value="editList" id="FavEdit" class="btn btn-sm btn-info listViewButton" onclick="return AspenDiscovery.Lists.editListAction()">{translate text='Edit'}</button>
								</div>
								<div class="btn-group btn-group-sm">
									<button value="saveList" id="FavSave" class="btn btn-sm btn-primary listEditButton" style="display:none" onclick='return AspenDiscovery.Lists.updateListAction()'>{translate text='Update'}</button>
									<button value="cancelEditList" id="cancelEditList" class="btn btn-sm btn-default listEditButton" style="display:none" onclick='return AspenDiscovery.Lists.cancelEditListAction()'>{translate text='Cancel'}</button>
								</div>
								<div class="btn-group btn-group-sm">
									<button value="batchAdd" id="FavBatchAdd" class="btn btn-sm btn-default listViewButton" onclick='return AspenDiscovery.Lists.batchAddToListAction({$userList->id})'>{translate text='Add Multiple Titles'}</button>
									{if $userList->public == 1}
										{if $loggedIn && (in_array('Administer All Collection Spotlights', $userPermissions) || in_array('Administer Library Collection Spotlights', $userPermissions))}
											&nbsp;&nbsp;<a href="#" class="button btn btn-sm btn-default listViewButton" id="FavCreateSpotlight" onclick="return AspenDiscovery.CollectionSpotlights.createSpotlightFromList('{$userList->id}')">{translate text='Create Spotlight'}</a>
										{/if}
										{if $loggedIn && (in_array('Administer All Browse Categories', $userPermissions) || in_array('Administer Library Browse Categories', $userPermissions))}
											<a href="#" id="FavHome" class="btn btn-sm btn-default listViewButton" onclick="return AspenDiscovery.Lists.addToHomePage('{$userList->id}')">{translate text='Add To Browse'}</a>
										{/if}
									{/if}
								</div>
							{/if}
							<div class="btn-group btn-group-sm">
								<button value="emailList" id="FavEmail" class="btn btn-sm btn-default listViewButton" onclick='return AspenDiscovery.Lists.emailListAction("{$userList->id}")'>{translate text='Email List'}</button>
								<button value="printList" id="FavPrint" class="btn btn-sm btn-default listViewButton" onclick='return AspenDiscovery.Lists.printListAction()'>{translate text='Print List'}</button>
								<button value="citeList" id="FavCite" class="btn btn-sm btn-default listViewButton" onclick='return AspenDiscovery.Lists.citeListAction("{$userList->id}")'>{translate text='Generate Citations'}</button>

								<div class="btn-group" role="group">
									<button type="button" class="btn btn-sm btn-default btn-info dropdown-toggle listViewButton" data-toggle="dropdown" aria-expanded="false">{translate text='Sort by'}&nbsp;<span class="caret"></span></button>
									<ul class="dropdown-menu dropdown-menu-right" role="menu">
										{foreach from=$sortList item=sortData}
											<li>
												<a{if !$sortData.selected} href="{$sortData.sortUrl|escape}"{/if}> {* only add link on un-selected options *}
													{translate text=$sortData.desc}
													{if $sortData.selected} <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>{/if}
												</a>
											</li>
										{/foreach}
									</ul>
								</div>

							</div>
							{if $allowEdit}
								<div class="btn-group btn-group-sm">
									<button value="deleteList" id="FavDelete" class="btn btn-sm btn-danger listViewButton" onclick='return AspenDiscovery.Lists.deleteListAction();'>{translate text='Delete'}</button>
								</div>
							{/if}
						</div>
					{/if}
				</div>
			</form>
		</div>
	</div>

	{if $userList->deleted == 0}
		{if $resourceList}
			<div class="row">
				<div class="col-xs-12">
					<form class="navbar form-inline">
						<label for="pageSize" class="control-label">{translate text='Records Per Page'}</label>&nbsp;
						<select id="pageSize" class="pageSize form-control-sm" onchange="AspenDiscovery.changePageSize()">
							<option value="20"{if $recordsPerPage == 20} selected="selected"{/if}>20</option>
							<option value="40"{if $recordsPerPage == 40} selected="selected"{/if}>40</option>
							<option value="60"{if $recordsPerPage == 60} selected="selected"{/if}>60</option>
							<option value="80"{if $recordsPerPage == 80} selected="selected"{/if}>80</option>
							<option value="100"{if $recordsPerPage == 100} selected="selected"{/if}>100</option>
						</select>
						<label for="hideCovers" class="control-label checkbox pull-right"> {translate text='Hide Covers'} <input id="hideCovers" type="checkbox" onclick="AspenDiscovery.Account.toggleShowCovers(!$(this).is(':checked'))" {if $showCovers == false}checked="checked"{/if}></label>
					</form>

					{if $allowEdit && $userSort}
						<div class="alert alert-info alert-dismissible" role="alert">
							<button type="button" class="close" data-dismiss="alert" aria-label="{translate text='Close' inAttribute=true}"><span aria-hidden="true">&times;</span>{translate text='Close'}</button>
							<strong>Drag-and-Drop!</strong> Just drag the list items into the order you like.
						</div>
					{/if}
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
			{if $userSort}
				<script type="text/javascript">
					{literal}
					$(function(){
						$('#UserList').sortable({
							start: function(e,ui){
								$(ui.item).find('.related-manifestations').fadeOut()
							},
							stop: function(e,ui){
								$(ui.item).find('.related-manifestations').fadeIn()
							},
							update: function (e, ui){
								let updates = [];
								let firstItemOnPage = {/literal}{$recordStart}{literal};
								$('#UserList .listEntry').each(function(currentOrder){
									let id = $(this).data('list_entry_id');
									let originalOrder = $(this).data('order');
									let change = currentOrder+firstItemOnPage-originalOrder;
									let newOrder = originalOrder+change;
									if (change !== 0) updates.push({'id':id, 'newOrder':newOrder});
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
												let listEntry = $('#listEntry'+ e.id);
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
			{translate text='You do not have any saved resources'}
		{/if}
	{/if}
{/strip}
