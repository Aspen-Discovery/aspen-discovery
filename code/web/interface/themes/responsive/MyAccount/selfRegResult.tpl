{strip}
<div id="selfRegSuccess" class="alert alert-success">
	{if !empty($selfRegistrationSuccessMessage)}
		{$selfRegistrationSuccessMessage}
	{else}
		{translate text='Congratulations, you have successfully registered for a new library card. You will have limited privileges initially.<br>	Please bring a valid ID to the library to receive a physical library card with full privileges.' isPublicFacing=true}
	{/if}
</div>
<div id="selfRegAccountInfo" class="alert alert-info">
	{if !empty($selfRegResult.barcode)}
		<p id="selfRegBarcode">{translate text='Your library card number is <strong>%1%</strong>' 1=$selfRegResult.barcode isPublicFacing=true}</p>
	{/if}
	{if !empty($selfRegResult.username)}
		<p id="selfRegUsername">{translate text='Your username is <strong>%1%</strong>' 1=$selfRegResult.username isPublicFacing=true}</p>
	{/if}
	{if !empty($selfRegResult.password)}
		<p id="selfRegPassword">{translate text='Your initial password is <strong>%1%</strong>' 1=$selfRegResult.password isPublicFacing=true}</p>
	{/if}
	{if !empty($selfRegResult.message)}
		<p id="selfRegMessage" class="alert alert-warning">{$selfRegResult.message}</p>
	{/if}
	{if !empty($selfRegResult.requirePinReset)}
		<p id="selfRegResetPin">{translate text="To login to the catalog, you must first reset your PIN." isPublicFacing=true}  <a class="btn btn-default" href="/MyAccount/EmailResetPin">{translate text="Reset PIN/Password" isPublicFacing=true}</a> </p>
	{/if}
</div>
{/strip}
