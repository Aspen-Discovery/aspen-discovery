<div class="col-xs-12">
	{if $title}
		<h1>{$title}</h1>
	{/if}
	{foreach from=$rows item=row}
		{if !empty($row->rowTitle)}
			<div class="row" {*style="display: flex; flex-direction: row"*}>
				<div class="col-tn-12">
					<h2>{$row->rowTitle}</h2>
				</div>
			</div>
		{/if}
		<div class="row" {*style="display: flex; flex-direction: row"*}>
			{foreach from=$row->getCells() item=cell}
				<div class="portal-cell col-tn-{$cell->widthTiny} col-xs-{$cell->widthXs} col-sm-{$cell->widthSm} col-md-{$cell->widthMd} col-lg-{$cell->widthLg}" style="align-items: {$cell->verticalAlignment}; justify-items: {$cell->horizontalJustification};">
					{$cell->getContents()}
				</div>
			{/foreach}
		</div>
	{/foreach}
</div>