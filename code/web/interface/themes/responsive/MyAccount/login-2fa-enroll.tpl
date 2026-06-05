{strip}
	{if !$isModal}<div id="page-content" class="col-xs-12">
		<h1>{translate text='Two-factor Authentication' isPublicFacing=true}</h1>{/if}
		<div id="loginFormWrapper">
			<p>{translate text="You must enroll into two-factor authentication before logging in." isPublicFacing=true}</p>
	            <div class="form-group">
		            <label class="control-label">{translate text="Choose how to enroll" isPublicFacing=true}</label>
                    {if $canUseTotp}
			            <div class="radio">
				            <label for="enrollMethodTotp">
					            <input
						            type="radio"
						            name="enrollMethod"
						            id="enrollMethodTotp"
						            value="totp"
                                    {if $canUseTotp}checked="checked"{/if}
					            />
                                {translate text="Use an authenticator app (Recommended)" isPublicFacing=true}
				            </label>
				            <small class="help-block">{translate text='Examples: Google Authenticator, Microsoft Authenticator, Authy, etc.' isPublicFacing=true}</small>
			            </div>
		            {/if}
                    {if $canUseEmail}
	                    <div class="radio">
		                    <label for="enrollMethodEmail">
			                    <input
				                    type="radio"
				                    name="enrollMethod"
				                    id="enrollMethodEmail"
				                    value="email"
                                    {if !$canUseTotp}checked="checked"{/if}
			                    />
                                {translate text="Use an email address" isPublicFacing=true}
		                    </label>
		                    <small class="help-block">{translate text="Get a code sent to your email address." isPublicFacing=true}</small>
	                    </div>
		            {/if}
	            </div>
			<input type="submit" name="submit" value="{translate text="Start" isPublicFacing=true}" id="loginFormEnroll" class="btn btn-primary" onclick="var selectedMethod = document.querySelector('input[name=&quot;enrollMethod&quot;]:checked'); return AspenDiscovery.Account.show2FAEnrollment(true, selectedMethod.value);"/>
            {if !$isModal}<a id="loginFormCancelLogin" class="btn btn-warning" href="/MyAccount/Logout">{translate text="Cancel Sign In" isPublicFacing=true}</a>{/if}
		</div>
		<br/>
	{if !$isModal}</div>{/if}
{/strip}