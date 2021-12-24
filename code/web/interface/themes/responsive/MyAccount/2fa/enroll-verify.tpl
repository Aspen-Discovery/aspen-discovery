<div id="main-content">
    {if $loggedIn}
		<form id="twoFactorAuthForm">
			<!-- Stepper -->
			<div class="steps-form">
				<div class="steps-row setup-panel">
					<div class="steps-step">
						<a type="button" class="btn btn-success btn-circle"><i class="fa fa-check"></i></a>
						<p>{translate text="Register" isPublicFacing=true}</p>
					</div>
					<div class="steps-step">
						<a type="button" class="btn btn-info btn-circle">2</a>
						<p>{translate text="Verify" isPublicFacing=true}</p>
					</div>
					<div class="steps-step">
						<a type="button" class="btn btn-default btn-circle" disabled="disabled">3</a>
						<p>{translate text="Backup" isPublicFacing=true}</p>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-10 col-md-offset-1 text-center">
				<h3>{translate text="Verify email" isPublicFacing=true}</h3>
				<p>{translate text="Enter the code sent to your email to make sure everything works." isPublicFacing=true}</p>
				<div class="form-group text-left">
					<label for="code">{translate text="6-digit code" isPublicFacing=true}</label>
					<input type="text" class="form-control" id="code" name="code" maxlength="6" spellcheck="false" autocomplete="false">
				</div>
				<div class="alert alert-danger" id="codeVerificationFailedPlaceholder" style="display: none;"></div>
				<button class="btn btn-xs btn-link" style="margin-top: 2em" onclick="return AspenDiscovery.Account.new2FACode();">{translate text="Code expired? Send another" isPublicFacing=true}</button>
				<div id="newCodeSentPlaceholder" class="alert alert-info" style="display: none;"></div>
				</div>
			</div>
		</form>
    {/if}
</div>