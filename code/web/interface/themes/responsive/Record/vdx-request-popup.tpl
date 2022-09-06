{strip}
<div id="page-content" class="content">
	{if $fromHoldError}
		<p class="alert alert-danger">{translate text='Sorry, we could not place a hold on that title for you. Would you like to make an Interlibrary Loan Request instead?' isPublicFacing='true'}</p>
	{/if}
	{$vdxFormFields}
</div>
{/strip}