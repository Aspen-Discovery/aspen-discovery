<div id="main-content">
	<form id="totpEnrollmentForm">
		<!-- Stepper -->
		<div class="steps-form">
			<div class="steps-row setup-panel">
				<div class="steps-step">
					<a type="button" class="btn btn-info btn-circle">1</a>
					<p>{translate text="Register" isPublicFacing=true}</p>
				</div>
				<div class="steps-step">
					<a type="button" class="btn btn-default btn-circle" disabled="disabled">2</a>
					<p>{translate text="Verify" isPublicFacing=true}</p>
				</div>
				<div class="steps-step">
					<a type="button" class="btn btn-default btn-circle" disabled="disabled">3</a>
					<p>{translate text="Backup Codes" isPublicFacing=true}</p>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<h3>{translate text="Set up Authenticator App" isPublicFacing=true}</h3>
				<p>{translate text="Two-factor authentication with an authenticator app adds an extra layer of security to your account. Follow these steps to set it up." isPublicFacing=true}</p>

				<p>
					<strong>{translate text="Step 1:" isPublicFacing=true}</strong> {translate text="Download an authenticator app on your mobile device if you haven't already. Popular options include Google Authenticator, Microsoft Authenticator, or Authy." isPublicFacing=true}
					<br>
					<strong>{translate text="Step 2:" isPublicFacing=true}</strong> {translate text="Scan the QR code below with your authenticator app, or enter the secret key manually." isPublicFacing=true}
				</p>

				<div style="text-align: center">
					<div id="qrCodeContainer">
						<img id="qrCodeImage" src="{$qrCodeUri}" alt="QR Code for TOTP setup" style="max-width: 300px; background-color: #fff; padding: 10px;">
					</div>
				</div>

				<div style="text-align: center;">
					<button type="button" class="btn btn-link" id="showManualButton" onclick="$('#qrCodeContainer').hide(); $('#manualEntrySection').show(); $('#showManualButton').hide(); $('#showQRButton').show(); return false;">
                        {translate text="Can't scan? Enter manually" isPublicFacing=true}
					</button>
					<button type="button" class="btn btn-link" id="showQRButton" style="display:none;" onclick="$('#qrCodeContainer').show(); $('#manualEntrySection').hide(); $('#showManualButton').show(); $('#showQRButton').hide(); return false;">
                        {translate text="Back to QR code" isPublicFacing=true}
					</button>
				</div>

				<div id="manualEntrySection" style="display:none; text-align: center;">
					<p>{translate text="Enter this code in your authenticator app:" isPublicFacing=true}</p>
					<div style="font-family: monospace; font-size: 18px; letter-spacing: 2px; text-align: center; padding: 1em; user-select: all; -webkit-user-select: all; -moz-user-select: all; -ms-user-select: all;">
						<span id="secretKey">{$secret}</span>
					</div>
				</div>
			</div>
		</div>

		<input type="hidden" id="totpSecretId" name="totpSecretId" value="{$secretId}">
	</form>
</div>