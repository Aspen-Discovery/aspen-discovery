{strip}
<div class="btn-toolbar">
	<div class="btn-group btn-group-vertical btn-block">
		{* actions *}
		{foreach from=$actions item=curAction}
			<a href="{$curAction.url}" {if !empty($curAction.target) && empty($curAction.requireLogin)}target="{$curAction.target}"{/if} {if !empty($curAction.id)}id="{$curAction.id}"{/if} {if !empty($curAction.requireLogin)}onclick="return AspenDiscovery.Account.followLinkIfLoggedIn(this, '{$curAction.url}');"{elseif !empty($curAction.onclick)}onclick="{$curAction.onclick}"{/if} {if !empty($curAction['data-needs-refresh'])}data-needs-refresh="{$curAction['data-needs-refresh']}"{/if} {if !empty($curAction['data-record-id'])}data-record-id="{$curAction['data-record-id']}"{/if} {if !empty($curAction['data-record-source'])}data-record-source="{$curAction['data-record-source']}"{/if} class="btn btn-sm {if empty($curAction.btnType)}btn-action{else}{$curAction.btnType}{/if} btn-wrap">{if !empty($curAction.target) && $curAction.target == "_blank"}<i class="fas fa-external-link-alt" role="presentation"></i> {/if}{$curAction.title}</a>
		{/foreach}
	</div>
</div>
{/strip}