{strip}
{* Add availability as needed *}
<div>
	<dl class="dl-horizontal">
		<dt>{translate text="Total Copies" isPublicFacing=true}</dt>
		<dd>{$availability->totalCopies}</dd>
		<dt>{translate text="Shared Copies" isPublicFacing=true}</dt>
		<dd>{$availability->sharedCopies}</dd>
		<dt>{translate text="Total Loan Copies" isPublicFacing=true}</dt>
		<dd>{$availability->totalLoanCopies}</dd>
		<dt>{translate text="Total Hold Copies" isPublicFacing=true}</dt>
		<dd>{$availability->totalHoldCopies}</dd>
		<dt>{translate text="Shared Loan Copies" isPublicFacing=true}</dt>
		<dd>{$availability->sharedLoanCopies}</dd>
	</dl>
</div>

{if $availability->totalHoldCopies > 0}
	{if $availability->totalHoldCopies > 1}
		<p>{translate text="There are %1% holds on this title." 1=$availability->totalHoldCopies isPublicFacing=true}</p>
	{else}
		<p>{translate text="There is 1 hold on this title." isPublicFacing=true}</p>
	{/if}
{/if}
{/strip}