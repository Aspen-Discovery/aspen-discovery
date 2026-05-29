{strip}
	{if !empty($allowEdit)}
		<div id="listEditControls" style="display:none" class="collapse">
			<div class="form-group">
				<label for="listTitleEdit" class="control-label">{translate text="Title" isPublicFacing=true}</label>
				{if empty($validListNames)}
					<input type="text" id="listTitleEdit" name="newTitle" value="{$userList->title|escape:"html"}" size="50" class="form-control">
				{else}
					<select id="listTitleEdit" name="titleSelect" class="form-control">
						{foreach from=$validListNames item=listName key=listNameIndex}
							<option value="{$listNameIndex}" {if $userList->title == $listName}selected{/if}>{$listName}</option>
						{/foreach}
					</select>
				{/if}
			</div>
			{if !empty($enableListDescriptions)}
				<div class="form-group">
					<label for="listDescriptionEdit" class="control-label">{translate text="Description" isPublicFacing=true}</label>&nbsp;
					<textarea name="newDescription" id="listDescriptionEdit" rows="3" cols="80" class="form-control">{$userList->getCleanDescription()|escape:"html"}</textarea>
				</div>
			{/if}
			<div class="form-group">
				<label for="public" class="col-sm-3 control-label">{translate text="Access" isPublicFacing=true}</label>
				<div class="col-sm-9">
					<input type='checkbox' name='public' id='public' data-on-text="Public" data-off-text="Private" {if $userList->public == 1}checked{/if} {if in_array('Include Lists In Search Results', $userPermissions)}onchange="AspenDiscovery.Lists.updateListEditFields();"{/if}/>
					<div class="form-text text-muted">
						<small>{translate text="Public lists can be shared with other people by copying the URL of the list or using the Email List button when viewing the list." isPublicFacing=true}</small>
					</div>
				</div>
			</div>
			{if !empty($userListGroups)}
				<div class="form-group">
					<label for="listGroupSelect" class="col-sm-3 control-label">{translate text="List Group" isPublicFacing=true}</label>
					<div class="col-sm-9">
						<select id="listGroupSelect" name="listGroupSelect" class="form-control">
							<option value="-1">{translate text="No Group" isPublicFacing=true}</option>
							{foreach from=$userListGroups item=listGroup}
								<option value="{$listGroup->id}" {if $inListGroup && $listGroup->id == $userList->listGroupId}selected{/if}>{$listGroup->title|escape:"html"}</option>
							{/foreach}
						</select>
						<div class="form-text text-muted">
							<small>{translate text="Select a group to associate this list with. List groups can be used to organize multiple lists." isPublicFacing=true}</small>
						</div>
					</div>
				</div>
			{/if}
			{if in_array('Include Lists In Search Results', $userPermissions)}
				<div class="form-group" id="searchableRow" {if $userList->public == 0}style="display: none"{/if}>
					<label for="searchable" class="col-sm-3 control-label">{translate text="Show in search results" isPublicFacing=true}</label>
					<div class="col-sm-9">
						<input type='checkbox' name='searchable' id='searchable' data-on-text="Yes" data-off-text="No" {if $userList->searchable == 1}checked{/if} onchange="AspenDiscovery.Lists.updateListEditFields();"/>
						<div class="form-text text-muted">
							<small>{translate text="If enabled, this list can be found by searching user lists. It must have at least 3 titles to be shown." isPublicFacing=true}</small>
						</div>
					</div>
				</div>
				<div class="form-group" id="displayListAuthorRow" {if $userList->public == 0}style="display: none"{/if}>
					<label for="displayListAuthor" class="col-sm-3 control-label">{translate text="Show list author in search results" isPublicFacing=true}</label>
					<div class="col-sm-9">
						<input type='checkbox' name='displayListAuthor' id='displayListAuthor' data-on-text="Yes" data-off-text="No" {if $userList->displayListAuthor == 1}checked{/if} onchange="AspenDiscovery.Lists.updateListEditFields();"/>
						<div class="form-text text-muted">
							<small>{translate text="If enabled, your name will be displayed as the author of this public list." isPublicFacing=true}</small>
						</div>
					</div>
				</div>
				<div class="form-group" id="customAuthorNameRow" style="display: none">
					<label for="customAuthorName" class="col-sm-3 control-label">{translate text="List Author Name" isPublicFacing=true}</label>
					<div class="col-sm-9">
						<input type='text' name='customAuthorName' id='customAuthorName' maxlength="256" class="form-control" value="{$userList->customAuthorName|escape}"/>
						<div class="form-text text-muted">
							<small>{translate text="Leave blank to use your user display name." isPublicFacing=true}</small>
						</div>
					</div>
				</div>
			{/if}
			{if in_array('Upload List Covers', $userPermissions)}
				<div class="form-group" id="listCoversRow" {if $userList->public == 0}style="display: none"{/if}>
					<label class="col-sm-3 control-label">{translate text="Upload custom list cover" isPublicFacing=true}</label>
					<div class="col-sm-9">
						<button onclick="return AspenDiscovery.Lists.getUploadListCoverForm({$userList->id})" class="btn btn-sm btn-default">{translate text="Upload List Cover from Computer" isPublicFacing=true}</button>
						<button onclick="return AspenDiscovery.Lists.getUploadListCoverFormByURL('{$userList->id}')" class="btn btn-sm btn-default">{translate text="Upload List Cover by URL" isPublicFacing=true}</button>
						{if $hasUploadedCover}
							<button onclick="return AspenDiscovery.Lists.removeUploadedListCover('{$userList->id}')" class="btn btn-sm btn-danger" id="removeUploadedListCover">{translate text="Remove Uploaded Cover" isPublicFacing=true}</button>
						{/if}
					</div>
				</div>
			{/if}
		</div>
		<script type="text/javascript">
		{literal}
			$(document).ready(function(){
				$('#public').bootstrapSwitch();
				$('#searchable').bootstrapSwitch();
				$('#displayListAuthor').bootstrapSwitch();
			});
		{/literal}</script>
	{/if}
{/strip}
