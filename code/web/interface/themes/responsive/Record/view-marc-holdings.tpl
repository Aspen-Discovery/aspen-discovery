{strip}
	<div class="fixed-height-table">
		<table class="table table-condensed table-hover table-condensed table-sticky" aria-label="Cron Log">
			<thead>
				<tr><th>{translate text='Library' isAdminFacing=true}</th><th>{translate text='Shelf Location' isAdminFacing=true}</th><th>{translate text='Holdings' isAdminFacing=true}</th><th>{translate text='Link' isAdminFacing=true}</th></tr>
			</thead>
			<tbody>
				{foreach from=$marcHoldings item="marcHolding"}
					<tr>
						<td>{$marcHolding.library}</td>
						<td>{$marcHolding.shelfLocation}</td>
						<td>{implode subject=$marcHolding.holdings glue='<br/>'}</td>
						<td>{if !empty($marcHolding.link)}<a href="{$marcHolding.link}" target="_blank"><i class="fas fa-external-link-alt"></i> {$marcHolding.linkText}</a>{else}{translate text="N/A" isPublicFacing=true}{/if}</td>
					</tr>
				{/foreach}
			</tbody>
		</table>
	</div>
{/strip}