{strip}
	{if $displaySidebarMenu}
		<div class="hidden-xs col-sm-1 col-md-1 col-lg-1" id="vertical-menu-bar-wrapper">
			<div id="vertical-menu-bar">
				<div class="menu-bar-option">
					<a href="#" onclick="VuFind.Menu.SideBar.showSearch(this)" class="menu-icon" title="Search" id="vertical-menu-search-button">
						<img src="{img filename='/interface/themes/responsive/images/Search.png'}" alt="Search">
						<div class="menu-bar-label rotated-text"><span class="rotated-text-inner">Search</span></div>
					</a>
				</div>
				{if $loggedIn}{* Logged In *}
					<div class="menu-bar-option">
						<a href="#" onclick="VuFind.Menu.SideBar.showAccount(this)" class="menu-icon" title="Account">
							<img src="{img filename='/interface/themes/responsive/images/Account.png'}" alt="Account">
							<div class="menu-bar-label rotated-text"><span class="rotated-text-inner">Account</span></div>
						</a>
					</div>
				{else} {* Not Logged In *}
					<div class="menu-bar-option">
						<a href="{$path}/MyAccount/Home" id="loginLink" onclick="{if $isLoginPage}$('#username').focus();return false{else}return VuFind.Account.followLinkIfLoggedIn(this){/if}" data-login="true" class="menu-icon" title="{translate text='Login'}">
							{*<a href="{$path}{$fullPath}" id="loginLink" onclick="{if $isLoginPage}$('#username').focus();return false{else}return VuFind.Account.followLinkIfLoggedIn(this){/if}" data-login="true" class="menu-icon" title="{translate text='Login'}">*}
							{*<img src="{img filename='/interface/themes/responsive/images/Account.png'}" alt="{translate text='Login'}">*}
							<img src="{img filename='/interface/themes/responsive/images/Login.png'}" alt="{translate text='Login'}">
							{*<div class="menu-bar-label rotated-text"><span class="rotated-text-inner">Account</span></div>*}
							<div class="menu-bar-label rotated-text"><span class="rotated-text-inner">Login</span></div>
						</a>
					</div>
				{/if}
				<div class="menu-bar-option">
					<a href="#" onclick="VuFind.Menu.SideBar.showMenu(this)" class="menu-icon" title="Additional menu options including links to information about the library and other library resources">
						<img src="{img filename='/interface/themes/responsive/images/Menu.png'}" alt="Additional menu options including links to information about the library and other library resources">
						<div class="menu-bar-label rotated-text"><span class="rotated-text-inner">{$sidebarMenuButtonText}</span></div>
					</a>
				</div>
				{if $showExploreMore}
					<div id="sidebar-menu-option-explore-more" class="menu-bar-option">
						<a href="#" onclick="VuFind.Menu.SideBar.showExploreMore(this)" class="menu-icon" title="{translate text='Explore More'}">
							<img src="{img filename='/interface/themes/responsive/images/ExploreMore.png'}" alt="{translate text='Explore More'}">
							<div class="menu-bar-label rotated-text">
									<span class="rotated-text-inner">
										{translate text='Explore More'}
									</span>
							</div>
						</a>
					</div>
				{/if}

				{* Open Appropriate Section on Initial Page Load *}
				<script type="text/javascript">
					$(function(){ldelim}
						{* .filter(':visible') clauses below ensures that a menu option is triggered if the side bar option is visible is visible :  *}

						{if ($module == "Search" && $action != 'History') || $module == "Series" || $module == "Author" || $module == "Genealogy" || $module == "Library"
							|| ($module == 'MyAccount' && $action == 'MyList' && !$listEditAllowed)
							|| ($module == 'EBSCO' && $action == 'Results')
							|| ($module == 'Union' && $action == 'CombinedResults')
							|| ($module == 'Archive' && ($action == 'Results' || $action == 'RelatedEntities'))
					    }
								{* Treat Public Lists not owned by user as a Search Page rather than an MyAccount Page *}
								{* Click Search Menu Bar Button *}
							$('.menu-bar-option:nth-child(1)>a', '#vertical-menu-bar').filter(':visible').click();
						{elseif (!$isLoginPage && !in_array($action, array('EmailResetPin', 'ResetPin', 'RequestPinReset', 'EmailPin', 'SelfReg'))) && ($module == "MyAccount" || $module == "Admin" || $module == "Circa" || $module == "EditorialReview" || $module == "Report" || ($module == 'Search' && $action == 'History'))}
							{* Prevent this action on the Pin Reset Page && Login Page && Offline Circulation Page*}
							{* Click Account Menu Bar Button *}
							$('.menu-bar-option:nth-child(2)>a', '#vertical-menu-bar').filter(':visible').click();
						{elseif $showExploreMore}
							{* Click Explore More Menu Bar Button *}
							$('.menu-bar-option:nth-child(4)>a', '#vertical-menu-bar').filter(':visible').click();
						{else}
							{* Click Menu - Sidebar Menu Bar Button *}
							$('.menu-bar-option:nth-child(3)>a', '#vertical-menu-bar').filter(':visible').click();
						{/if}
						{rdelim})
				</script>
			</div>
		</div>
	{/if}
{/strip}