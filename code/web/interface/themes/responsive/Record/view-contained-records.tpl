{strip}
	<div class="striped">
		{foreach from=$childRecords item="childRecord"}
			<div class="row contained-record">
				<div class="col-xs-7 contained-record-title">
					<a href="{$childRecord.link}">{$childRecord.label}</a>
				</div>
				<div class="col-xs-3">
					{$childRecord.format}
				</div>
				<div class="col-xs-2">
					{if !empty($childRecord.actions)}
						<div class="btn-group btn-group-vertical btn-block">
							{foreach from=$childRecord.actions item=curAction}
								{if !empty($curAction.url) && strlen($curAction.url) > 0}
									<a href="{$curAction.url}" class="btn btn-sm {if empty($curAction.btnType)}btn-action{else}{$curAction.btnType}{/if} btn-wrap" onclick="{if !empty($curAction.requireLogin)}return AspenDiscovery.Account.followLinkIfLoggedIn(this, '{$curAction.url}');{/if}" {if !empty($curAction.alt)}title="{translate text=$curAction.alt inAttribute=true isPublicFacing=true}"{/if} {if !empty($curAction.target) && ($curAction.target == "_blank")}target="{$curAction.target}"{/if}>{if !empty($curAction.target) && ($curAction.target == "_blank")}<i class="fas fa-external-link-alt"></i> {/if} {translate text=$curAction.title isPublicFacing=true}</a>
								{else}
									<a href="#" class="btn btn-sm {if empty($curAction.btnType)}btn-action{else}{$curAction.btnType}{/if} btn-wrap" onclick="{$curAction.onclick}" {if !empty($curAction.alt)}title="{translate text=$curAction.alt inAttribute=true isPublicFacing=true}"{/if}" {if !empty($curAction.target) && ($curAction.target == "_blank")}target="{$curAction.target}"{/if}>{if !empty($curAction.target) && ($curAction.target == "_blank")}<i class="fas fa-external-link-alt"></i> {/if}{translate text=$curAction.title isPublicFacing=true}</a>
								{/if}
							{/foreach}
						</div>
					{/if}
				</div>
			</div>
		{/foreach}
	</div>
{/strip}