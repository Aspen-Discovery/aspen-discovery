{strip}
	{if $genealogyData->cemeteryName}
		<div class='genealogyDataDetail'><span class='result-label'>{translate text="Cemetery Name"} </span><span class='genealogyDataDetailValue'>{$genealogyData->cemeteryName}</span></div>
	{/if}
	{if $genealogyData->cemeteryLocation}
		<div class='genealogyDataDetail'><span class='result-label'>{translate text="Cemetery Location"} </span><span class='genealogyDataDetailValue'>{$genealogyData->cemeteryLocation}</span></div>
	{/if}
	{if $genealogyData->cemeteryAvenue}
		<div class='genealogyDataDetail'><span class='result-label'>{translate text="Cemetery Avenue"} </span><span class='genealogyDataDetailValue'>{$genealogyData->cemeteryAvenue}</span></div>
	{/if}
	{if $genealogyData->addition || $genealogyData->lot || $genealogyData->block || $genealogyData->grave}
		<div class='genealogyDataDetail'><span class='result-label'>{translate text="Burial Location"}</span>
			<span class='genealogyDataDetailValue'>
				{if $genealogyData->addition}{translate text="Addition"} {$genealogyData->addition}{if $genealogyData->block || $genealogyData->lot || $genealogyData->grave}, {/if}{/if}
				{if $genealogyData->block}{translate text="Block"} {$genealogyData->block}{if $genealogyData->lot || $genealogyData->grave}, {/if}{/if}
				{if $genealogyData->lot}{translate text="Lot"} {$genealogyData->lot}{if $genealogyData->grave}, {/if}{/if}
				{if $genealogyData->grave}{translate text="Grave"} {$genealogyData->grave}{/if}
			</span>
		</div>
		{if $genealogyData->tombstoneInscription}
			<div class='genealogyDataDetail'><span class='result-label'>{translate text="Tombstone Inscription"} </span><div class='genealogyDataDetailValue'>{$genealogyData->tombstoneInscription}</div></div>
		{/if}
	{/if}
	{if $genealogyData->mortuaryName}
		<div class='genealogyDataDetail'><span class='result-label'>{translate text="Mortuary Name"} </span><span class='genealogyDataDetailValue'>{$genealogyData->mortuaryName}</span></div>
	{/if}
{/strip}