{strip}
	<p class="alert alert-info" id="masqueradeLoading" style="display: none">Starting Masquerade Mode</p>
	<p class="alert alert-danger" id="masqueradeAsError" style="display: none"></p>
	{*<p class="alert alert-danger" id="cookiesError" style="display: none">It appears that you do not have cookies enabled on this computer.  Cookies are required to access account information.</p>*}

<form id="masqueradeForm" class="form-horizontal" role="form">
	<div id="loginUsernameRow" class="form-group">
		<label for="cardNumber" class="control-label col-xs-12 col-sm-4">{translate text="Library Card Number"}:</label>
		<div class="col-xs-12 col-sm-8">
			<input type="text" name="cardNumber" id="cardNumber" value="{$cardNumber|escape}" size="28" class="form-control required">
		</div>
	</div>
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