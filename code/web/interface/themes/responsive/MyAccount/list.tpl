{strip}

	{if $printInterface === true}
		<div class="row">
			<div class="col-xs-12">
				<h2>{$userList->title|escape:"html"}</h2>
				{if $printListAuthor === true}
					<p><strong>{$userList->getListAuthor()}</strong></p>
				{/if}
                {if !empty($enableListDescriptions) && $printListDescription === true}
	                <p>{$userList->getCleanDescription()|escape:"html"}</p>
				{/if}
			<p><small>{translate text='Created on' isPublicFacing=true}  {$dateCreated}<br/>
			{translate text='Last Updated' isPublicFacing=true}  {$dateUpdated}</small></p>
			</div>
		</div>
	{else}
		{* Regular display (not print interface) *}
		<div class="row" id="listActions">
			<div class="col-xs-12">
				<form action="/MyAccount/MyList/{$userList->id}" id="myListFormHead">
					<div>
						<input type="hidden" name="myListActionHead" id="myListActionHead" class="form">
						<h1 id="listTitle">{$userList->title|escape:"html"}</h1>
						{if $inListGroup && !empty($allowEdit) && !empty($showListGroup)}
							<div id="listGroup">
								<small>{translate text='In Group' isPublicFacing=true}  <a href="/MyAccount/Lists?groupId={$listGroupInfo->id}">{$listGroupInfo->getFullGroupTitle()}</a></small>
							</div>
						{/if}
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
							{include file="MyAccount/listEditForm.tpl"}
							{include file="MyAccount/listToolbar.tpl" location="Top"}
						{/if}
					</div>
				</form>
			</div>
		</div>

		{if !empty($topRecommendations)}
			{foreach from=$topRecommendations item="recommendations"}
				{include file=$recommendations}
			{/foreach}
		{/if}
	{/if}

	{if $userList->deleted == 0}
		{if !empty($resourceList)}
            {if $printInterface === false}
			<div class="row">
				<form class="navbar form-inline">
					{if $recordCount > 20}
						<div class="col-xs-4">
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
						</div>
					{/if}
					<div class="col-xs-4">
						{if !empty($allowEdit)}
							<div class="form-group checkbox">
								<label for="selectAllMenuItems">
									<input type="checkbox" name="selectAllMenuItems" id="selectAllMenuItems" onchange="AspenDiscovery.toggleCheckboxes('.titleSelect', '#selectAllMenuItems');"> <strong>{translate text="Select/Deselect All" isPublicFacing=true}</strong>
								</label>
							</div>
						{/if}
					</div>
					{if $recordCount < 20}
						<div class="col-xs-4"></div>
					{/if}
                    <div class="col-xs-4">
						<label for="hideCovers" class="control-label checkbox pull-right"> {translate text='Hide Covers' isPublicFacing=true} <input id="hideCovers" type="checkbox" onclick="AspenDiscovery.Account.toggleShowCovers(!$(this).is(':checked'))" {if $showCovers == false}checked="checked"{/if}></label>
					</div>
				</form>
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

			{include file="MyAccount/listToolbar.tpl" location="Bottom"}

            {if strlen($pageLinks.all) > 0}<div class="text-center">{$pageLinks.all}</div>{/if}
        {else}
			{translate text='You do not have any saved resources' isPublicFacing=true}
		{/if}
	{/if}
{/strip}
