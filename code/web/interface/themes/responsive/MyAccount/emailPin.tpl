{strip}
<div id="page-content" class="col-xs-12">

	<h1>{translate text='Forget Your PIN?' isPublicFacing=true}</h1>
	<div class="alert alert-info">{translate text="Please enter your complete card number.  Your current PIN number will be sent to the email address on file for your account." isPublicFacing=true}</div>

	<form id="emailPin" method="POST" action="/MyAccount/EmailPin" class="form-horizontal">
		<div class="form-group">
			<label for="barcode" class="control-label col-xs-12 col-sm-4">{translate text="Card Number" isPublicFacing=true}<span class="required">*</span></label>
			<div class="col-xs-12 col-sm-8">
				<input name="barcode" type="text" size="14" maxlength="14" class="required form-control">
			</div>
		</div>
		<div class="form-group">
			<div class="col-xs-12 col-sm-offset-4 col-sm-8">
				<input id="emailPinSubmit" name="submit" class="btn btn-primary" type="submit" value="{translate text="Email My Pin" isPublicFacing=true inAttribute=true}">
			</div>
		</div>
	</form>
</div>
{/strip}
<script type="text/javascript">
	{literal}
	$(function () {
		$("#emailPin").validate();
	});
	{/literal}
</script>
