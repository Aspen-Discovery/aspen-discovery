{strip}
{* Add availability as needed *}
<div>
	<dl class="dl-horizontal">
		<dt>{translate text="Total Copies"}</dt>
		<dd>{$availability->totalCopies}</dd>
		<dt>{translate text="Shared Copies"}</dt>
		<dd>{$availability->sharedCopies}</dd>
		<dt>{translate text="Total Loan Copies"}</dt>
		<dd>{$availability->totalLoanCopies}</dd>
		<dt>{translate text="Total Hold Copies"}</dt>
		<dd>{$availability->totalHoldCopies}</dd>
		<dt>{translate text="Shared Loan Copies"}</dt>
		<dd>{$availability->sharedLoanCopies}</dd>
	</dl>
</div>

{if $availability->totalHoldCopies > 0}
	{if $availability->totalHoldCopies > 1}
		<p>There are {$availability->totalHoldCopies} holds on this title.</p>
	{else}
		<p>There is {$availability->totalHoldCopies} hold on this title.</p>
	{/if}
{/if}
{/strip}