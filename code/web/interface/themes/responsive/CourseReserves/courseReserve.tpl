{strip}

	<div class="row">
		<div class="col-xs-12">
			<form action="/CourseReserves/{$courseReserve->id}" id="myListFormHead">
				<div>
					<input type="hidden" name="myListActionHead" id="myListActionHead" class="form">
					<h1 id="listTitle">{$courseReserve->getTitle()|escape:"html"}</h1>

					{if $courseReserve->deleted == 1}
						<p class="alert alert-danger">{translate text='Sorry, this course reserve has been deleted.' isPublicFacing=true}</p>
					{else}
						<div class="clearer"></div>
						<div id="listTopButtons" class="btn-toolbar">
							{if $allowEdit}
								<div class="btn-group btn-group-sm">
									<button value="editList" id="FavEdit" class="btn btn-sm btn-info listViewButton" onclick="return AspenDiscovery.CourseReserves.editListAction()">{translate text='Edit' isPublicFacing=true}</button>
								</div>
								<div class="btn-group btn-group-sm">
									<button value="cancelEditList" id="cancelEditList" class="btn btn-sm btn-default listEditButton" style="display:none" onclick='return AspenDiscovery.CourseReserves.cancelEditListAction()'>{translate text='Cancel' isPublicFacing=true}</button>
								</div>
							{/if}
							{if $loggedIn && (in_array('Administer All Collection Spotlights', $userPermissions) || in_array('Administer Library Collection Spotlights', $userPermissions) || in_array('Administer All Browse Categories', $userPermissions) || in_array('Administer Library Browse Categories', $userPermissions))}
								<div class="btn-group btn-group-sm">
									{if (in_array('Administer All Collection Spotlights', $userPermissions) || in_array('Administer Library Collection Spotlights', $userPermissions))}
										&nbsp;&nbsp;<a href="#" class="button btn btn-sm btn-default listViewButton" id="FavCreateSpotlight" onclick="return AspenDiscovery.CollectionSpotlights.createSpotlightFromCourseReserve('{$courseReserve->id}')">{translate text='Create Spotlight' isAdminFacing=true}</a>
									{/if}
									{if (in_array('Administer All Browse Categories', $userPermissions) || in_array('Administer Library Browse Categories', $userPermissions))}
										<a href="#" id="FavHome" class="btn btn-sm btn-default listViewButton" onclick="return AspenDiscovery.CourseReserves.addToHomePage('{$courseReserve->id}')">{translate text='Add To Browse' isAdminFacing=true}</a>
									{/if}
								</div>
							{/if}

							<div class="btn-group btn-group-sm">
								<button value="emailList" id="CourseReserveEmail" class="btn btn-sm btn-default listViewButton" onclick='return AspenDiscovery.CourseReserves.emailAction("{$courseReserve->id}")'>{translate text='Email' isPublicFacing=true}</button>
								<button value="printList" id="CourseReservePrint" class="btn btn-sm btn-default listViewButton" onclick='return AspenDiscovery.CourseReserves.printAction()'>{translate text='Print' isPublicFacing=true}</button>
							</div>
						</div>
					{/if}
				</div>
			</form>
		</div>
	</div>

	{if $courseReserve->deleted == 0}
		{if $resourceList}
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
			{if $userSort}
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
										,listID:{/literal}{$courseReserve->id}{literal}
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
