<div id="portal-cell-{$portalCell->id}" class="portal-cell-edit col-tn-{$portalCell->widthTiny} col-xs-{$portalCell->widthXs} col-sm-{$portalCell->widthSm} col-md-{$portalCell->widthMd} col-lg-{$portalCell->widthLg}">
	<div class="row portal-cell-title-edit">
		<div class="col-xs-8">
			{$portalCell->sourceType} : {$portalCell->sourceId}
		</div>
		<div class="col-xs-4 text-right">
			{if $portalCell->weight != 0}<span class="btn btn-xs btn-default" onclick="return AspenDiscovery.WebBuilder.moveCell('{$portalCell->id}', 'left');" title="{translate text="Move Left"}">&#x25C0;</span>{/if}
			<a href="/WebBuilder/PortalCells?objectAction=edit&id={$portalCell->id}"><img src="/images/silk/edit.png" alt="{translate text="Edit"}" /></a>
			<a onclick="return AspenDiscovery.WebBuilder.deleteCell('{$portalCell->id}')"><img src="/images/silk/delete.png" alt="{translate text="Delete"}" /></a>
			{if !$portalCell->isLastCell()}<span class="btn btn-xs btn-default" onclick="return AspenDiscovery.WebBuilder.moveCell('{$portalCell->id}', 'right');" title="{translate text="Move Right"}">&#x25B6;</span>{/if}
		</div>
	</div>
	<div class="row">
		<div class="col-xs-12">
			{$portalCell->getContents()}
		</div>
	</div>
</div>