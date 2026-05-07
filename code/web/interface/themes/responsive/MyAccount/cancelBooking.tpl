{strip}
	<div class="contents">
		{if !empty($cancelResults.success)}
			<div class="alert alert-success">{$cancelResults.message}</div>
		{elseif !empty($cancelResults.message)}
			<div class="alert alert-danger">{$cancelResults.message}</div>
		{/if}
	</div>
{/strip}
