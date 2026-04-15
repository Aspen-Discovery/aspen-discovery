{strip}
<h1>{translate text='Register New Patron' isAdminFacing=true}</h1>
<div class="page">
	{if !empty($error)}
		<div class="alert alert-warning">{$error}</div>
	{/if}
	<div id="staffRegistrationFormContainer">
		{$staffRegForm}
	</div>
</div>
{/strip}
