{strip}
	<div class="fixed-height-table">
		<table class="table table-condensed table-hover table-condensed table-sticky" aria-label="Cron Log">
			<thead>
				<tr><th>{translate text='Library' isAdminFacing=true}</th><th>{translate text='Shelf Location' isAdminFacing=true}</th><th>{translate text='Holdings' isAdminFacing=true}</th></tr>
			</thead>
			<tbody>
				{foreach from=$marcHoldings item="marcHolding"}
					<tr>
						<td>{$marcHolding.library}</td>
						<td>{$marcHolding.shelfLocation}</td>
						<td>{implode subject=$marcHolding.holdings glue='<br/>'}</td>
					</tr>
				{/foreach}
			</tbody>
		</table>
	</div>
{/strip}