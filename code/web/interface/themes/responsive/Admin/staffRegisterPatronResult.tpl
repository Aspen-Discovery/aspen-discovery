{strip}
<h1>{translate text='Patron Registered' isAdminFacing=true}</h1>
<div class="page">
	<div class="alert alert-success">
		{translate text='The new patron account has been created. Please provide the credentials below to the patron — this information will not be shown again.' isAdminFacing=true}
	</div>
	<div class="alert alert-info">
		{if !empty($result.barcode)}
			<p><strong>{translate text='Barcode' isAdminFacing=true}:</strong> {$result.barcode}</p>
		{/if}
		{if !empty($result.username)}
			<p><strong>{translate text='Username' isAdminFacing=true}:</strong> {$result.username}</p>
		{/if}
		{if !empty($result.password)}
			<p><strong>{translate text='Initial password' isAdminFacing=true}:</strong> {$result.password}</p>
		{/if}
		{if !empty($result.message)}
			<p>{$result.message}</p>
		{/if}
		{if !empty($result.requirePinReset)}
			<p>{translate text='The patron will need to reset their PIN before they can log in.' isAdminFacing=true}</p>
		{/if}
	</div>
	<a href="/Admin/StaffRegisterPatron" class="btn btn-primary">{translate text='Register another patron' isAdminFacing=true}</a>
	<a href="/Admin/Home" class="btn btn-default">{translate text='Back to Administration Home' isAdminFacing=true}</a>
</div>
{/strip}
