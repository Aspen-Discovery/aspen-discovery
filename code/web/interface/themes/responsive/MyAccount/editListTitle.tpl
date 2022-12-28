{strip}
	<h1 id="resourceTitle">{$recordDriver->getTitle()|escape:"html"}</h1>

	<form enctype="multipart/form-data" method="post" id="listEntryEditForm" action="/MyAccount/AJAX" class="form-horizontal">
		<input type="hidden" name="listEntry" value="{$listEntry}">
		<input type="hidden" name="listId" value="{$listId}">
		{if $listId->defaultSort != 'custom'}<input type="hidden" name="position" value="{if !empty($listEntry->weight)}{$listEntry->weight}{else}0{/if}">{/if}
		<div>
			<div class="form-group">
				<label for="listName" class="col-sm-3">{translate text='List' isPublicFacing=true} </label>
				<div class="col-sm-9">{$list->title}</div>
			</div>

			{if $list->defaultSort == 'custom'}
			<div class="form-group">
				<label for="listPosition" class="col-sm-3">{translate text='Position' isPublicFacing=true} </label>
				<div class="col-sm-9"><input type="number" class="form-control" value="{$listEntry->weight}" name="position" min="1" max="{$maxListPosition}"></div>
			</div>
			{/if}

			<div class="form-group">
				<label for="listCopy" class="col-sm-3">{translate text='Copy to List' isPublicFacing=true} </label>
				<div class="col-sm-9">
					<select class="form-control" name="copyTo">
						<option value="null"></option>
						{foreach from=$lists item="list" key="resultIndex"}
							{if $list->id != $listId}
								<option value="{$list->id}">{$list->title}</option>
							{/if}
						{/foreach}
					</select>
				</div>
			</div>

			<div class="form-group">
				<label for="listMove" class="col-sm-3">{translate text='Move to List' isPublicFacing=true} </label>
				<div class="col-sm-9">
					<select class="form-control" name="moveTo">
						<option value="null"></option>
						{foreach from=$lists item="list" key="resultIndex"}
							{if ($list->id != $listId)}
								<option value="{$list->id}">{$list->title}</option>
							{/if}
						{/foreach}
					</select>
				</div>
			</div>

			{if !empty($enableListDescriptions)}
				<div class="form-group">
					<label for="listNotes" class="col-sm-3">{translate text='Notes' isPublicFacing=true} </label>
					<div class="col-sm-9">
						<textarea id="listNotes" name="notes" rows="3" cols="50" class="form-control">{$listEntry->notes|escape:"html"}</textarea>
					</div>
				</div>
			{/if}

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