<div id="portal-rows">
{foreach from=$propValue item=subObject}
	{assign var=portalRow value=$subObject}
	{include file="DataObjectUtil/portalRow.tpl"}
{/foreach}
</div>
<div class="row">
	<div class="col-xs-12">
		<div class="btn btn-sm btn-primary" onclick="AspenDiscovery.WebBuilder.addRow({$id});">{translate text="Add Row" isPublicFacing=true}</div>
	</div>
</div>