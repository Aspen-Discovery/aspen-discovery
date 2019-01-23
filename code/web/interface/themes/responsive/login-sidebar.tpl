{strip}
	<div id="home-page-login" class="text-center row"{if $displaySidebarMenu} style="display: none"{/if}>
		{if $masqueradeMode}
			<div class="sidebar-masquerade-section">
				<div class="logoutOptions hidden-phone" >
					<a id="masqueradedMyAccountNameLink" href="{$path}/MyAccount/Home">Masquerading As {$userDisplayName|capitalize}</a>
					<div class="bottom-border-line"></div> {* divs added to aid anythink styling. plb 11-19-2014 *}
				</div>
				<div class="logoutOptions">
					<a href="#" onclick="VuFind.Account.endMasquerade()" id="logoutLink">{translate text="End Masquerading"}</a>
					<div class="bottom-border-line"></div>
				</div>
			</div>
			<div class="logoutOptions hidden-phone" {if !$loggedIn} style="display: none;"{/if}>
				<a id="myAccountNameLink" href="{$path}/MyAccount/Home">Logged In As {$guidingUser->displayName|capitalize}</a>
				<div class="bottom-border-line"></div> {* divs added to aid anythink styling. plb 11-19-2014 *}
			</div>
			<div class="logoutOptions">
				<a href="{$path}/MyAccount/Logout" id="logoutLink" >{translate text="Log Out"}</a>
				<div class="bottom-border-line"></div>
			</div>

		{else}
			<div class="logoutOptions hidden-phone" {if !$loggedIn} style="display: none;"{/if}>
				<a id="myAccountNameLink" href="{$path}/MyAccount/Home">Logged In As {$userDisplayName|capitalize}</a>
				<div class="bottom-border-line"></div> {* divs added to aid anythink styling. plb 11-19-2014 *}
			</div>
			<div class="logoutOptions" {if !$loggedIn} style="display: none;"{/if}>
				<a href="{$path}/MyAccount/Logout" id="logoutLink" >{translate text="Log Out"}</a>
				<div class="bottom-border-line"></div>
			</div>
			{if !$loggedIn}
				<div class="loginOptions" {if $loggedIn} style="display: none;"{/if}> {* TODO: don't write at all? Does it get unhidden? *}
					{if $showLoginButton == 1}
						{if $isLoginPage}
							<a class="loginLink" href="#" title="Login To My Account" onclick="$('#username').focus(); return false">{translate text="LOGIN TO MY ACCOUNT"}</a>
						{else}
							<a href="{$path}/MyAccount/Home" class="loginLink" title="Login To My Account" onclick="return VuFind.Account.followLinkIfLoggedIn(this);" data-login="true">{translate text="LOGIN TO MY ACCOUNT"}</a>
						{/if}
						<div class="bottom-border-line"></div>
					{/if}
				</div>
			{/if}
		{/if}
	</div>
{/strip}