{strip}
	<h2 class="hiddenTitle" id="mobileNav">{translate text="Mobile Navigation"}</h2>
	{if $loggedIn}{* Logged In *}
		<a href="/MyAccount/Logout" id="logoutLink" class="menu-icon" title="{translate text="Log Out"}">
			<i class="fas fa-sign-out-alt fa-2x"></i>
		</a>
		<a href="#{*home-page-login*}" id="mobile-menu-account-icon" onclick="AspenDiscovery.Menu.Mobile.showAccount(this)" class="menu-icon" title="Account">
			<i class="fas fa-user fa-2x"></i>
		</a>
	{else} {* Not Logged In *}
		<a href="/MyAccount/Home" id="loginLink" onclick="{if $isLoginPage}$('#username').focus();return false{else}return AspenDiscovery.Account.followLinkIfLoggedIn(this){/if}" data-login="true" class="menu-icon" title="{translate text='Login'}">
			<i class="fas fa-sign-in-alt fa-2x"></i>
		</a>
	{/if}
	<a href="#{*home-page-login*}" id="mobile-menu-menu-icon" onclick="AspenDiscovery.Menu.Mobile.showMenu(this)" class="menu-icon" title="Menu">
		<i class="fas fa-bars fa-2x"></i>
	</a>

	<a href="#{*horizontal-menu-bar-wrapper*}" id="mobile-menu-search-icon" onclick="AspenDiscovery.Menu.Mobile.showSearch(this)" class="menu-icon menu-left" title="Search">
		{* mobile-menu-search-icon id used by Refine Search button to set the menu to search (in case another menu option has been selected) *}
		<i class="fas fa-search fa-2x"></i>
	</a>

	{if !empty($showExploreMore)}
		{* TODO: set explore more anchor tag so exploremore is moved into view on mobile *}
		<a href="#" id="mobile-menu-explore-more-icon" onclick="AspenDiscovery.Menu.Mobile.showExploreMore(this)" class="menu-icon menu-left" title="{translate text='Explore More'}">
			<i class="fas fa-share-square fa-2x"></i>
		</a>
	{/if}
{/strip}