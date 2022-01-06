<div id="main-content">
	<form class="form-horizontal">
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
					<p>{translate text="Backup" isPublicFacing=true}</p>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-md-10 col-md-offset-1">
			<h3 class="text-center">{translate text="Choose a verification method" isPublicFacing=true}</h3>
				<div class="radio">
					<label>
						<input type="radio" name="verificationMethod" id="email" value="email" checked>
						{translate text="Email to" isPublicFacing=true} <b>{$emailAddress}</b>
					</label>
				</div>
			<span class="help-block text-center" style="margin-top: 2em">{translate text="You'll receive a 6-digit verification code by email." isPublicFacing=true}</span>
			</div>
			</div>
	</form>
</div>