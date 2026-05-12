{strip}
<h1>{translate text='Register New Patron' isAdminFacing=true}</h1>
<div class="page">
	{if !empty($error)}
		<div class="alert alert-warning">{$error}</div>
	{/if}
	<div id="staffRegistrationFormContainer" data-patron-category-meta='{$categoryMeta|json_encode}' data-child-needs-guarantor='{if !empty($childNeedsGuarantor)}1{else}0{/if}'>
		{$staffRegForm}
	</div>
</div>
{/strip}
