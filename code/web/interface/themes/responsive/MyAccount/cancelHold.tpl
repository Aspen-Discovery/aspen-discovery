{strip}
	{if !empty($cancelResults.title) && !is_array($cancelResults.title)}
		{* for single item results *}
		<p><strong>{$cancelResults.title|removeTrailingPunctuation}</strong></p>
	{/if}
	<div class="contents">
		{if !empty($cancelResults.success)}
			<div class="alert alert-success">{$cancelResults.message}</div>
		{else}
			{if is_array($cancelResults.message)}
				{*assign var=numFailed value=$cancelResults.message|@count*}
				{assign var=totalCancelled value=$cancelResults.titles|@count}
				<div class="alert alert-warning">{translate text="%1% of %2% holds were cancelled successfully." 1=$cancelResults.numCancelled 2=$totalCancelled isPublicFacing=true}</div>
				{foreach from=$cancelResults.message item=message}
					<div class="alert alert-danger">{$message}</div>
				{/foreach}
			{else}
				<div class="alert alert-danger">{$cancelResults.message}</div>
			{/if}
		{/if}
	</div>
{/strip}