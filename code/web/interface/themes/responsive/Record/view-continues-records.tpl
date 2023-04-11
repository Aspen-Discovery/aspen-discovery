{strip}
	<div class="striped">
		{foreach from=$continuesRecords item="record"}
			<div class="row">
				<div class="col-xs-7">
					{if !empty($record.link)}<a href="{$record.link}">{/if}{$record.label}{if !empty($record.link)}</a>{/if}
				</div>
				<div class="col-xs-3">
					{$record.format}
				</div>
				<div class="col-xs-2">
					{if !empty($record.actions)}
						<div class="btn-group btn-group-vertical btn-block">
							{foreach from=$record.actions item=curAction}
								{if !empty($curAction.url) && strlen($curAction.url) > 0}
									<a href="{$curAction.url}" class="btn btn-sm {if empty($curAction.btnType)}btn-action{else}{$curAction.btnType}{/if} btn-wrap" onclick="{if !empty($curAction.requireLogin)}return AspenDiscovery.Account.followLinkIfLoggedIn(this, '{$curAction.url}');{/if}" {if !empty($curAction.alt)}title="{translate text=$curAction.alt inAttribute=true isPublicFacing=true}"{/if}>{translate text=$curAction.title isPublicFacing=true}</a>
								{else}
									<a href="#" class="btn btn-sm {if empty($curAction.btnType)}btn-action{else}{$curAction.btnType}{/if} btn-wrap" onclick="{$curAction.onclick}" {if !empty($curAction.alt)}title="{translate text=$curAction.alt inAttribute=true isPublicFacing=true}"{/if}>{translate text=$curAction.title isPublicFacing=true}</a>
								{/if}
							{/foreach}
						</div>
					{/if}
				</div>
			</div>
		{/foreach}
	</div>
{/strip}