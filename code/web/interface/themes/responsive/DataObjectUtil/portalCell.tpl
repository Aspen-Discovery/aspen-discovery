<div id="portal-cell-{$portalCell->id}" class="portal-cell-edit col-tn-{$portalCell->widthTiny} col-xs-{$portalCell->widthXs} col-sm-{$portalCell->widthSm} col-md-{$portalCell->widthMd} col-lg-{$portalCell->widthLg}">
	<div class="row portal-cell-title-edit">
		<div class="col-xs-8">
			{translate text=$portalCell->sourceType isAdminFacing=true}{if !empty($portalCell->sourceId)} : {$portalCell->sourceId}{/if}
		</div>
		<div class="col-xs-4 text-right">
			{if $portalCell->weight != 0}<span class="btn btn-xs btn-default" onclick="return AspenDiscovery.WebBuilder.moveCell('{$portalCell->id}', 'left');" title="{translate text="Move Left" isAdminFacing=true inAttribute=true}"><i class="fas fa-caret-left fa"></i></span>{/if}
			<a href="/WebBuilder/PortalCells?objectAction=edit&id={$portalCell->id}" class="btn btn-xs btn-default" title="{translate text="Edit" isAdminFacing=true inAttribute=true}"><i class="fas fa-edit fa"></i></a>
			<span class="btn btn-xs btn-danger"  onclick="return AspenDiscovery.WebBuilder.deleteCell('{$portalCell->id}')" title="{translate text="Delete" isAdminFacing=true inAttribute=true}"><i class="fas fa-minus-circle fa"></i></span>
			{if !$portalCell->isLastCell()}<span class="btn btn-xs btn-default" onclick="return AspenDiscovery.WebBuilder.moveCell('{$portalCell->id}', 'right');" title="{translate text="Move Right" isAdminFacing=true inAttribute=true}"><i class="fas fa-caret-right fa"></i></span>{/if}
		</div>
	</div>
	<div class="row">
		<div class="col-xs-12">
			{$portalCell->getContents($inPageEditor)}
		</div>
	</div>
</div>