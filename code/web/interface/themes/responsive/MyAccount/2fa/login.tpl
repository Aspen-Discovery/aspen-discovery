<div id="main-content">
    {if $message}
	    <h1>{translate text='Sign in to your account' isPublicFacing=true}</h1>
    {/if}
		<form id="twoFactorAuthForm">
			<div class="row">
				<div class="col-md-10 col-md-offset-1">
					<p>{translate text="Enter the code sent to your authentication method or provide a backup code." isPublicFacing=true}</p>
					<div class="form-group">
						<label for="code">{translate text="6-digit code" isPublicFacing=true}</label>
						<input type="text" class="form-control" id="code" name="code" maxlength="6" spellcheck="false" autocomplete="false">
					</div>
					<div class="alert alert-danger" id="codeVerificationFailedPlaceholder" style="display: none;"></div>
					<a class="btn btn-xs btn-link" style="margin-top: 2em" onclick="return AspenDiscovery.Account.new2FACode();">{translate text="Code expired? Send another" isPublicFacing=true}</a>
					<div id="newCodeSentPlaceholder" class="alert alert-info" style="display: none;"></div>
				</div>
			</div>
			<input type="hidden" id="referer" value="{$referer}" />
			<input type="hidden" id="name" value="{$name}" />
			<input type="hidden" id="myAccountAuth" value="false">
		</form>
</div>