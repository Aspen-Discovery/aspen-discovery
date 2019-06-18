{strip}
	{assign var="numHoldsAvailableTotal" value=$user->getNumHoldsAvailableTotal()}
	{if $numHoldsAvailableTotal && $numHoldsAvailableTotal > 0}
		<div class="text-info text-center alert alert-info">
			{if !$noLink}<a href="/MyAccount/Holds" class="alert-link">{/if}
				You have <strong>{$numHoldsAvailableTotal}</strong> hold{if $numHoldsAvailableTotal !=1}s{/if} ready for pick up.
			{if !$noLink}</a>{/if}
		</div>
	{/if}
{/strip}
