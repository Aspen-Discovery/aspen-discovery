<div id="main-content">
		<form id="twoFactorAuthForm">
			<div class="row">
				<div class="col-md-10 col-md-offset-1 text-center">
					<p>{translate text="Enter the code sent to your authentication method or provide a backup code." isPublicFacing=true}</p>
					<div class="form-group text-left">
						<label for="code">{translate text="6-digit code" isPublicFacing=true}</label>
						<input type="text" class="form-control" id="code" name="code" maxlength="6" spellcheck="false" autocomplete="false">
					</div>
					<div class="alert alert-danger" id="codeVerificationFailedPlaceholder" style="display: none;"></div>
					<button class="btn btn-xs btn-link" style="margin-top: 2em" onclick="return AspenDiscovery.Account.new2FACode();">{translate text="Code expired? Send another" isPublicFacing=true}</button>
					<div id="newCodeSentPlaceholder" class="alert alert-info" style="display: none;"></div>
				</div>
			</div>
			<input type="hidden" id="referer" value="{$referer}" />
			<input type="hidden" id="name" value="{$name}" />
		</form>
</div>