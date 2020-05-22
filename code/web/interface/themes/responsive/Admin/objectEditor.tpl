<script src="/ckeditor/ckeditor.js"></script>
{if $lastError}
	<div class="alert alert-danger">
		{$lastError}
	</div>
{/if}
{strip}
	<div class="col-xs-12">
		<div class="row">
			<div class="col-xs-12 col-md-9">
				<h1 id="pageTitle">{$pageTitleShort}{if !empty($objectName)} - {$objectName}{/if}</h1>
			</div>
			<div class="col-xs-12 col-md-3 help-link">
				{if $instructions}<a href="{$instructions}"><img src="/images/silk/help.png" alt="Help" /> Documentation</a>{/if}
			</div>
		</div>
		<p>
			{if $showReturnToList}
				<a class="btn btn-default" href='/{$module}/{$toolName}?objectAction=list'>{translate text="Return to List"}</a>
			{/if}
			<a class="btn btn-default" href='/{$module}/{$toolName}?id={$id}&amp;objectAction=history'>{translate text="History"}</a>
			{if $id > 0 && $canDelete}<a class="btn btn-danger" href='/{$module}/{$toolName}?id={$id}&amp;objectAction=delete' onclick='return confirm("Are you sure you want to delete this {$objectType}?")'>{translate text="Delete"}</a>{/if}
		</p>
		<div class="btn-group">
			{foreach from=$additionalObjectActions item=action}
				<a class="btn btn-default btn-sm"{if $action.url} href='{$action.url}'{/if}{if $action.onclick} onclick="{$action.onclick}"{/if}>{$action.text}</a>
			{/foreach}
		</div>
		{include file="DataObjectUtil/objectEditForm.tpl"}
	</div>
{/strip}