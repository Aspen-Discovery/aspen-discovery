{strip}
	<div id="home-page-login" class="text-center row">
		{if $masqueradeMode}
			<div class="sidebar-masquerade-section">
				<div class="endMasqueradeOptions hidden-phone" >
					<a id="masqueradedMyAccountNameLink" href="/MyAccount/Home">{translate text="Masquerading As %1%" 1=$userDisplayName}</a>
					<div class="bottom-border-line"></div> {* divs added to aid anythink styling. plb 11-19-2014 *}
				</div>
				<div class="endMasqueradeOptions">
					<a href="#" onclick="AspenDiscovery.Account.endMasquerade()" id="endMasqueradeLink">{translate text="End Masquerading"}</a>
					<div class="bottom-border-line"></div>
				</div>
			</div>
			<div class="logoutOptions hidden-phone" {if !$loggedIn} style="display: none;"{/if}>
				<a id="myAccountNameLink" href="/MyAccount/Home">{translate text="Logged In As %1%" 1=$guidingUser->displayName }</a>
				<div class="bottom-border-line"></div> {* divs added to aid anythink styling. plb 11-19-2014 *}
			</div>
			<div class="logoutOptions">
				<a href="/MyAccount/Logout" id="logoutLink" >{translate text="Sign Out"}</a>
				<div class="bottom-border-line"></div>
			</div>

		{else}
			<div class="logoutOptions hidden-phone" {if !$loggedIn} style="display: none;"{/if}>
				<a id="myAccountNameLink" href="/MyAccount/Home">{translate text="Logged In As %1%" 1=$userDisplayName }</a>
				<div class="bottom-border-line"></div> {* divs added to aid anythink styling. plb 11-19-2014 *}
			</div>
			<div class="logoutOptions" {if !$loggedIn} style="display: none;"{/if}>
				<a href="/MyAccount/Logout" id="logoutLink" >{translate text="Sign Out"}</a>
				<div class="bottom-border-line"></div>
			</div>
			{if !$loggedIn}
				<div class="loginOptions" {if $loggedIn} style="display: none;"{/if}> {* TODO: don't write at all? Does it get unhidden? *}
					{if $showLoginButton == 1}
						{if $isLoginPage}
							<a class="loginLink" href="#" title="Sign in to My Account" onclick="$('#username').focus(); return false">{translate text="SIGN IN TO MY ACCOUNT"}</a>
						{else}
							<a href="/MyAccount/Home" class="loginLink" title="Sign In To My Account" onclick="return AspenDiscovery.Account.followLinkIfLoggedIn(this);" data-login="true">{translate text="LOGIN TO MY ACCOUNT"}</a>
						{/if}
						<div class="bottom-border-line"></div>
					{/if}
				</div>
			{/if}
		{/if}
	</div>
{/strip}