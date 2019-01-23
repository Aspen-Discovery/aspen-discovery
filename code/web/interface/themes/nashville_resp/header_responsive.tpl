{strip}

	{*<div class="col-xs-12 col-sm-6 col-md-5 col-lg-4">*}
	<div class="col-tn-12 col-xs-12 col-sm-3 col-md-3 col-lg-3">
		<div id="header-logo"> {* Image also has this id *}
			<a href="{$logoLink}/">

				<img src="{if $responsiveLogo}{$responsiveLogo}{else}{img filename="logo_responsive.png"}{/if}" alt="{$librarySystemName}" title="Return to Catalog Home" id="header-logo" {if $showDisplayNameInHeader && $librarySystemName}class="pull-left"{/if}>

			</a>
		</div>
	</div>

	{* Heading Info Div *}
	<div id="headingInfo" class="hidden-xs hidden-sm col-md-5 col-lg-5">
		{if $showDisplayNameInHeader && $librarySystemName}
			<span id="library-name-header" class="hidden-xs visible-sm">{$librarySystemName}</span>
		{/if}

		{if !empty($headerText)}
			<div id="headerTextDiv">{*An id of headerText would clash with the input textarea on the Admin Page*}
				{$headerText}
			</div>
		{/if}
	</div>

	<div class="logoutOptions"{if !$loggedIn} style="display: none;"{/if}>
		{*<div class="hidden-xs col-sm-2 col-sm-offset-2 col-md-2 col-md-offset-3 col-lg-2 col-lg-offset-4">*}
		<div class="hidden-xs col-sm-2 col-sm-offset-5 col-md-2 col-md-offset-0 col-lg-2 col-lg-offset-0">
			<a id="myAccountNameLink" href="{$path}/MyAccount/Home">
				<div class="header-button header-primary">
					{if $userDisplayName}{$userDisplayName|capitalize}'s Account{else}My Account{/if}
				</div>
			</a>
		</div>

		{*<div class="hidden-xs col-xs-3 col-sm-2 col-md-2 col-lg-2">*}
		<div class="hidden-xs col-sm-2 col-md-2 col-lg-2">
			<a href="{$path}/MyAccount/Logout"{if $masqueradeMode} onclick="return confirm('This will end both Masquerade Mode and your session as well. Continue to log out?')"{/if}  id="logoutLink">
				<div class="header-button header-logout">
					{translate text="Log Out"}
				</div>
			</a>
		</div>
	</div>

	<div class="loginOptions col-sm-2 col-sm-offset-7 col-md-2 col-md-offset-2 col-lg-offset-2 col-lg-2"{if $loggedIn} style="display: none;"{/if}>
		{if $showLoginButton == 1}
			<a id="headerLoginLink" href="{$path}/MyAccount/Home" class="loginLink" data-login="true" title="Login">
				<div class="hidden-xs header-button header-primary">
					{translate text="LOGIN"}
				</div>
			</a>
		{/if}
	</div>

	{if $topLinks}
		<div class="col-xs-12" id="header-links">
			{foreach from=$topLinks item=link}
				<div class="header-link-wrapper">
					<a href="{$link->url}" class="library-header-link">{$link->linkText}</a>
				</div>
			{/foreach}
		</div>
	{/if}
{/strip}
