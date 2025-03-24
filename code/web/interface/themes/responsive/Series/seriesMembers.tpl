{strip}

	<div class="row">
		<div class="col-xs-12">
			<form action="/Series/{$series->id}" id="myListFormHead">
				<div class="result">
					<input type="hidden" name="myListActionHead" id="myListActionHead" class="form">
					<h1 id="listTitle">{$series->displayName|escape:"html"}</h1>
					<div class="row">
						<div class="col-xs-3 col-sm-3 col-md-3 col-lg-2 text-center">
							<img class="listResultImage img-thumbnail {$coverStyle}" src='{$cover}' alt='{translate text='Series Cover' inAttribute=true isPublicFacing=true}'/>
						</div>
						<div class="col-xs-9 col-sm-9 col-md-9 col-lg-10">
							{if !empty($authors)}
								<div class="row">
									<div class="result-label col-tn-3 col-xs-3">{translate text="Author" isPublicFacing=true}</div>
									<div class="result-value col-tn-9 col-xs-9 notranslate">
										{if is_array($authors)}
											{foreach from=$authors item=author}
												{if $author == "Various"}
													{translate text="Various" isPublicFacing=true}
												{else}
													<a href='/Author/Home?author="{$author|escape:"url"}"'>{$author|highlight}</a> <br/>
												{/if}
											{/foreach}
										{else}
											{$authors|escape:"html"}
										{/if}
									</div>
								</div>
							{/if}
							{if !empty($series->audience)}
								<div class="row">
									<div class="result-label col-tn-3 col-xs-3">{translate text="Audience" isPublicFacing=true}</div>
									<div class="result-value col-tn-9 col-xs-9">
										{$series->audience|escape:"html"}
									</div>
								</div>
							{/if}
							{if !empty($series->description)}
								<br/>
								<div class="row">
									<div class="result-value col-sm-12">{$series->description}</div>
								</div>
							{/if}
						</div>
					</div>
					<hr/>

					{if $series->deleted == 1}
						<p class="alert alert-danger">{translate text='Sorry, this series has been deleted.' isPublicFacing=true}</p>
					{else}
						<div class="clearer"></div>
						<div id="listTopButtons" class="btn-toolbar">
							{if !empty($loggedIn) && (in_array('Administer Series', $userPermissions))}
								<div class="btn-group btn-group-sm">
									<button value="editList" id="FavEdit" class="btn btn-sm btn-info listViewButton" onclick="return AspenDiscovery.Series.editAction({$series->id})">{translate text='Edit' isPublicFacing=true}</button>
								</div>
							{/if}

							<div class="btn-group btn-group-sm">
								<button value="emailList" id="SeriesEmail" class="btn btn-sm btn-default listViewButton" onclick='return AspenDiscovery.Series.emailAction("{$series->id}")'>{translate text='Email' isPublicFacing=true}</button>
								<button value="printList" id="Seriesrint" class="btn btn-sm btn-default listViewButton" onclick='return AspenDiscovery.Series.printAction()'>{translate text='Print' isPublicFacing=true}</button>
							</div>

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
					{/if}
				</div>
			</form>
		</div>
	</div>

	{if $series->deleted == 0}
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
										,listID:{/literal}{$series->id}{literal}
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
