{strip}
	<p class="alert alert-info" id="masqueradeLoading" style="display: none">{translate text="Starting Masquerade Mode" isPublicFacing=true}</p>
	<p class="alert alert-danger" id="masqueradeAsError" style="display: none"></p>
	<form id="masqueradeForm" class="form-horizontal" role="form">
		<div id="loginUsernameRow" class="form-group">
			<label for="cardNumber" class="control-label col-xs-12 col-sm-4">{translate text="Library Card Number" isPublicFacing=true}</label>
			<div class="col-xs-12 col-sm-8">
				<input type="text" name="cardNumber" id="cardNumber" value="" size="28" class="form-control">
			</div>
		</div>
		{if $supportsLoginWithUsername && $allowMasqueradeWithUsername}
			<div id="masqueradeAsChoice" class="form-group">
				<div class="col-xs-12 col-sm-8 col-sm-offset-4">
					{translate text="- or -" isPublicFacing=true}
				</div>
			</div>
			<div id="loginUsernameRow" class="form-group">
				<label for="cardNumber" class="control-label col-xs-12 col-sm-4">{translate text="masquerade_username" defaultText="Username" isPublicFacing=true}</label>
				<div class="col-xs-12 col-sm-8">
					<input type="text" name="username" id="username" value="" size="28" class="form-control">
				</div>
			</div>
		{/if}
	</form>
	<script type="text/javascript">
		$('#cardNumber').focus().select();
		{literal}
		$(document).ready(function () {
			$("#masqueradeForm").validate({
				submitHandler: function (form) {
					AspenDiscovery.Account.initiateMasquerade();
				}
			});

			$("#masqueradeForm").on("keydown", function (event) {
				if (event.key === "Enter" && !$(event.target).is("textarea")) {
					event.preventDefault();
					if ($("#masqueradeForm").valid()) {
						$("#masqueradeForm").submit();
					}
				}
			});
		});
		{/literal}

	</script>
{/strip}