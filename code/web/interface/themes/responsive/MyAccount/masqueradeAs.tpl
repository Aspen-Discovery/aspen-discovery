{strip}
	<p class="alert alert-info" id="masqueradeLoading" style="display: none">{translate text="Starting Masquerade Mode" isAdminFacing=true}</p>
	{if !empty($error)}
	<p class="alert alert-danger" id="masqueradeAsError">{$error}</p>
	{/if}

<form id="masqueradeForm" class="form-horizontal" role="form">
	<div id="loginUsernameRow" class="form-group">
		<label for="cardNumber" class="control-label col-xs-12 col-sm-4">{translate text="Library Card Number" isPublicFacing=true}</label>
		<div class="col-xs-12 col-sm-8">
			<input type="text" name="cardNumber" id="cardNumber" value="{$cardNumber|escape}" size="28" class="form-control required">
		</div>
	</div>
	<button class="tool btn btn-primary" onclick="$('#masqueradeForm').submit()">{translate text="Start" isAdminFacing=true}</button>
</form>
	<script type="text/javascript">
		$('#cardNumber').focus().select();
		{literal}
		$("#masqueradeForm").validate({
			submitHandler: function(){
				AspenDiscovery.Account.initiateMasquerade();
			}
		});
		{/literal}

	</script>
{/strip}