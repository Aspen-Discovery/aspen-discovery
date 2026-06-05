{strip}
	<div id="page-content" class="col-xs-12">
		<h1>{translate text='Two-factor Authentication' isPublicFacing=true}</h1>
		<div id="loginFormWrapper">
            {if $authMethod === 'totp'}
                {if !$codeSent}
					<p>{translate text="Enter the 6-digit code from your authenticator app or provide a backup code." isPublicFacing=true}</p>
                {else}
					<p>{translate text="Enter the 6-digit code from your authenticator app or provide a backup code." isPublicFacing=true}</p>
                {/if}
            {else}
                {if !$codeSent}
					<div class="alert alert-warning">{translate text="Unable to send your authentication code. Verify your account has a valid email address. To access your account now, you may provide a backup code." isPublicFacing=true}</div>
                {else}
					<p>{translate text="Enter the code sent to your authentication method or provide a backup code." isPublicFacing=true}</p>
                {/if}
            {/if}
			<p class="alert alert-danger" id="codeVerificationFailedPlaceholder" style="display: none;"></p>
			<p id="newCodeSentPlaceholder" class="alert alert-info" style="display: none;"></p>
			<p class="alert alert-info" id="loading" style="display: none">
                {translate text="Logging you in now. Please wait." isPublicFacing=true}
			</p>
			<form id="twoFactorAuthForm" class="form-horizontal">
				<div id="loginFormFields">
					<div id="loginAuthCodeRow" class="form-group">
						<div class="col-xs-12 col-sm-4 text-right">
							<label for="code" class="control-label">{translate text="6-digit code" isPublicFacing=true}</label>
						</div>
						<div class="col-xs-12 col-sm-8">
							<input type="text" class="form-control" id="code" name="code" maxlength="6" spellcheck="false" autocomplete="false">
						</div>
					</div>
				</div>
				<div id="loginActions" class="form-group">
					<div class="col-xs-12 col-sm-offset-4 col-sm-8">
                        {if !empty($followupModule)}<input type="hidden" name="followupModule" value="{$followupModule}">{/if}
                        {if !empty($followupAction)}<input type="hidden" name="followupAction" value="{$followupAction}">{/if}
                        {if !empty($recordId)}<input type="hidden" name="recordId" value="{$recordId|escape:"html"}">{/if}
                        {if !empty($comment)}<input type="hidden" id="comment" name="comment" value="{$comment|escape:"html"}">{/if}
                        {if !empty($cardNumber)}<input type="hidden" name="cardNumber" value="{$cardNumber|escape:"html"}">{/if}
						<input type="hidden" id="myAccountAuth" value="true">
						<input type="submit" name="submit" value="{translate text="Verify" isPublicFacing=true}" id="loginFormVerify" class="btn btn-primary" onclick="return AspenDiscovery.Account.verify2FALogin();">
						&nbsp;<a id="loginFormCancelLogin" class="btn btn-warning" href="/MyAccount/Logout">{translate text="Cancel Sign In" isPublicFacing=true}</a>
					</div>
                    {if $authMethod !== 'totp'}
						<div class="col-xs-12 col-sm-offset-4 col-sm-8">
							<a class="btn btn-xs btn-link" style="display: inline-block; margin-top: 1em" onclick="return AspenDiscovery.Account.new2FACode();">{translate text="Code expired? Send another" isPublicFacing=true}</a>
						</div>
                    {/if}
				</div>
			</form>
            {if !empty($setupMethods) && count($setupMethods) > 1}
				<div class="text-center">
					<a class="btn btn-secondary" style="margin-top: 2em" onclick="$('#loginFormWrapper').toggle(); $('#altMethodWrapper').toggle(); return false;">{translate text="Try Another Method" isPublicFacing=true}</a>
				</div>
            {/if}
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
							<form id="alt-method-email" class="alt-method-form" style="display:none;">
								<div class="form-group">
									<button class="btn btn-secondary" style="margin-top: 2em" onclick="return AspenDiscovery.Account.new2FACode(false);">{translate text="Send a Code" isPublicFacing=true}</button>
								</div>
								<div class="form-group">
									<label for="code_email">{translate text='Email Code' isPublicFacing=true}</label>
									<input type="text" class="form-control alt-code-input" id="code_email" data-method="email" maxlength="6" autocomplete="off">
								</div>
								<div class="form-group">
                                    {if !empty($followupModule)}<input type="hidden" name="followupModule" value="{$followupModule}">{/if}
                                    {if !empty($followupAction)}<input type="hidden" name="followupAction" value="{$followupAction}">{/if}
                                    {if !empty($recordId)}<input type="hidden" name="recordId" value="{$recordId|escape:"html"}">{/if}
                                    {if !empty($comment)}<input type="hidden" id="comment" name="comment" value="{$comment|escape:"html"}">{/if}
                                    {if !empty($cardNumber)}<input type="hidden" name="cardNumber" value="{$cardNumber|escape:"html"}">{/if}
									<input type="hidden" id="myAccountAuth" value="true">
									<input type="submit" name="submit" value="{translate text="Verify" isPublicFacing=true}" id="loginFormVerify" class="btn btn-primary" onclick="return AspenDiscovery.Account.verify2FALogin();">
									&nbsp;<a id="loginFormCancelLogin" class="btn btn-warning" href="/MyAccount/Logout">{translate text="Cancel Sign In" isPublicFacing=true}</a>
								</div>
							</form>
                        {/if}

                        {if in_array('totp', $setupMethods)}
							<form id="alt-method-totp" class="alt-method-form" style="display:none;">
								<div class="form-group">
									<label for="code_totp">{translate text='Authenticator App Code' isPublicFacing=true}</label>
									<input type="text" class="form-control alt-code-input" id="code_totp" data-method="totp" maxlength="6" autocomplete="off">
								</div>
								<div class="form-group">
                                    {if !empty($followupModule)}<input type="hidden" name="followupModule" value="{$followupModule}">{/if}
                                    {if !empty($followupAction)}<input type="hidden" name="followupAction" value="{$followupAction}">{/if}
                                    {if !empty($recordId)}<input type="hidden" name="recordId" value="{$recordId|escape:"html"}">{/if}
                                    {if !empty($comment)}<input type="hidden" id="comment" name="comment" value="{$comment|escape:"html"}">{/if}
                                    {if !empty($cardNumber)}<input type="hidden" name="cardNumber" value="{$cardNumber|escape:"html"}">{/if}
									<input type="hidden" id="myAccountAuth" value="true">
									<input type="submit" name="submit" value="{translate text="Verify" isPublicFacing=true}" id="loginFormVerify" class="btn btn-primary" onclick="return AspenDiscovery.Account.verify2FALogin();">
									&nbsp;<a id="loginFormCancelLogin" class="btn btn-warning" href="/MyAccount/Logout">{translate text="Cancel Sign In" isPublicFacing=true}</a>
								</div>
							</form>
                        {/if}

						<form id="alt-method-backup" class="alt-method-form" style="margin-top: 2em; display: none;">
							<div id="alt-method-backup" class="alt-method-form" style="display:none;">
								<div class="form-group">
									<label for="code_backup">{translate text='Backup Code' isPublicFacing=true}</label>
									<input type="text" class="form-control alt-code-input" id="code_backup" data-method="backup" autocomplete="off">
								</div>
								<div class="form-group">
                                    {if !empty($followupModule)}<input type="hidden" name="followupModule" value="{$followupModule}">{/if}
                                    {if !empty($followupAction)}<input type="hidden" name="followupAction" value="{$followupAction}">{/if}
                                    {if !empty($recordId)}<input type="hidden" name="recordId" value="{$recordId|escape:"html"}">{/if}
                                    {if !empty($comment)}<input type="hidden" id="comment" name="comment" value="{$comment|escape:"html"}">{/if}
                                    {if !empty($cardNumber)}<input type="hidden" name="cardNumber" value="{$cardNumber|escape:"html"}">{/if}
									<input type="hidden" id="myAccountAuth" value="true">
									<input type="submit" name="submit" value="{translate text="Verify" isPublicFacing=true}" id="loginFormVerify" class="btn btn-primary" onclick="return AspenDiscovery.Account.verify2FALogin();">
									&nbsp;<a id="loginFormCancelLogin" class="btn btn-warning" href="/MyAccount/Logout">{translate text="Cancel Sign In" isPublicFacing=true}</a>
								</div>
							</div>
						</form>
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
{/strip}