{strip}
	<h1 id="resourceTitle">{$recordDriver->getTitle()|escape:"html"}</h1>

	<form enctype="multipart/form-data" method="post" id="listEntryEditForm" action="/MyAccount/AJAX" class="form-horizontal">
		<input type="hidden" name="listEntry" value="{$listEntry}">
		<input type="hidden" name="listId" value="{$list}">
		{if $list->defaultSort != 'custom'}<input type="hidden" name="position" value="{if !empty($listEntry->weight)}{$listEntry->weight}{else}0{/if}">{/if}
		<div>
			<div class="form-group">
				<label for="listName" class="col-sm-3">{translate text='List'}: </label>
				<div class="col-sm-9">{$list->title}</div>
			</div>

			{if $list->defaultSort == 'custom'}
			<div class="form-group">
				<label for="listPosition" class="col-sm-3">{translate text='Position'}: </label>
				<div class="col-sm-9"><input type="number" class="form-control" value="{$listEntry->weight}" name="position"></div>
			</div>
			{/if}

			<div class="form-group">
				<label for="listCopy" class="col-sm-3">{translate text='Copy to List'}: </label>
				<div class="col-sm-9">
					<select class="form-control" name="copyTo">
						<option value="null"></option>
						{foreach from=$lists item="list" key="resultIndex"}
						<option value="{$list->id}">{$list->title}</option>
						{/foreach}
					</select>
				</div>
			</div>

			<div class="form-group">
				<label for="listMove" class="col-sm-3">{translate text='Move to List'}: </label>
				<div class="col-sm-9">
					<select class="form-control" name="moveTo">
						<option value="null"></option>
						{foreach from=$lists item="list" key="resultIndex"}
							<option value="{$list->id}">{$list->title}</option>
						{/foreach}
					</select>
				</div>
			</div>

			<div class="form-group">
				<label for="listNotes" class="col-sm-3">{translate text='Notes'}: </label>
				<div class="col-sm-9">
					<textarea id="listNotes" name="notes" rows="3" cols="50" class="form-control">{$listEntry->notes|escape:"html"}</textarea>
				</div>
			</div>

		</div>
	</form>
	<script type="application/javascript">
		{literal}
		$("#listEntryEditForm").validate({
			submitHandler: function(){
				AspenDiscovery.Account.editListItem()
			}
		});
		{/literal}
	</script>
{/strip}