{strip}
	{if $listError}<p class="error">{$listError|translate}</p>{/if}
	<form method="post" action="" name="listForm" class="form form-horizontal" id="addListForm">
		<div class="form-group">
			<label for="listTitle" class="col-sm-3 control-label">{translate text="List"}</label>
			<div class="col-sm-9">
				<input type="text" id="listTitle" name="title" value="{$list->title|escape:"html"}" size="50" class="form-control">
			</div>
		</div>
		<div class="form-group">
		  <label for="listDesc" class="col-sm-3 control-label">{translate text="Description"}</label>
			<div class="col-sm-9">
		    <textarea name="desc" id="listDesc" rows="3" cols="50" class="form-control">{$list->desc|escape:"html"}</textarea>
			</div>
		</div>
		<div class="form-group">
			<label for="public" class="col-sm-3 control-label">{translate text="Access"}</label>
			<div class="col-sm-9">
				<input type='checkbox' name='public' id='public' data-on-text="Public" data-off-text="Private" {if in_array('Include Lists In Search Results', $userPermissions)}onchange="if($(this).prop('checked') === true){ldelim}$('#searchableRow').show(){rdelim}else{ldelim}$('#searchableRow').hide(){rdelim}"{/if}/>
				<div class="form-text text-muted">
					<small>{translate text="nonindexed_public_list_description" defaultText="Public lists can be shared with other people by copying the URL of the list or using the Email List button when viewing the list."}</small>
				</div>
			</div>
		</div>
		{if in_array('Include Lists In Search Results', $userPermissions)}
			<div class="form-group" id="searchableRow" style="display: none">
				<label for="searchable" class="col-sm-3 control-label">{translate text="Show in search results"}</label>
				<div class="col-sm-9">
					<input type='checkbox' name='searchable' id='searchable' data-on-text="Yes" data-off-text="No" checked/>
					<div class="form-text text-muted">
						<small>{translate text="searchable_list_description" defaultText="If enabled, this list can be found by searching user lists. It must have at least 3 titles to be shown."}</small>
					</div>
				</div>
			</div>
		{/if}
		<input type="hidden" name="source" value="{$source}">
		<input type="hidden" name="sourceId" value="{$sourceId}">
	</form>
	<br/>
{/strip}
<script type="text/javascript">{literal}
	$(document).ready(function(){
		var publicSwitch = $('#public').bootstrapSwitch();
		var searchableSwitch = $('#searchable').bootstrapSwitch();
	});
{/literal}</script>