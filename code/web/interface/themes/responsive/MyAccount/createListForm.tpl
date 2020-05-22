{strip}
	{if $listError}<p class="error">{$listError|translate}</p>{/if}
	<form method="post" action="" name="listForm" class="form form-horizontal" id="addListForm">
		<div class="form-group">
			<label for="listTitle" class="col-sm-3 control-label">{translate text="List"}:</label>
			<div class="col-sm-9">
				<input type="text" id="listTitle" name="title" value="{$list->title|escape:"html"}" size="50" class="form-control">
			</div>
		</div>
		<div class="form-group">
		  <label for="listDesc" class="col-sm-3 control-label">{translate text="Description"}:</label>
			<div class="col-sm-9">
		    <textarea name="desc" id="listDesc" rows="3" cols="50" class="form-control">{$list->desc|escape:"html"}</textarea>
			</div>
		</div>
		<div class="form-group">
			<label for="public" class="col-sm-3 control-label">{translate text="Access"}:</label>
			<div class="col-sm-9">
				<input type='checkbox' name='public' id='public' data-on-text="Public" data-off-text="Private"/>
			</div>

		</div>
		<div class="form-group">
			<div class="col-sm-9 col-sm-offset-3">
				<div class="alert alert-info">
					{if !$publicListWillBeIndexed}
						{translate text="nonindexed_public_list_description" defaultText="Public lists can be shared with other people by copying the URL of the list or using the Email List button when viewing the list."}
					{else}
						{translate text="indexed_public_list_description" defaultText="This list will be shown within search results if it has at least 3 titles on it. Public lists can also be shared with other people by copying the URL of the list or using the Email List button when viewing the list."}
					{/if}
				</div>
			</div>
		</div>
		<input type="hidden" name="source" value="{$source}">
		<input type="hidden" name="sourceId" value="{$sourceId}">
	</form>
	<br/>
{/strip}
<script type="text/javascript">{literal}
	$(document).ready(function(){
		let publicSwitch = $('#public').bootstrapSwitch();
	});
{/literal}</script>