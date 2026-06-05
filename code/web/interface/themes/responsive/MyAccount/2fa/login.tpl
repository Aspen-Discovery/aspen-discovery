<div id="main-content">
    {if !empty($message)}
		<h1>{translate text='Sign in to your account' isPublicFacing=true}</h1>
    {/if}

	<div id="primaryMethodWrapper">
		<form id="twoFactorAuthForm" onsubmit="AspenDiscovery.Account.verify2FALogin(); return false;">
			<div class="row">
				<div class="col-md-10 col-md-offset-1">
                    {if !$codeSent && !$hasTotp}
						<div class="alert alert-warning">{translate text="Unable to send your authentication code. Verify your account has a valid email address. To access your account now, you may provide a backup code." isPublicFacing=true}</div>
                    {/if}
                    {if $hasTotp}
						<p>{translate text="Enter the code from your authenticator app or provide a backup code." isPublicFacing=true}</p>
                    {else}
						<p>{translate text="Enter the code sent to your email address or provide a backup code." isPublicFacing=true}</p>
                    {/if}
					<div class="form-group">
						<label for="code">{translate text="6-digit code" isPublicFacing=true}</label>
						<input type="text" class="form-control" id="code" name="code" maxlength="6" spellcheck="false" autocomplete="false">
					</div>
					<div class="alert alert-danger" id="codeVerificationFailedPlaceholder" style="display: none;"></div>
                    {if $hasEmail && !$hasTotp}
						<a class="btn btn-xs btn-link" style="margin-top: 2em" onclick="return AspenDiscovery.Account.new2FACode();">{translate text="Code expired? Send another" isPublicFacing=true}</a>
						<div id="newCodeSentPlaceholder" class="alert alert-info" style="display: none;"></div>
                    {/if}
                    {if $hasTotp && $hasEmail}
						<div class="text-center">
							<a class="btn btn-secondary" style="margin-top: 2em" onclick="$('#primaryMethodWrapper').toggle(); $('#altMethodWrapper').toggle(); return false;">{translate text="Try Another Method" isPublicFacing=true}</a>
						</div>
                    {/if}
				</div>
			</div>
			<input type="hidden" id="referer" value="{$referer}" />
			<input type="hidden" id="name" value="{$name}" />
			<input type="hidden" id="myAccountAuth" value="false">
			<input type="hidden" id="authMethod" name="authMethod" value="{if $hasTotp}totp{else}email{/if}">
			<input type="hidden" id="usingAltMethod" name="usingAltMethod" value="0">
		</form>
	</div>

	<div id="altMethodWrapper" style="display: none;">
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<p>{translate text='Select an alternative authentication method to continue:' isPublicFacing=true}</p>

				<div style="margin-top: 2em;">
					<div class="list-group" role="group" aria-label="{translate text='Authentication Methods' isPublicFacing=true}">
                        {if in_array('email', $setupMethods)}
							<a href="#" class="list-group-item alt-method-toggle" data-target="#alt-method-email">
								{translate text='Email' isPublicFacing=true}
							</a>
                        {/if}
                        {if in_array('totp', $setupMethods)}
							<a href="#" class="list-group-item alt-method-toggle" data-target="#alt-method-totp">
								{translate text='Authenticator App' isPublicFacing=true}
							</a>
                        {/if}
						<a href="#" class="list-group-item alt-method-toggle" data-target="#alt-method-backup">
							{translate text='Backup Code' isPublicFacing=true}
						</a>
					</div>

                    {if in_array('email', $setupMethods)}
	                    <div id="alt-method-email" class="alt-method-form" style="display:none;">
		                    <div class="form-group">
			                    <button class="btn btn-secondary" style="margin-top: 2em" onclick="return AspenDiscovery.Account.new2FACode(false);">{translate text="Send a Code" isPublicFacing=true}</button>
		                    </div>
		                    <div class="form-group">
			                    <label for="code_email">{translate text='Email Code' isPublicFacing=true}</label>
			                    <input type="text" class="form-control alt-code-input" id="code_email" data-method="email" maxlength="6" autocomplete="off">
		                    </div>
	                    </div>
                    {/if}

                    {if in_array('totp', $setupMethods)}
	                    <div id="alt-method-totp" class="alt-method-form" style="display:none;">
		                    <div class="form-group">
			                    <label for="code_totp">{translate text='Authenticator App Code' isPublicFacing=true}</label>
			                    <input type="text" class="form-control alt-code-input" id="code_totp" data-method="totp" maxlength="6" autocomplete="off">
		                    </div>
	                    </div>
                    {/if}

					<div id="alt-method-backup" class="alt-method-form" style="margin-top: 2em; display: none;">
						<div id="alt-method-backup" class="alt-method-form" style="display:none;">
							<div class="form-group">
								<label for="code_backup">{translate text='Backup Code' isPublicFacing=true}</label>
								<input type="text" class="form-control alt-code-input" id="code_backup" data-method="backup" autocomplete="off">
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<script>
		$('.alt-method-toggle').on('click', function(e){
			e.preventDefault();
			var target = $(this).data('target');
			$('.alt-method-toggle').removeClass('active');
			$(this).addClass('active');
			$('.alt-method-form').hide();
			$(target).show();
		});
		$('.alt-method-form').hide();
		$('.alt-method-toggle').removeClass('active');
	</script>
</div>