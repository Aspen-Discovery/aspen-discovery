{strip}
<div class="btn-toolbar">
	<div class="btn-group btn-group-vertical btn-block">
		{* actions *}
		{foreach from=$actions item=curAction}
			<a href="{$curAction.url}" {if $curAction.onclick}onclick="{$curAction.onclick}"{/if} class="btn btn-sm btn-action btn-wrap">{$curAction.title}</a>
		{/foreach}
	</div>
</div>
{/strip}