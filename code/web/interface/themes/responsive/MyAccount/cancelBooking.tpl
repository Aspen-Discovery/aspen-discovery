{strip}
	<div class="contents">
		{if $cancelResults.success}
			<div class="alert alert-success">{$cancelResults.message}</div>
		{else}
			{if is_array($cancelResults.message)}
				<div class="alert alert-warning">
					{if $totalCancelled}
						<strong>{$numCancelled} of {$totalCancelled}</strong> scheduled items were cancelled successfully.
					{else}
						Some of the attempted cancellations failed.
					{/if}
				</div>
				{foreach from=$cancelResults.message item=message}
					<div class='alert alert-danger'>{$message}</div>
				{/foreach}
			{else}
				<div class='alert alert-danger'>{$cancelResults.message}</div>
			{/if}
		{/if}
	</div>
{/strip}