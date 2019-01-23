<div id="menu-header">
	<div id="menu-header-links">
		<div id="menu-account-links">
		<span id="myAccountNameLink" class="menu-account-link logoutOptions top-menu-item"{if !$loggedIn} style="display: none;"{/if}><a href="{$path}/MyResearch/Home">{$userDisplayName}</a></span>
		<span class="menu-account-link logoutOptions top-menu-item"{if !$loggedIn} style="display: none;"{/if}><a href="{$path}/MyAccount/Home">{translate text="My Account"}</a></span>
		<span class="menu-account-link logoutOptions top-menu-item"{if !$loggedIn} style="display: none;"{/if}><a href="{$path}/MyAccount/Logout">{translate text="Log Out"}</a></span>
		{if $showLoginButton == 1}
		  <span class="menu-account-link loginOptions top-menu-item" {if $loggedIn} style="display: none;"{/if}><a href="{$path}/MyAccount/Home" class='loginLink'>{translate text="My Account"}</a></span>
		{/if}
		</div>
	</div>
</div>