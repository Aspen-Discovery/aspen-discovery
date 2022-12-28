{strip}
{if count($items) > 0}
	{foreach from=$items item=item key=index}
	<div class="eContentHolding">
		<div class="eContentHoldingHeader">
			<div class="row">
				<div class="col-md-9">
					<strong>{$item.shelfLocation}</strong>
				</div>
			</div>
		</div>
		<div class="eContentHoldingActions">
			{* Options for the user to view online or download *}
			{foreach from=$item.actions item=link}
				<a href="{if !empty($link.url)}{$link.url}{else}#{/if}" {if !empty($link.target)}target="{$link.target}"{/if} {if !empty($link.onclick)}onclick="{$link.onclick}"{/if} class="btn btn-sm btn-action">{if !empty($curAction.target) && $curAction.target == "_blank"}<i class="fas fa-external-link-alt"></i> {/if}{translate text=$link.title isPublicFacing=true}</a>&nbsp;
			{/foreach}
		</div>
	</div>
	{/foreach}
{else}
	<p class="alert alert-warning">
		{translate text="No Links Found" isPublicFacing=true}
	</p>
{/if}
{/strip}