<div class="col-xs-12">
	{if $title}
		<h1>{$title}</h1>
	{/if}
	{foreach from=$rows item=row}
		{if !empty($row->rowTitle)}
			{if $row->makeAccordion == '1'}<div class="panel customAccordionRow" id="{$row->id}-Panel">{/if}
			{if $row->makeAccordion != '1'}<div class="row" style="display:flex">{/if}
					{if $row->makeAccordion == '1'}<a data-toggle="collapse" href="#{$row->id}-PanelBody">{/if}
					<div class="col-tn-12 {if $row->makeAccordion == '1'}panel-heading{/if}">
						{if $row->makeAccordion == '1'}<div class="panel-title">{/if}
							{if $row->makeAccordion == '0'}<h2>{$row->rowTitle}</h2>{else}{$row->rowTitle}{/if}
						{if $row->makeAccordion == '1'}</div>{/if}
					</div>
					{if $row->makeAccordion == '1'}</a>{/if}
		{if $row->makeAccordion != '1'}</div>{/if}
		{/if}
				<div class="row{if $row->makeAccordion == '1'} panel-collapse collapse{/if}" {if $row->makeAccordion == '1'}id="{$row->id}-PanelBody"{else} style="display:flex"{/if}>
					{if $row->makeAccordion == '1'}<div class="panel-body">{/if}
					{foreach from=$row->getCells() item=cell}
						<div class="portal-cell col-tn-{$cell->widthTiny} col-xs-{$cell->widthXs} col-sm-{$cell->widthSm} col-md-{$cell->widthMd} col-lg-{$cell->widthLg}" style="align-self: {if $cell->verticalAlignment != ''}{$cell->verticalAlignment}{else}flex-start{/if}; {if $cell->horizontalJustification != ''}text-align:{$cell->horizontalJustification}{/if}">
							{$cell->getContents()}
						</div>
					{/foreach}
						{if $row->makeAccordion == '1'}</div>{/if}
				</div>
			{if $row->makeAccordion == '1'}</div>{/if}
	{/foreach}
</div>