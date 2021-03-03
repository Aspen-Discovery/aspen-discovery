{strip}
<div class="btn-toolbar">
	<div class="btn-group btn-group-vertical btn-block">
		{* actions *}
		{foreach from=$actions item=curAction}
			<a href="{$curAction.url}" {if $curAction.onclick}onclick="{$curAction.onclick}"{/if} class="btn btn-sm {if empty($curAction.btnType)}btn-action{else}{$curAction.btnType}{/if} btn-wrap">{$curAction.title}</a>
		{/foreach}
	</div>
</div>
{/strip}