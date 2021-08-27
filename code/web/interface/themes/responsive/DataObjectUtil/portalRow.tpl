<div class="portal-row-edit row" id="portalRow{$portalRow->id}">
	<div class="col-xs-12 portal-row-title-edit">
		{if !empty($portalRow->rowTitle)}
			<h2>{$portalRow->rowTitle}</h2>
		{/if}
	</div>

	<div class="col-xs-12">
		<div class="row">
			<div class="col-sm-11">
				{* Show each cell under the title *}
				<div class="row" id="portal-row-cells-{$portalRow->id}">
					{foreach from=$portalRow->getCells() item=portalCell}
						{include file='DataObjectUtil/portalCell.tpl'}
					{/foreach}
				</div>
			</div>

			{* Actions to delete the row or edit properties *}
			<div class="col-sm-1 text-center">
				<div class="btn-group-vertical btn-group-xs">
					{if $portalRow->weight != 0}<span class="btn btn-xs btn-default" onclick="return AspenDiscovery.WebBuilder.moveRow('{$portalRow->id}', 'up');" title="{translate text="Move Up"}"><i class="fas fa-caret-up fa"></i></span>{/if}
					<a href="/WebBuilder/PortalRows?objectAction=edit&id={$portalRow->id}" class="btn btn-xs btn-primary btn-wrap" title="{translate text="Edit Row" isAdminFacing=true inAttribute=true}"><i class="fas fa-edit fa"></i></a>
					<span class="btn btn-xs btn-default btn-wrap" onclick="return AspenDiscovery.WebBuilder.addCell('{$portalRow->id}')" title="{translate text="Add Cell" isAdminFacing=true inAttribute=true}"><i class="fas fa-plus-circle fa"></i></span>
					<span class="btn btn-xs btn-danger btn-wrap btn-danger" onclick="return AspenDiscovery.WebBuilder.deleteRow('{$portalRow->id}')" title="{translate text="Delete Row" isAdminFacing=true inAttribute=true}"><i class="fas fa-minus-circle fa"></i></span>
					{if !$portalRow->isLastRow()}<span class="btn btn-xs btn-default" onclick="return AspenDiscovery.WebBuilder.moveRow('{$portalRow->id}', 'down');" title="{translate text="Move Down"}"><i class="fas fa-caret-down fa"></i></span>{/if}
				</div>
			</div>
		</div>
	</div>

</div>