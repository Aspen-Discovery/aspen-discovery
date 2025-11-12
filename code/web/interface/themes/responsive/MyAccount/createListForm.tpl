{strip}
	{if !empty($listError)}<p class="error">{translate text=$listError isPublicFacing=true}</p>{/if}
	<form method="post" action="" name="listForm" class="form form-horizontal" id="addListForm">
		<div class="form-group">
			<label for="listTitle" class="col-sm-3 control-label">{translate text="List" isPublicFacing=true}</label>
			<div class="col-sm-9">
				{if empty($validListNames)}
					<input type="text" id="listTitle" name="title" value="" size="50" class="form-control">
				{else}
					<select id="listTitle" name="titleSelect" class="form-control">
						{foreach from=$validListNames item=listName key=listNameIndex}
							<option value="{$listNameIndex}">{$listName}</option>
						{/foreach}
					</select>
				{/if}
			</div>
		</div>
		{if !empty($enableListDescriptions)}
			<div class="form-group">
			  <label for="listDesc" class="col-sm-3 control-label">{translate text="Description" isPublicFacing=true}</label>
				<div class="col-sm-9">
					<textarea name="desc" id="listDesc" rows="3" cols="50" class="form-control"></textarea>
				</div>
			</div>
		{/if}
		<div class="form-group">
			<label for="public" class="col-sm-3 control-label">{translate text="Access" isPublicFacing=true}</label>
			<div class="col-sm-9">
				<input type='checkbox' name='public' id='public' data-on-text="{translate text="Public" isPublicFacing=true}" data-off-text="{translate text="Private" isPublicFacing=true}" {if in_array('Include Lists In Search Results', $userPermissions)}onchange="if($(this).prop('checked') === true){ldelim}$('#searchableRow').show();$('#displayListAuthorRow').show(){rdelim}else{ldelim}$('#searchableRow').hide();$('#displayListAuthorRow').hide(){rdelim}"{/if}/>
				<div class="form-text text-muted">
					<small>{translate text="Public lists can be shared with other people by copying the URL of the list or using the Email List button when viewing the list." isPublicFacing=true}</small>
				</div>
			</div>
		</div>
		{if !empty($userPermissions)}
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
		{/if}
		{if !empty($userPermissions)}
		{if in_array('Include Lists In Search Results', $userPermissions)}
			<div class="form-group" id="displayListAuthorRow" style="display: none">
				<label for="displayListAuthor" class="col-sm-3 control-label">{translate text="Show list author in search results" isPublicFacing=true}</label>
				<div class="col-sm-9">
					<input type='checkbox' name='displayListAuthor' id='displayListAuthor' data-on-text="{translate text="Yes" isPublicFacing=true}" data-off-text="{translate text="No" isPublicFacing=true}" checked/>
					<div class="form-text text-muted">
						<small>{translate text="If enabled, your name will be displayed as the author of this public list." isPublicFacing=true}</small>
					</div>
				</div>
			</div>
		{/if}
		{/if}

		{*Options for adding to a list group*}
		<div class="form-group" style="padding-top: 1em;">
			<label for="addToListGroup-Options" class="col-sm-3 control-label">{translate text='Add To List Group?' isPublicFacing=true}</label>
			<div class="col-sm-9">
				<select name="addToListGroup-Options" id="addToListGroup-Options" class="form-control form-control-sm">
					<option value="none" {if empty($userListGroups) && empty($defaultGroupId)}selected{/if}>{translate text='No, do not add to a group' isPublicFacing=true}</option>
					<option value="new">{translate text='Yes, create a new group' isPublicFacing=true}</option>
					{if !empty($userListGroups)}<option value="existing" {if !empty($defaultGroupId)}selected{/if}>{translate text='Yes, add to an existing group' isPublicFacing=true}</option>{/if}
				</select>
				<script>
					$(document).ready(function(){ldelim}
						$('#addToListGroup-Options').change(function(){ldelim}
							var selectedOption = $(this).val();
							if (selectedOption === 'new') {ldelim}
								$('#addToListGroup-New').show();
								$('#addToListGroup-Existing').hide();
							{rdelim} else if (selectedOption === 'existing') {ldelim}
								$('#addToListGroup-New').hide();
								$('#addToListGroup-Existing').show();
							{rdelim} else {ldelim}
								$('#addToListGroup-New').hide();
								$('#addToListGroup-Existing').hide();
							{rdelim}
						{rdelim});
					{rdelim});
				</script>
			</div>
		</div>

		{*Show the new group name and nesting options if "new" is selected*}
		<div id="addToListGroup-New" style="display: none;">
			<div class="form-group">
				<label for="addToListGroup-NewName" class="col-sm-3 control-label">{translate text='New List Group Name' isPublicFacing=true}</label>
				<div class="col-sm-9">
					<input type="text" name="newListGroupName" id="addToListGroup-NewName" class="form-control form-control-sm"/>
					<script>
						$(document).ready(function() {ldelim}
							$('#addListForm').submit(function(e) {ldelim}
								var newGroupName = $('#addToListGroup-NewName').val().trim().toLowerCase();
								var exists = false;
								{foreach from=$userListGroups item="listGroup"}
								if (newGroupName === "{$listGroup->title|escape:'js'}".toLowerCase()) {ldelim}
									exists = true;
								{rdelim}
								{/foreach}
								if ($('#addToListGroup-Options').val() === 'new' && exists) {ldelim}
									$('#newListGroupName-validation').show();
									e.preventDefault();
								{rdelim} else {ldelim}
									$('#newListGroupName-validation').hide();
								{rdelim}
							{rdelim});
						{rdelim});
					</script>
					<div id="newListGroupName-validation" style="display:none;">
						<small class="text-danger">{translate text='A list group with this name already exists.' isPublicFacing=true}</small>
					</div>
				</div>
			</div>

			{if !empty($userListGroups)}
				<div id="addToListGroup-New-NestingGroups" class="form-group">
					<label for="addToListGroup-Nested" class="col-sm-3 control-label">{translate text='Nest within another group?' isPublicFacing=true}</label>
					<div class="col-sm-9">
						<select name="nestedWithinGroup" id="addToListGroup-Nested" class="form-control form-control-sm">
							<option value="none" {if empty($defaultGroupId)}selected{/if}>{translate text='No, do not nest within another group' isPublicFacing=true}</option>
							{foreach from=$userListGroups item="listGroup"}
								<option value="{$listGroup->id}" {if !empty($defaultGroupId) && $listGroup->id == $defaultGroupId}selected{/if}>{$listGroup->getFullGroupTitle()|escape:"html"}</option>
							{/foreach}
						</select>
					</div>
				</div>
			{/if}
		</div>

		{*Show the existing group selection if "existing" is selected*}
		{if !empty($userListGroups)}
			<div class="form-group" id="addToListGroup-Existing" {if empty($defaultGroupId)}style="display: none;"{/if}>
				<label for="addToList-listGroup" class="col-sm-3">{translate text='Choose a List Group' isPublicFacing=true}</label>
				<div class="col-sm-9">
					<select name="listGroup" id="addToList-listGroup" class="form-control form-control-sm">
						{foreach from=$userListGroups item="listGroup"}
							<option value="{$listGroup->id}" {if $userListGroupLastViewed === $listGroup->id}selected{/if}>{$listGroup->title|escape:"html"}</option>
						{/foreach}
					</select>
				</div>
			</div>
		{/if}
	<input type="hidden" name="source" value="{if !empty($source)}{$source}{/if}">
		<input type="hidden" name="sourceId" value="{if !empty($sourceId)}{$sourceId}{/if}">
	</form>
	<br/>
{/strip}
<script type="text/javascript">
{literal}
	$(document).ready(function(){
		var publicSwitch = $('#public').bootstrapSwitch();
		var searchableSwitch = $('#searchable').bootstrapSwitch();
		var displayListAuthorSwitch = $('#displayListAuthor').bootstrapSwitch();
	});
{/literal}</script>
