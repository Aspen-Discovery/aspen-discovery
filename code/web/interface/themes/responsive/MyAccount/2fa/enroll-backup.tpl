<div id="main-content">
    {if $loggedIn}
		<form>
			<!-- Stepper -->
			<div class="steps-form">
				<div class="steps-row setup-panel">
					<div class="steps-step">
						<a type="button" class="btn btn-success btn-circle"><i class="fa fa-check"></i></a>
						<p>{translate text="Register" isPublicFacing=true}</p>
					</div>
					<div class="steps-step">
						<a type="button" class="btn btn-success btn-circle"><i class="fa fa-check"></i></a>
						<p>{translate text="Verify" isPublicFacing=true}</p>
					</div>
					<div class="steps-step">
						<a type="button" class="btn btn-info btn-circle">3</a>
						<p>{translate text="Backup" isPublicFacing=true}</p>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-10 col-md-offset-1 text-center">
				<h3>{translate text="Backup verification codes" isPublicFacing=true}</h3>
				<p>{translate text="With 2FA enabled for your account, you’ll need these backup codes if you aren’t able to access your email. Without a backup code, you’ll have to contact the library to recover your account." isPublicFacing=true}</p>
				<table class="table table-bordered table-striped" style="width: auto; margin: 0 auto">
                    {foreach from=$backupCodes item=code name="backupCodes"}
					<tr>
						<td><samp>{$code}</samp></td>
					</tr>
					{/foreach}
				</table>
				</div>
			</div>
		</form>
    {/if}
</div>