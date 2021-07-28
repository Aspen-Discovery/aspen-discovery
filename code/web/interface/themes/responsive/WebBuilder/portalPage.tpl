<div class="col-xs-12">
	{if $title}
		<h1>{$title}</h1>
	{/if}
	{foreach from=$rows item=row}
		{if !empty($row->rowTitle)}
			{if $row->makeAccordion == '1'}<div class="panel customAccordionRow" id="{$row->id}-Panel">{/if}
			{if $row->makeAccordion != '1'}<div class="row" style="display:flex; flex-wrap: wrap;">{/if}
					{if $row->makeAccordion == '1'}<a data-toggle="collapse" href="#{$row->id}-PanelBody">{/if}
					<div class="col-tn-12 {if $row->makeAccordion == '1'}panel-heading{/if}">
						{if $row->makeAccordion == '1'}<div class="panel-title">{/if}
							{if $row->makeAccordion == '0'}<h2>{$row->rowTitle}</h2>{else}{$row->rowTitle}{/if}
						{if $row->makeAccordion == '1'}</div>{/if}
					</div>
					{if $row->makeAccordion == '1'}</a>{/if}
		{if $row->makeAccordion != '1'}</div>{/if}
		{/if}
				<div class="row{if $row->makeAccordion == '1'} panel-collapse collapse{/if}" {if $row->makeAccordion == '1'}id="{$row->id}-PanelBody"{else} style="display:flex; flex-wrap: wrap;"{/if}>
					{if $row->makeAccordion == '1'}<div class="panel-body" style="display:flex; flex-wrap: wrap;">{/if}
					{foreach from=$row->getCells() item=cell}
						{if $cell->colorScheme == 'default'}
							{assign var="backgroundColor" value='default'}
							{assign var="foregroundColor" value='default'}
							{if $cell->invertColor == '1'}
								{assign var="backgroundColor" value=$bodyTextColor}
								{assign var="foregroundColor" value=$bodyBackgroundColor}
							{/if}
						{elseif $cell->colorScheme == 'primary'}
							{assign var="backgroundColor" value=$primaryBackgroundColor}
							{assign var="foregroundColor" value=$primaryForegroundColor}
							{if $cell->invertColor == '1'}
								{assign var="backgroundColor" value=$primaryForegroundColor}
								{assign var="foregroundColor" value=$primaryBackgroundColor}
							{/if}
						{elseif $cell->colorScheme == 'secondary'}
							{assign var="backgroundColor" value=$secondaryBackgroundColor}
							{assign var="foregroundColor" value=$secondaryForegroundColor}
							{if $cell->invertColor == '1'}
								{assign var="backgroundColor" value=$secondaryForegroundColor}
								{assign var="foregroundColor" value=$secondaryBackgroundColor}
							{/if}
						{elseif $cell->colorScheme == 'tertiary'}
							{assign var="backgroundColor" value=$tertiaryBackgroundColor}
							{assign var="foregroundColor" value=$tertiaryForegroundColor}
							{if $cell->invertColor == '1'}
								{assign var="backgroundColor" value=$tertiaryForegroundColor}
								{assign var="foregroundColor" value=$tertiaryBackgroundColor}
							{/if}
						{/if}
					{literal}<style>div#customColor-cell{/literal}{$cell->id}{literal}{color: {/literal}{$foregroundColor}{literal}; background-color: {/literal}{$backgroundColor}{literal}; padding:10px;} div#customColor-cell{/literal}{$cell->id}{literal} a{color: {/literal}{$foregroundColor}{literal}}</style>{/literal}
						<div id="customColor-cell{$cell->id}" class="portal-cell col-tn-{$cell->widthTiny} col-xs-{$cell->widthXs} col-sm-{$cell->widthSm} col-md-{$cell->widthMd} col-lg-{$cell->widthLg}" style="align-self: {if $cell->verticalAlignment != ''}{$cell->verticalAlignment}{else}flex-start{/if}; {if $cell->horizontalJustification != ''}text-align:{$cell->horizontalJustification}{/if}">
							{$cell->getContents()}
						</div>
					{/foreach}
						{if $row->makeAccordion == '1'}</div>{/if}
				</div>
			{if $row->makeAccordion == '1'}</div>{/if}
	{/foreach}
</div>