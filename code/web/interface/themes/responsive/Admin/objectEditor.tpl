<script src="/tinymce/tinymce-emoji/plugin.min.js"></script>
<script src="/tinymce/tinymce.min.js"></script>
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
				{if !empty($instructions)}<a href="{$instructions}"><i class="fas fa-question-circle"></i>&nbsp;{translate text="Documentation" isAdminFacing=true}</a>{/if}
			</div>
		</div>
		<div class="row">
			<div class="col-xs-12">
				<div class="btn-group">
					{if !empty($showReturnToList)}
						<a class="btn btn-default" href='/{$module}/{$toolName}?objectAction=list'><i class="fas fa-arrow-alt-circle-left"></i> {translate text="Return to List" isAdminFacing=true}</a>
					{/if}
					{if !empty($id)}
						<a class="btn btn-default" href='/{$module}/{$toolName}?id={$id}&amp;objectAction=history'><i class="fas fa-history"></i> {translate text="History" isAdminFacing=true}</a>
					{/if}
				</div>
				<div class="btn-group">
					{if !empty($id) && $canCopy}
						<a class="btn btn-default" href='/{$module}/{$toolName}?sourceId={$id}&amp;objectAction=copy'><i class="fas fa-copy"></i> {translate text="Copy" isAdminFacing=true}</a>
					{/if}
				</div>
				<div class="btn-group">
					{if !empty($id) && $canShareToGreenhouse}
						<a class="btn btn-default" href='/{$module}/{$toolName}?sourceId={$id}&amp;objectAction=shareForm'><i class="fas fa-file-upload"></i> {translate text="Share with Community" isAdminFacing=true}</a>
					{/if}
				</div>
				<div class="btn-group" role="group">
					{if !empty($id) && $id > 0 && $canDelete}<a class="btn btn-danger" href='/{$module}/{$toolName}?id={$id}&amp;objectAction=delete' onclick='return confirm("{translate text='Are you sure you want to delete this %1%?' 1=$objectType inAttribute=true isAdminFacing=true}")'><i class="fas fa-trash"></i> {translate text="Delete" isAdminFacing=true}</a>{/if}
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-xs-12">
				<div class="btn-group-sm">
					{foreach from=$additionalObjectActions item=action}
						<a class="btn btn-default"{if !empty($action.url)} href='{$action.url}'{/if}{if !empty($action.onclick)} onclick="{$action.onclick}"{/if} {if !empty($action.target) && ($action.target == "_blank")}target="_blank" {/if} >{if !empty($action.target) && ($action.target == "_blank")}<i class="fas fa-external-link-alt"></i> {/if} {translate text=$action.text isAdminFacing=true}</a>
					{/foreach}
				</div>
			</div>
		</div>
		{if empty('formLabel')}
			{assign var="formLabel" value=$pageTitleShort}
		{/if}
		{include file="DataObjectUtil/objectEditForm.tpl"}
	</div>
{/strip}