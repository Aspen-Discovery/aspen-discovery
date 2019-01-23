{*{strip}*}
	<form action="{$path}/MyAccount/MyList/{$favList->id}" id="myListFormHead">
		<div>
			<input type="hidden" name="myListActionHead" id="myListActionHead" class="form">
			<h3 id="listTitle">{*<span class="silk list">&nbsp;</span>*}{$favList->title|escape:"html"}</h3>
			{if $notes}
				<div id="listNotes">
				{foreach from=$notes item="note"}
					<div class="listNote">{$note}</div>
				{/foreach}
				</div>
			{/if}

			{if $favList->deleted == 1}
				<p class="alert alert-danger">Sorry, this list has been deleted.</p>
			{else}
				{if $favList->description}<div class="listDescription alignleft" id="listDescription">{$favList->description|escape}</div>{/if}
				{if $allowEdit}
					<div id="listEditControls" style="display:none" class="collapse">
						<div class="form-group">
							<label for="listTitleEdit" class="control-label">Title: </label>
							<input type="text" id="listTitleEdit" name="newTitle" value="{$favList->title|escape:"html"}" maxlength="255" size="80" class="form-control">
						</div>
						<div class="form-group">
							<label for="listDescriptionEdit" class="control-label">Description: </label>&nbsp;
							<textarea name="newDescription" id="listDescriptionEdit" rows="3" cols="80" class="form-control">{$favList->description|escape:"html"}</textarea>
						</div>
						<div class="form-group">

							<label for="defaultSort" class="control-label">Default Sort: </label>
							<select id="defaultSort" name="defaultSort" class="form-control">
								{foreach from=$defaultSortList item=sortValue key=sortLabel}
									<option value="{$sortLabel}"{if $sortLabel == $defaultSort} selected="selected"{/if}>
										{translate text=$sortValue}
									</option>
								{/foreach}
							</select>

						</div>
					</div>
				{/if}
				<div class="clearer"></div>
				<div id="listTopButtons" class="btn-toolbar">
					{if $allowEdit}
						<div class="btn-group">
							<button value="editList" id="FavEdit" class="btn btn-sm btn-info" onclick="return VuFind.Lists.editListAction()">Edit List</button>
						</div>
						<div class="btn-group">
							<button value="saveList" id="FavSave" class="btn btn-sm btn-primary" style="display:none" onclick='return VuFind.Lists.updateListAction()'>Save Changes</button>
						</div>
						<div class="btn-group">
							<button value="batchAdd" id="FavBatchAdd" class="btn btn-sm btn-default" onclick='return VuFind.Lists.batchAddToListAction({$favList->id})'>Add Multiple Titles</button>
							{if $favList->public == 0}
								<button value="makePublic" id="FavPublic" class="btn btn-sm btn-default" onclick='return VuFind.Lists.makeListPublicAction()'>Make Public</button>
							{else}
								<button value="makePrivate" id="FavPrivate" class="btn btn-sm btn-default" onclick='return VuFind.Lists.makeListPrivateAction()'>Make Private</button>
								{if $loggedIn && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('contentEditor', $userRoles))}
									&nbsp;&nbsp;<a href="#" class="button btn btn-sm btn-default" id="FavCreateWidget" onclick="return VuFind.ListWidgets.createWidgetFromList('{$favList->id}')">Create Widget</a>
								{/if}
								{if $loggedIn && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('contentEditor', $userRoles) || array_key_exists('libraryManager', $userRoles) || array_key_exists('locationManager', $userRoles))}
									<a href="#" id="FavHome" class="btn btn-sm btn-default" onclick="return VuFind.Lists.addToHomePage('{$favList->id}')">{translate text='Add To Home Page'}</a>
								{/if}
							{/if}
						</div>
					{/if}
					<div class="btn-group">
						<button value="emailList" id="FavEmail" class="btn btn-sm btn-default" onclick='return VuFind.Lists.emailListAction("{$favList->id}")'>Email List</button>
						<button value="printList" id="FavPrint" class="btn btn-sm btn-default" onclick='return VuFind.Lists.printListAction()'>Print List</button>
						<button value="citeList" id="FavCite" class="btn btn-sm btn-default" onclick='return VuFind.Lists.citeListAction("{$favList->id}")'>Generate Citations</button>

						<div class="btn-group" role="group">
							<button type="button" class="btn btn-sm btn-default btn-info dropdown-toggle" data-toggle="dropdown" aria-expanded="false">Sort &nbsp;<span class="caret"></span></button>
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
							<button value="deleteList" id="FavDelete" class="btn btn-sm btn-danger" onclick='return VuFind.Lists.deleteListAction();'>Delete List</button>
						</div>
					{/if}
				</div>
			{/if}
		</div>
	</form>

	{if $favList->deleted == 0}
		{if $resourceList}
			<form class="navbar form-inline">
				<label for="pagesize" class="control-label">Records Per Page</label>&nbsp;
				<select id="pagesize" class="pagesize form-control{* input-sm*}" onchange="VuFind.changePageSize()">
					<option value="20"{if $recordsPerPage == 20} selected="selected"{/if}>20</option>
					<option value="40"{if $recordsPerPage == 40} selected="selected"{/if}>40</option>
					<option value="60"{if $recordsPerPage == 60} selected="selected"{/if}>60</option>
					<option value="80"{if $recordsPerPage == 80} selected="selected"{/if}>80</option>
					<option value="100"{if $recordsPerPage == 100} selected="selected"{/if}>100</option>
				</select>
				<label for="hideCovers" class="control-label checkbox pull-right"> Hide Covers <input id="hideCovers" type="checkbox" onclick="VuFind.Account.toggleShowCovers(!$(this).is(':checked'))" {if $showCovers == false}checked="checked"{/if}></label>
			</form>

		{if $recordCount}
			<div class="resulthead row">
				<div class="col-xs-12">
						{translate text="Showing"} <b>{$recordStart}</b> - <b>{$recordEnd}</b> {translate text='of'} <b>{$recordCount}</b>
						{if $debug}
							&nbsp;There are {$favList->numValidListItems()} valid Items.
						{/if}
				</div>
			</div>
		{/if}

			{if $allowEdit && $userSort}
				<div class="alert alert-info alert-dismissible" role="alert">
					<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
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
								var updates = [],
										firstItemOnPage = {/literal}{$recordStart}{literal};
								$('#UserList>div>div').each(function(currentOrder){
									var id = this.id.replace('groupedRecord','') /* Grouped IDs for catalog items */
																	.replace('archive',''),      /*modified Islandora PIDs for archive items*/
													originalOrder = $(this).data('order'),
													change = currentOrder+firstItemOnPage-originalOrder,
													newOrder = originalOrder+change;
									if (change != 0) updates.push({'id':id, 'newOrder':newOrder});
								});
								$.getJSON(Globals.path + '/MyAccount/AJAX',
												{
													method:'setListEntryPositions'
													,updates:updates
													,listID:{/literal}{$favList->id}{literal}
												}
												, function(response){
													if (response.success) {
														updates.forEach(function(e){
															if ($('#groupedRecord'+ e.id).length > 0) {
																$('#groupedRecord' + e.id).data('order', e.newOrder)
																				.find('span.result-index').text(e.newOrder + ')');
															/*$('#weight_'+ e.id).val(e.newOrder);*/
															} else if ($('#archive'+ e.id).length > 0) {
																$('#archive' + e.id).data('order', e.newOrder)
																				.find('span.result-index').text(e.newOrder + ')');
															/*$('#weight_'+ e.id).val(e.newOrder);*/
															}
														})
													}
												})
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
{*{/strip}*}
