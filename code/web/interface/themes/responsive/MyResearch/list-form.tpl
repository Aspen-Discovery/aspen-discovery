{strip}
	{if $listError}<p class="error">{$listError|translate}</p>{/if}
	<form method="post" action="{$path}/MyAccount/ListEdit" name="listForm" class="form form-horizontal" id="addListForm">
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
	  <input type="hidden" name="recordId" value="{$recordId}">
	</form>
	<br/>
{/strip}
<script type="text/javascript">{literal}
	$(document).ready(function(){
		var publicSwitch = $('#public').bootstrapSwitch();
	});
{/literal}</script>