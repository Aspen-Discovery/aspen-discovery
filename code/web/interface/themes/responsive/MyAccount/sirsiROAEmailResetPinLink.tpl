{strip}
	<div id="page-content" class="col-xs-12">

		<h2>{translate text='Reset My PIN / Password' isPublicFacing=true}</h2>
		<div class="alert alert-info">{translate text="Please enter your complete card number or username.  An email will be sent to the email address on file for your account containing a link to reset your %1%." 1=$passwordLabel translateParameters=true isPublicFacing=true}</div>

		<form id="emailResetPin" method="POST" action="/MyAccount/EmailResetPin" class="form-horizontal">
			<div class="form-group">
				<label for="barcode" class="control-label col-xs-12 col-sm-4">{translate text=$usernameLabel isPublicFacing=true}</label>
				<div class="col-xs-12 col-sm-8">
					<input id="barcode" name="barcode" type="text" size="14" maxlength="14" class="required form-control">
				</div>
			</div>
			<div class="form-group">
				<div class="col-xs-12 col-sm-offset-4 col-sm-8">
					<button type="submit" id="emailPinSubmit" name="submit" class="btn btn-primary">
						{translate text="Reset My PIN" isPublicFacing=true}
					</button>
				</div>
			</div>
		</form>
	</div>
{/strip}
<script type="text/javascript">
	{literal}
	$(function () {
		$("#emailResetPin").validate();
	});
	{/literal}
</script>
