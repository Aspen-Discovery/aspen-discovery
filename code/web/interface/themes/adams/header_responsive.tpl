<div class="col-tn-12 col-xs-12">
	<div class="row">
		<div id="header_black" class="col-tn-12 col-xs-12">
			<a href="http://www.adams.edu/">
				<img src="https://libapps.s3.amazonaws.com/accounts/14067/images/Adams_State_University_Logo.png" alt="Adams State University">
			</a>
		</div>
	</div>
	<div class="row">
		<div id="header_library">
			<div class="col-tn-4 col-xs-4 col-sm-3 col-md-8 col-lg-8">
				<a class="nielsenlibrarytxt" href="https://adams.edu/library/">Nielsen Library</a>
			</div>

			<div class="logoutOptions"{if !$loggedIn} style="display: none;"{/if}>
				<div class="hidden-xs col-sm-2 col-sm-offset-5 col-md-2 col-md-offset-0 col-lg-2 col-lg-offset-0">
					<a id="myAccountNameLink" href="{$path}/MyAccount/Home">
						<div class="header-button header-primary">
							{translate text="Your Account"}
						</div>
					</a>
				</div>

				<div class="hidden-xs col-sm-2 col-md-2 col-lg-2">
					<a href="{$path}/MyAccount/Logout"{if $masqueradeMode} onclick="return confirm('This will end both Masquerade Mode and your session as well. Continue to log out?')"{/if} id="logoutLink">
						<div class="header-button header-primary">
							{translate text="Log Out"}
						</div>
					</a>
				</div>
			</div>

			<div class="loginOptions col-sm-2 col-sm-offset-7 col-md-2 col-md-offset-2 col-lg-offset-2 col-lg-2"{if $loggedIn} style="display: none;"{/if}>
				{if $showLoginButton == 1}
					<a id="headerLoginLink" href="{$path}/MyAccount/Home" class="loginLink" data-login="true" title="Login" onclick="{if $isLoginPage}$('#username').focus();return false{else}return VuFind.Account.followLinkIfLoggedIn(this);{/if}">
						<div class="hidden-xs header-button header-primary">
							{translate text="LOGIN"}
						</div>
					</a>
				{/if}
			</div>

		</div>
	</div>
</div>
{strip}
	{if $topLinks}
		<div class="col-tn-12" id="header-links">
			{foreach from=$topLinks item=link}
				<div class="header-link-wrapper">
					<a href="{$link->url}" class="library-header-link">{$link->linkText}</a>
				</div>
			{/foreach}
		</div>
	{/if}
{/strip}