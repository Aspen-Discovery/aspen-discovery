{strip}

	{* In mobile view this is the top div and spans across the screen *}
	{* Logo Div *}
	<div class="col-xs-12 col-sm-3 col-md-3 col-lg-3">
		<a href="{$logoLink}/">
			<img src="{if $responsiveLogo}{$responsiveLogo}{else}{img filename="logo_responsive.png"}{/if}" alt="{$librarySystemName}" title="{$logoAlt}" id="header-logo" {if $showDisplayNameInHeader && $librarySystemName}class="pull-left"{/if}>
{*			{if $showDisplayNameInHeader && $librarySystemName}
				<span id="library-name-header" class="hidden-xs visible-sm">{$librarySystemName}</span>
			{/if}*}
		</a>
	</div>

	{* Heading Info Div *}
	<div id="headingInfo" class="hidden-xs hidden-sm col-md-5 col-lg-5">
		{if $showDisplayNameInHeader && $librarySystemName}
			<p id="library-name-header">{$librarySystemName}</p>
		{/if}

		{if !empty($headerText)}
		<div id="headerTextDiv">{*An id of headerText would clash with the input textarea on the Admin Page*}
			{$headerText}
		</div>
		{/if}

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