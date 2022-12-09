{strip}
	{if count($localMarcHoldings) > 0}
		<h3>{translate text="Local Holdings" isPublicFacing=true}</a></h3>
		<table class="table table-condensed table-hover table-condensed table-sticky" aria-label="Local holdings">
			<thead>
				<tr><th>{translate text='Library' isAdminFacing=true}</th><th>{translate text='Shelf Location' isAdminFacing=true}</th><th>{translate text='Holdings' isAdminFacing=true}</th><th>{translate text='Link' isAdminFacing=true}</th></tr>
			</thead>
			<tbody>
				{foreach from=$localMarcHoldings item="marcHolding"}
					<tr>
						<td>{$marcHolding.library}</td>
						<td>{$marcHolding.shelfLocation}</td>
						<td>{implode subject=$marcHolding.holdings glue='<br/>'}</td>
						<td>{if !empty($marcHolding.link)}<a href="{$marcHolding.link}" target="_blank"><i class="fas fa-external-link-alt"></i> {$marcHolding.linkText}</a>{else}{translate text="N/A" isPublicFacing=true}{/if}</td>
					</tr>
				{/foreach}
			</tbody>
		</table>
	{/if}
	{if count($otherMarcHoldings) > 0}
		<button onclick="$('#otherMarcHoldings').show();$('#showOtherMarcHoldings').hide();return false;" id="showOtherMarcHoldings" >{translate text="Show other holdings" isPublicFacing=true}</button>
		<div id="otherMarcHoldings" style="display: none">
			<h3>{translate text="Other Holdings" isPublicFacing=true}</a></h3>
			<table class="table table-condensed table-hover table-condensed table-sticky" aria-label="Other Holdings">
				<thead>
					<tr><th>{translate text='Library' isAdminFacing=true}</th><th>{translate text='Shelf Location' isAdminFacing=true}</th><th>{translate text='Holdings' isAdminFacing=true}</th><th>{translate text='Link' isAdminFacing=true}</th></tr>
				</thead>
				<tbody>
					{foreach from=$otherMarcHoldings item="marcHolding"}
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
	{/if}
{/strip}