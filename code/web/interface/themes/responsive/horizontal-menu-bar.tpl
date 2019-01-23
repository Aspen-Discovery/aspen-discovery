{strip}
	{if $loggedIn}{* Logged In *}
		<a href="{$path}/MyAccount/Logout" id="logoutLink" class="menu-icon" title="{translate text="Log Out"}">
			<img src="{img filename='/interface/themes/responsive/images/Logout.png'}" alt="{translate text="Log Out"}">
		</a>
		<a href="#{*home-page-login*}" id="mobile-menu-account-icon" onclick="VuFind.Menu.Mobile.showAccount(this)" class="menu-icon" title="Account">
			<img src="{img filename='/interface/themes/responsive/images/Account.png'}" alt="Account">
		</a>
	{else} {* Not Logged In *}
		<a href="{$path}/MyAccount/Home" id="loginLink" onclick="{if $isLoginPage}$('#username').focus();return false{else}return VuFind.Account.followLinkIfLoggedIn(this){/if}" data-login="true" class="menu-icon" title="{translate text='Login'}">
			{*<img src="{img filename='/interface/themes/responsive/images/Account.png'}" alt="{translate text='Login'}">*}
			<img src="{img filename='/interface/themes/responsive/images/Login.png'}" alt="{translate text='Login'}">
		</a>
	{/if}
	<a href="#{*home-page-login*}" id="mobile-menu-menu-icon" onclick="VuFind.Menu.Mobile.showMenu(this)" class="menu-icon" title="Menu">
		<img src="{img filename='/interface/themes/responsive/images/Menu.png'}" alt="Menu">
	</a>

	<a href="#{*horizontal-menu-bar-wrapper*}" id="mobile-menu-search-icon" onclick="VuFind.Menu.Mobile.showSearch(this)" class="menu-icon menu-left" title="Search">
		{* mobile-menu-search-icon id used by Refine Search button to set the menu to search (in case another menu option has been selected) *}
		<img src="{img filename='/interface/themes/responsive/images/Search.png'}" alt="Search">
	</a>

	{if $showExploreMore}
		{* TODO: set explore more anchor tag so exploremore is moved into view on mobile *}
		<a href="#" id="mobile-menu-explore-more-icon" onclick="VuFind.Menu.Mobile.showExploreMore(this)" class="menu-icon menu-left" title="{translate text='Explore More'}">
			<img src="{img filename='/interface/themes/responsive/images/ExploreMore.png'}" alt="{translate text='Explore More'}">
		</a>
	{/if}
{/strip}