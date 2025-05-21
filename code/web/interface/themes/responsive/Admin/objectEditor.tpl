<script src="/tinymce/tinymce.min.js"></script>
<script src="/tinymce/plugins/tinymceEmoji/plugin.min.js"></script>
{if !empty($updateMessage)}
	<div class="alert {if !empty($updateMessageIsError)}alert-danger{else}alert-info{/if}">
		{$updateMessage}
	</div>
{/if}
{strip}
	<div class="col-xs-12">
		<div class="row">
			<div class="col-xs-12 col-md-9">
				{if $objectAction == 'copy'}
					<h1 id="pageTitle">{translate text="Copying %1%" 1=$objectName isAdminFacing=true}</h1>
				{else}
					<h1 id="pageTitle">{$pageTitleShort}{if !empty($objectName)} - {$objectName}{/if}</h1>
				{/if}
			</div>
			<div class="col-xs-12 col-md-3 help-link">
				{if !empty($instructions)}<a href="{$instructions}"><i class="fas fa-question-circle" role="presentation"></i>&nbsp;{translate text="Documentation" isAdminFacing=true}</a>{/if}
			</div>
		</div>
		<div class="row">
			<div class="col-xs-12">
				<div class="btn-group">
					{if !empty($showReturnToList) && !empty($object) && !empty($object->formId)}
						<a class="btn btn-default" href='/{$module}/{$toolName}?objectAction=list&formId={$object->formId}'><i class="fas fa-arrow-alt-circle-left" role="presentation"></i> {translate text="Return to List" isAdminFacing=true}</a>
					{elseif !empty($showReturnToList) && !empty($object) && !empty($object->pollId)}
						<a class="btn btn-default" href='/{$module}/{$toolName}?objectAction=list&pollId={$object->pollId}'><i class="fas fa-arrow-alt-circle-left" role="presentation"></i> {translate text="Return to List" isAdminFacing=true}</a>
					{elseif !empty($showReturnToList)}
						<a class="btn btn-default" href='/{$module}/{$toolName}?objectAction=list'><i class="fas fa-arrow-alt-circle-left" role="presentation"></i> {translate text="Return to List" isAdminFacing=true}</a>
					{/if}
					{if !empty($id)}
						<a class="btn btn-default" href='/{$module}/{$toolName}?id={$id}&amp;objectAction=history'><i class="fas fa-history" role="presentation"></i> {translate text="History" isAdminFacing=true}</a>
					{/if}
				</div>
				<div class="btn-group">
					{if !empty($id) && $canCopy}
						{if $hasCopyOptions}
							<a class="btn btn-default" onclick="AspenDiscovery.Admin.showCopyOptions('{$module}', '{$toolName}', {$id})"><i class="fas fa-copy" role="presentation"></i> {translate text="Copy" isAdminFacing=true}</a>
						{else}
							<a class="btn btn-default" href='/{$module}/{$toolName}?sourceId={$id}&amp;objectAction=copy'><i class="fas fa-copy" role="presentation"></i> {translate text="Copy" isAdminFacing=true}</a>
						{/if}
					{/if}
				</div>
				<div class="btn-group">
					{if !empty($id) && $canShareToCommunity}
						<a class="btn btn-default" href='/{$module}/{$toolName}?sourceId={$id}&amp;objectAction=shareForm'><i class="fas fa-file-upload" role="presentation"></i> {translate text="Share with Community" isAdminFacing=true}</a>
					{/if}
				</div>
				<div class="btn-group" role="group">
					{if !empty($id) && $id > 0 && $canDelete && $object->canActiveUserDelete()}<a class="btn btn-danger" href='/{$module}/{$toolName}?id={$id}&amp;objectAction=delete' onclick='return confirm("{translate text='Are you sure you want to delete this %1%?' 1=$objectType inAttribute=true isAdminFacing=true}")'><i class="fas fa-trash" role="presentation"></i> {translate text="Delete" isAdminFacing=true}</a>{/if}
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-xs-12">
				<div class="btn-group-sm">
					{foreach from=$additionalObjectActions item=action}
						<a class="btn btn-default"{if !empty($action.url)} href='{$action.url}'{/if}{if !empty($action.onclick)} onclick="{$action.onclick}"{/if} {if !empty($action.target) && ($action.target == "_blank")}target="_blank" {/if} >{if !empty($action.target) && ($action.target == "_blank")}<i class="fas fa-external-link-alt" role="presentation"></i> {/if} {translate text=$action.text isAdminFacing=true}</a>
					{/foreach}
				</div>
			</div>
		</div>

		{if !empty($allowSearchingProperties)}
			<form role="form" class="searchForm">
				<div class="alert alert-info">
					<label for="propertySearch">{translate text="Search for a Property" isAdminFacing=true}</label>
					<div class="input-group input-group-sm">
						<input  type="text" name="propertySearch" id="propertySearch"
								onkeyup="return AspenDiscovery.Admin.searchProperties();" class="form-control" />
						<span class="input-group-btn"><button class="btn btn-default" type="button" onclick="$('#propertySearch').val('');return AspenDiscovery.Admin.searchProperties();" title="{translate text="Clear" inAttribute=true isAdminFacing=true}"><i class="fas fa-times-circle" role="presentation"></i></button></span>
						<script type="text/javascript">
							{literal}
							$(document).ready(function() {
								$("#propertySearch").keydown("keydown", function (e) {
									if (e.which === 13) {
										e.preventDefault();
									}
								});
							});
							{/literal}
						</script>
					</div>
				</div>
			</form>
		{/if}
		{if empty('formLabel')}
			{assign var="formLabel" value=$pageTitleShort}
		{/if}
		{include file="DataObjectUtil/objectEditForm.tpl"}
	</div>
{/strip}
