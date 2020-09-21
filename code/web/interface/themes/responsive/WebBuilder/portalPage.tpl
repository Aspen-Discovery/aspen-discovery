<div class="col-xs-12">
	<h1>{$title}</h1>
	{if $loggedIn && (array_key_exists('Administer All Custom Pages', $userPermissions) || array_key_exists('Administer Library Custom Pages', $userPermissions))}
		<div class="row">
			<div class="col-xs-12">
				<a href="/WebBuilder/PortalPages?id={$id}&objectAction=edit" class="btn btn-default btn-sm">{translate text=Edit}</a>
			</div>
		</div>
	{/if}
	{foreach from=$rows item=row}
		<div class="row" {*style="display: flex; flex-direction: row"*}>
			<div class="col-tn-12">
				<h2>{$row->rowTitle}</h2>
			</div>
		</div>
		<div class="row" {*style="display: flex; flex-direction: row"*}>
			{foreach from=$row->getCells() item=cell}
				<div class="portal-cell col-tn-{$cell->widthTiny} col-xs-{$cell->widthXs} col-sm-{$cell->widthSm} col-md-{$cell->widthMd} col-lg-{$cell->widthLg}" style="align-items: {$cell->verticalAlignment}; justify-items: {$cell->horizontalJustification};">
					{$cell->getContents()}
				</div>
			{/foreach}
		</div>
	{/foreach}
</div>