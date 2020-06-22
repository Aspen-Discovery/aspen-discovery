<div id="portal-cell-{$portalCell->id}" class="portal-cell-edit col-tn-{$portalCell->widthTiny} col-xs-{$portalCell->widthXs} col-sm-{$portalCell->widthSm} col-md-{$portalCell->widthMd} col-lg-{$portalCell->widthLg}">
	<div class="row portal-cell-title-edit">
		<div class="col-xs-8">
			{$portalCell->sourceType} : {$portalCell->sourceId}
		</div>
		<div class="col-xs-4 text-right">
			<a onclick="return AspenDiscovery.WebBuilder.showEditCellForm('{$portalCell->id}')"><img src="/images/silk/edit.png" alt="{translate text="Edit"}" /></a>
			<a onclick="return AspenDiscovery.WebBuilder.deleteCell('{$portalCell->id}')"><img src="/images/silk/delete.png" alt="{translate text="Delete"}" /></a>
		</div>
	</div>
	<div class="row">
		<div class="col-xs-12">
			{$portalCell->getContents()}
		</div>
	</div>
</div>