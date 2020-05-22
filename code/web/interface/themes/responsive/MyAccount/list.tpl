{strip}
	<form action="/MyAccount/MyList/{$userList->id}" id="myListFormHead">
		<div>
			<input type="hidden" name="myListActionHead" id="myListActionHead" class="form">
			<h3 id="listTitle">{$userList->title|escape:"html"}</h3>
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
				{if $userList->getCleanDescription()}<div class="listDescription alignleft" id="listDescription">{$userList->getCleanDescription()}</div>{/if}
				{if $allowEdit}
					<div id="listEditControls" style="display:none" class="collapse">
						<div class="form-group">
							<label for="listTitleEdit" class="control-label">{translate text="Title"}</label>
							<input type="text" id="listTitleEdit" name="newTitle" value="{$userList->title|escape:"html"}" maxlength="255" size="80" class="form-control">
						</div>
						<div class="form-group">
							<label for="listDescriptionEdit" class="control-label">{translate text="Description"}"</label>&nbsp;
							<textarea name="newDescription" id="listDescriptionEdit" rows="3" cols="80" class="form-control">{$userList->getCleanDescription()|escape:"html"}</textarea>
						</div>
					</div>
				{/if}
				<div class="clearer"></div>
				<div id="listTopButtons" class="btn-toolbar">
					{if $allowEdit}
						<div class="btn-group">
							<button value="editList" id="FavEdit" class="btn btn-sm btn-info" onclick="return AspenDiscovery.Lists.editListAction()">{translate text='Edit List'}</button>
						</div>
						<div class="btn-group">
							<button value="saveList" id="FavSave" class="btn btn-sm btn-primary" style="display:none" onclick='return AspenDiscovery.Lists.updateListAction()'>{translate text='Save Changes'}</button>
						</div>
						<div class="btn-group">
							<button value="batchAdd" id="FavBatchAdd" class="btn btn-sm btn-default" onclick='return AspenDiscovery.Lists.batchAddToListAction({$userList->id})'>{translate text='Add Multiple Titles'}</button>
							{if $userList->public == 0}
								<button value="makePublic" id="FavPublic" class="btn btn-sm btn-default" onclick='return AspenDiscovery.Lists.makeListPublicAction()'>{translate text='Make Public'}</button>
							{else}
								<button value="makePrivate" id="FavPrivate" class="btn btn-sm btn-default" onclick='return AspenDiscovery.Lists.makeListPrivateAction()'>{translate text='Make Private'}</button>
								{if $loggedIn && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('contentEditor', $userRoles))}
									&nbsp;&nbsp;<a href="#" class="button btn btn-sm btn-default" id="FavCreateSpotlight" onclick="return AspenDiscovery.CollectionSpotlights.createSpotlightFromList('{$userList->id}')">{translate text='Create Spotlight'}</a>
								{/if}
								{if $loggedIn && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('contentEditor', $userRoles) || array_key_exists('libraryManager', $userRoles) || array_key_exists('locationManager', $userRoles))}
									<a href="#" id="FavHome" class="btn btn-sm btn-default" onclick="return AspenDiscovery.Lists.addToHomePage('{$userList->id}')">{translate text='Add To Browse'}</a>
								{/if}
							{/if}
						</div>
					{/if}
					<div class="btn-group">
						<button value="emailList" id="FavEmail" class="btn btn-sm btn-default" onclick='return AspenDiscovery.Lists.emailListAction("{$userList->id}")'>{translate text='Email List'}</button>
						<button value="printList" id="FavPrint" class="btn btn-sm btn-default" onclick='return AspenDiscovery.Lists.printListAction()'>{translate text='Print List'}</button>
						<button value="citeList" id="FavCite" class="btn btn-sm btn-default" onclick='return AspenDiscovery.Lists.citeListAction("{$userList->id}")'>{translate text='Generate Citations'}</button>

						<div class="btn-group" role="group">
							<button type="button" class="btn btn-sm btn-default btn-info dropdown-toggle" data-toggle="dropdown" aria-expanded="false">{translate text='Sort by'}&nbsp;<span class="caret"></span></button>
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
						<div class="btn-group">
							<button value="deleteList" id="FavDelete" class="btn btn-sm btn-danger" onclick='return AspenDiscovery.Lists.deleteListAction();'>{translate text='Delete List'}</button>
						</div>
					{/if}
				</div>
			{/if}
		</div>
	</form>

	{if $userList->deleted == 0}
		{if $resourceList}
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
