{strip}
	<h2 class="hiddenTitle" id="mobileNav">{translate text="Navigation"}</h2>
	<a href="/" id="homeLink" class="menu-icon menu-bar-option menu-left" title="{translate text=$homeLinkText}" aria-label="{translate text="Return to $homeLinkText"}">
		<i class="fas fa-home fa-lg"></i><span class="menu-bar-label hidden-inline-block-xs"></span>
	</a>
	<a onclick="$('#horizontal-search-box').slideToggle('slow');return false;" class="menu-icon menu-bar-option menu-left" title="{translate text="Search"}" aria-label="{translate text="Search"}">
		<i class="fas fa-search fa-lg"></i><span class="menu-bar-label hidden-inline-block-xs">{translate text="Search"}</span>
	</a>
	{if !empty($sidebar)}
		<a onclick="$('#side-bar').slideToggle('slow');return false;" id="homeLink" class="menu-icon menu-bar-option visible-tn visible-xs hidden-sm hidden-md hidden-lg" title="{translate text="Show Menu"}">
			<i class="fas fa-bars fa-lg"></i><span class="menu-bar-label hidden-inline-block-xs"></span>
		</a>
	{/if}
	{if $loggedIn}{* Logged In *}
		<a href="/MyAccount/Logout" onclick="return confirm('{translate text="Are you sure you want to logout?"}')" id="logoutLink" class="menu-icon menu-bar-option visible-md visible-lg" title="{translate text="Log Out"}">
			<i class="fas fa-sign-out-alt fa-lg"></i><span class="menu-bar-label hidden-inline-block-xs"></span>
		</a>
		<a href="/MyAccount/Home" id="mobile-menu-account-icon" onclick="AspenDiscovery.Menu.Mobile.showAccount(this)" class="menu-icon menu-bar-option" title="Account">
			<i class="fas fa-user fa-lg"></i><span class="menu-bar-label">
				{if $masqueradeMode}
					{translate text="Masquerading As %1%" 1=$userDisplayName}
				{else}
					{$userDisplayName}
				{/if}
			</span>
		</a>
		{if !empty($userRoles)}
			<a href="/Admin/Home" class="menu-icon menu-bar-option" title="Aspen Administration">
				<i class="fas fa-cogs fa-lg"></i><span class="menu-bar-label hidden-inline-block-xs">{translate text="Admin"}</span>
			</a>
		{/if}
	{else} {* Not Logged In *}
		<a href="/MyAccount/Home" id="loginLink" onclick="{if $isLoginPage}$('#username').focus();return false;{else}return AspenDiscovery.Account.followLinkIfLoggedIn(this);{/if}" data-login="true" class="menu-icon menu-bar-option" title="{translate text='Login'}">
			<i class="fas fa-sign-in-alt fa-lg"></i><span class="menu-bar-label hidden-inline-block-xs">{translate text="Login"}</span>
		</a>
	{/if}
{*	<a href="#*}{*home-page-login*}{*" id="mobile-menu-menu-icon" onclick="AspenDiscovery.Menu.Mobile.showMenu(this)" class="menu-icon menu-bar-option" title="Menu">*}
{*		<i class="fas fa-info-circle fa-lg"></i><span class="menu-bar-label hidden-inline-block-xs">{translate text=$sidebarMenuButtonText}</span>*}
{*	</a>*}

{/strip}