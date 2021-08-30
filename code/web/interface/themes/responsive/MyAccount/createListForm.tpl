{strip}
	{if $listError}<p class="error">{$listError|translate}</p>{/if}
	<form method="post" action="" name="listForm" class="form form-horizontal" id="addListForm">
		<div class="form-group">
			<label for="listTitle" class="col-sm-3 control-label">{translate text="List" isPublicFacing=true}</label>
			<div class="col-sm-9">
				<input type="text" id="listTitle" name="title" value="{$list->title|escape:"html"}" size="50" class="form-control">
			</div>
		</div>
		<div class="form-group">
		  <label for="listDesc" class="col-sm-3 control-label">{translate text="Description" isPublicFacing=true}</label>
			<div class="col-sm-9">
		    <textarea name="desc" id="listDesc" rows="3" cols="50" class="form-control">{$list->desc|escape:"html"}</textarea>
			</div>
		</div>
		<div class="form-group">
			<label for="public" class="col-sm-3 control-label">{translate text="Access" isPublicFacing=true}</label>
			<div class="col-sm-9">
				<input type='checkbox' name='public' id='public' data-on-text="{translate text="Public" isPublicFacing=true}" data-off-text="{translate text="Private" isPublicFacing=true}" {if in_array('Include Lists In Search Results', $userPermissions)}onchange="if($(this).prop('checked') === true){ldelim}$('#searchableRow').show(){rdelim}else{ldelim}$('#searchableRow').hide(){rdelim}"{/if}/>
				<div class="form-text text-muted">
					<small>{translate text="Public lists can be shared with other people by copying the URL of the list or using the Email List button when viewing the list." isPublicFacing=true}</small>
				</div>
			</div>
		</div>
		{if in_array('Include Lists In Search Results', $userPermissions)}
			<div class="form-group" id="searchableRow" style="display: none">
				<label for="searchable" class="col-sm-3 control-label">{translate text="Show in search results" isPublicFacing=true}</label>
				<div class="col-sm-9">
					<input type='checkbox' name='searchable' id='searchable' data-on-text="{translate text="Yes" isPublicFacing=true}" data-off-text="{translate text="No" isPublicFacing=true}" checked/>
					<div class="form-text text-muted">
						<small>{translate text="If enabled, this list can be found by searching user lists. It must have at least 3 titles to be shown." isPublicFacing=true}</small>
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