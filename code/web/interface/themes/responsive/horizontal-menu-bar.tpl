{strip}
	<h2 class="hiddenTitle" id="mobileNav">{translate text="Navigation"}</h2>
	{if !empty($homeLink)}
		<a href="{$homeLink}" id="homeLink" class="menu-icon menu-bar-option menu-left" title="{translate text='Library Home Page'}" aria-label="{translate text="Return to $homeLinkText"}">
			<i class="fas fa-home fa-lg"></i><span class="menu-bar-label hidden-inline-block-xs"></span>
		</a>
	{/if}
	<a href="/" id="homeLink" class="menu-icon menu-bar-option menu-left" title="{translate text='Browse the catalog'}" aria-label="{translate text='Browse the catalog'}">
		<i class="fas {if empty($homeLink)}fa-home{else}fa-th{/if} fa-lg"></i><span class="menu-bar-label hidden-inline-block-xs"></span>
	</a>
	<a onclick="$('#horizontal-search-box').slideToggle('slow');return false;" class="menu-icon menu-bar-option menu-left hidden-inline-md hidden-inline-lg" title="{translate text="Search"}" aria-label="{translate text="Search"}">
		<i class="fas fa-search fa-lg"></i><span class="menu-bar-label hidden-inline-block-xs">{translate text="Search"}</span>
	</a>
	<span id="menuToggleButton">
		<a onclick="return AspenDiscovery.toggleMenu();" class="menu-icon menu-bar-option" title="{translate text="Show Menu"}">
			<i class="fas fa-bars fa-lg"></i>
		</a>
		{include file="header-menu.tpl"}
	</span>
	{if $loggedIn}{* Logged In *}
		<a href="/MyAccount/Home" id="mobile-menu-account-icon" class="menu-icon menu-bar-option" title="Account">
			{if $masqueradeMode}
				<i class="fas fa-user fa-theater-masks"></i>
			{else}
				<i class="fas fa-user fa-lg"></i>
			{/if}
			<span class="menu-bar-label hidden-inline-block-xs">
				{if $masqueradeMode}
					{translate text="Masquerading As %1%" 1=$userDisplayName}
				{else}
					{$userDisplayName}
				{/if}
			</span>
		</a>
	{else} {* Not Logged In *}
		<a href="/MyAccount/Home" id="loginLink" onclick="{if $isLoginPage}$('#username').focus();return false;{else}return AspenDiscovery.Account.followLinkIfLoggedIn(this);{/if}" data-login="true" class="menu-icon menu-bar-option" title="{translate text='Login'}">
			<i class="fas fa-sign-in-alt fa-lg"></i><span class="menu-bar-label hidden-inline-block-xs">{translate text="Login"}</span>
		</a>
	{/if}
{/strip}