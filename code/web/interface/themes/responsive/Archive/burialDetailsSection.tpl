{strip}
	{if $genealogyData->cemeteryName}
		<div class='genealogyDataDetail'><span class='result-label'>Cemetery Name: </span><span class='genealogyDataDetailValue'>{$genealogyData->cemeteryName}</span></div>
	{/if}
	{if $genealogyData->cemeteryLocation}
		<div class='genealogyDataDetail'><span class='result-label'>Cemetery Location: </span><span class='genealogyDataDetailValue'>{$genealogyData->cemeteryLocation}</span></div>
	{/if}
	{if $genealogyData->cemeteryAvenue}
		<div class='genealogyDataDetail'><span class='result-label'>Cemetery Avenue: </span><span class='genealogyDataDetailValue'>{$genealogyData->cemeteryAvenue}</span></div>
	{/if}
	{if $genealogyData->addition || $genealogyData->lot || $genealogyData->block || $genealogyData->grave}
		<div class='genealogyDataDetail'><span class='result-label'>Burial Location:</span>
			<span class='genealogyDataDetailValue'>
									{if $genealogyData->addition}Addition {$genealogyData->addition}{if $genealogyData->block || $genealogyData->lot || $genealogyData->grave}, {/if}{/if}
				{if $genealogyData->block}Block {$genealogyData->block}{if $genealogyData->lot || $genealogyData->grave}, {/if}{/if}
				{if $genealogyData->lot}Lot {$genealogyData->lot}{if $genealogyData->grave}, {/if}{/if}
				{if $genealogyData->grave}Grave {$genealogyData->grave}{/if}
								</span>
		</div>
		{if $genealogyData->tombstoneInscription}
			<div class='genealogyDataDetail'><span class='result-label'>Tombstone Inscription: </span><div class='genealogyDataDetailValue'>{$genealogyData->tombstoneInscription}</div></div>
		{/if}
	{/if}
	{if $genealogyData->mortuaryName}
		<div class='genealogyDataDetail'><span class='result-label'>Mortuary Name: </span><span class='genealogyDataDetailValue'>{$genealogyData->mortuaryName}</span></div>
	{/if}
{/strip}