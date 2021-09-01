{strip}
	<h2 class="hiddenTitle" id="mobileNav">{translate text="Navigation" isPublicFacing=true}</h2>
	<div class="menu-section menu-section-left">
		{if $useHomeLink == '1' || $useHomeLink == '3'}
			<a href="{$homeLink}" id="homeLink" class="menu-icon menu-bar-option" title="{translate text="Return to $homeLinkText" inAttribute=true isPublicFacing=true}" aria-label="{translate text="Return to $homeLinkText" inAttribute=true isPublicFacing=true}" role="link">
				<i class="fas fa-home fa-lg"></i><span class="menu-bar-label hidden-inline-block-xs"></span>
			</a>
		{/if}
		<a href="{if $useHomeLink == '0' || $useHomeLink == '2'}/{else}/Search/Home{/if}" id="homeLink" class="menu-icon menu-bar-option" title="{translate text='Browse the Catalog' inAttribute=true isPublicFacing=true}" aria-label="{translate text='Browse the Catalog' inAttribute=true isPublicFacing=true}" role="link">
			<i class="fas {if ($useHomeLink == '1' || $useHomeLink == '3') || ($showBookIcon == '1' && ($useHomeLink == '0' || $useHomeLink == '2'))}fa-book-open{else}fa-home{/if} fa-lg"></i>{if $useHomeLink == '1' || $useHomeLink == '3'}<span class="menu-bar-label visible-inline-block-lg">{translate text=$browseLinkText isPublicFacing=true}</span>{else}{/if}
		</a>
		{foreach from=$libraryLinks key=categoryName item=menuCategory}
			{assign var=topCategory value=$menuCategory|@reset}
			{if $topCategory->showInTopMenu || $topCategory->alwaysShowIconInTopMenu}
				{if count($menuCategory) > 1}
					<div class="dropdown menuToggleButton {$topCategory->getEscapedCategory()}Menu" style="display:inline-block;">
						<a id="{$topCategory->getEscapedCategory()}-menu-trigger" tabindex="0" class="dropdown-toggle menu-icon menu-bar-option {if !$topCategory->alwaysShowIconInTopMenu}visible-inline-block-lg{/if}" aria-label="{translate text=$categoryName inAttribute=true isPublicFacing=true}"  aria-haspopup="true" aria-expanded="false" role="link">
							{if !empty($topCategory->iconName)}
								<i class="fas fa-{$topCategory->iconName} fa-lg"></i>
							{/if}
							<span class="menu-bar-label visible-inline-block-lg">
								{if $topCategory->published == 0}<em>{/if}
								{translate text=$topCategory->category isPublicFacing=true}
								{if $topCategory->published == 0}</em>{/if}
							</span>
						</a>
						<div id="{$topCategory->getEscapedCategory()}-menu" class="dropdown-menu dropdownMenu" aria-labelledby="{$topCategory->getEscapedCategory()}-menu-trigger">
							{foreach from=$menuCategory item=link key=linkName}
								{* Only render HTML contents in the header menu *}
								{if empty($link->htmlContents)}
									<div class="header-menu-option childMenuItem">
										<a href="{$link->url}" {if $link->openInNewTab}target="_blank"{/if} aria-label="{translate text=$linkName isPublicFacing=true inAttribute=true}">
											{if $link->published == 0}<em>{/if}
											{translate text=$linkName isPublicFacing=true}
											{if $link->published == 0}</em>{/if}
										</a>
									</div>
								{/if}
							{/foreach}
						</div>
					</div>
				{literal}
					<script type="application/javascript">
						// fixed bootstrap custom menu toggles
						$('div.dropdown.menuToggleButton.{/literal}{$topCategory->getEscapedCategory()}{literal}Menu a').on('click', function (event) {
							$(this).parent().toggleClass('open');
						});
						$(document).on('click', function (e) {
							var trigger = $('div.dropdown.menuToggleButton.{/literal}{$topCategory->getEscapedCategory()}{literal}Menu');
							if (trigger !== event.target && !trigger.has(event.target).length) {
								$('div.dropdown.menuToggleButton.{/literal}{$topCategory->getEscapedCategory()}{literal}Menu').removeClass('open');
							}
						});
					</script>
				{/literal}
				{else}
					<a href="{$topCategory->url}" role="link" class="menu-icon menu-bar-option {if !$topCategory->alwaysShowIconInTopMenu}visible-inline-block-lg{/if}" aria-label="{translate text=$categoryName inAttribute=true isPublicFacing=true}" {if $topCategory->openInNewTab}target="_blank"{/if} tabindex="0">
						{if !empty($topCategory->iconName)}
							<i class="fas fa-{$topCategory->iconName} fa-lg"></i>
						{/if}
						<span class="menu-bar-label visible-inline-block-lg">
							{if $topCategory->published == 0}<em>{/if}
							{translate text=$topCategory->category isPublicFacing=true}
							{if $topCategory->published == 0}</em>{/if}
						</span>
					</a>
				{/if}
			{/if}
		{/foreach}
	</div>
	<div class="menu-section menu-section-right">
		{if $loggedIn}{* Logged In *}
			<div class="dropdown menuToggleButton accountMenu" style="display:inline-block;">
			<a tabindex="0" class="dropdown-toggle menu-icon menu-bar-option" role="button" title="{translate text="Account" inAttribute=true isPublicFacing=true}" aria-haspopup="true" aria-expanded="false" id="account-menu-dropdown">
				{if $masqueradeMode}
					<i class="fas fa-theater-masks fa-lg"></i>
				{else}
					<i class="fas fa-user fa-lg"></i>
				{/if}
				<span class="menu-bar-label hidden-inline-block-xs">
					{if $masqueradeMode}
						{translate text="Acting As %1%" 1=$userDisplayName isPublicFacing=true}
					{else}
						{$userDisplayName}
					{/if}
				</span>
			</a>
			{include file="account-menu.tpl"}
			</div>
		{else} {* Not Logged In *}
			{if $showLoginButton}
			<a href="/MyAccount/Home" id="loginLink" onclick="{if $isLoginPage}$('#username').focus();return false;{else}return AspenDiscovery.Account.followLinkIfLoggedIn(this);{/if}" onkeypress="{if $isLoginPage}$('#username').focus();return false;{else}return AspenDiscovery.Account.followLinkIfLoggedIn(this);{/if}" data-login="true" class="menu-icon menu-bar-option" title="{translate text='Login' inAttribute=true isPublicFacing=true}">
				<i id="loginLinkIcon" class="fas fa-sign-in-alt fa-lg"></i><span class="menu-bar-label hidden-inline-block-xs" id="login-button-label">{translate text="Sign in" isPublicFacing=true}</span>
			</a>
			{/if}
		{/if}

		<div class="dropdown menuToggleButton headerMenu" style="display:inline-block;"><a class="dropdown-toggle menu-icon menu-bar-option" tabindex="0" role="button" title="{translate text="Show Menu" inAttribute=true isPublicFacing=true}"  aria-haspopup="true" aria-expanded="false" id="header-menu-dropdown">
			<i class="fas fa-bars fa-lg"></i>
		</a>
		{include file="header-menu.tpl"}
		</div>
	</div>
{/strip}