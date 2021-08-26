{strip}
<div id="header-menu" class="dropdown-menu dropdownMenu" aria-labelledby="header-menu-dropdown">
	{if $showLoginButton}
		<div id="hamburger-menu-my-account" class="header-menu-option">
			<a href="/MyAccount/Home"><i class="fas fa-user fa-fw"></i><span>{translate text='My Account'}</span></a>
		</div>
	{/if}

	{if !empty($userPermissions)}
		<div id="home-page-home-button" class="header-menu-option">
			<a href="/Admin/Home"><i class="fas fa-tools fa-fw"></i><span>{translate text='Aspen Administration'}</span></a>
		</div>
	{/if}

	{if !empty($homeLink)}
		<div id="home-page-home-button" class="header-menu-option">
			<a href="{$homeLink}"><i class="fas fa-landmark fa-fw"></i><span>{translate text='Library Home Page'}</span></a>
		</div>
	{/if}

	{if $showLibraryHoursAndLocationsLink}
		<a href="/AJAX/JSON?method=getHoursAndLocations" data-title="{translate text="Library Hours and Locations" inAttribute=true}" class="modalDialogTrigger">
			<div id="home-page-hours-locations" class="header-menu-option">
				<i class="fas fa-map-marker-alt fa-fw"></i>
				<span>
				{if $numLocations == 1}
					{if !isset($hasValidHours) || $hasValidHours}
						{translate text="Library Hours & Location"}
					{else}
						{translate text="Location"}
					{/if}
				{else}
					{if !isset($hasValidHours) || $hasValidHours}
						{translate text="Library Hours & Location"}
					{else}
						{translate text="Locations"}
					{/if}
				{/if}
				</span>
			</div>
		</a>
	{/if}

	{if $libraryLinks}
		{foreach from=$libraryLinks item=linkCategory key=categoryName name=linkLoop}
			{assign var=firstCategory value=$linkCategory|@reset}
			{if !$firstCategory->alwaysShowIconInTopMenu}
				{if $categoryName && !preg_match('/none-\\d+/', $categoryName) && count($linkCategory) > 1}
					{* Put the links within a collapsible section *}
					<a onclick="return AspenDiscovery.toggleMenuSection('{$categoryName|escapeCSS}');" {if $firstCategory->showInTopMenu == 1 || $firstCategory->alwaysShowIconInTopMenu == 1}class="hidden-lg"{/if}>
						<div class="header-menu-section" id="{$categoryName|escapeCSS}MenuSection">
							<i class="fas {if !array_key_exists($categoryName, $expandedLinkCategories)}fa-caret-right{else}fa-caret-down{/if}"></i>
							{if $linkCategory->published == 0}<em>{/if}
							{$categoryName|translate}
							{if $linkCategory->published == 0}</em>{/if}
						</div>
					</a>
					<div id="{$categoryName|escapeCSS}MenuSectionBody" class="menuSectionBody {if $firstCategory->showInTopMenu == 1 || $firstCategory->alwaysShowIconInTopMenu == 1}hidden-lg{/if}" {if !array_key_exists($categoryName, $expandedLinkCategories)}style="display: none" {/if}>
						{foreach from=$linkCategory item=link key=linkName}
							{if !empty($link->htmlContents)}
								{$link->htmlContents}
							{else}
								<div class="header-menu-option {if $categoryName && !preg_match('/none-\\d+/', $categoryName)}childMenuItem{/if}">
									<a href="{$link->url}" {if $link->openInNewTab}target="_blank"{/if}>
										{if !empty($link->iconName)}
											<i class="fas fa-{$link->iconName} fa-fw"></i>
										{/if}
										{if $link->published == 0}<em>{/if}
											<span>{$linkName|translate}</span>
										{if $link->published == 0}</em>{/if}
									</a>
								</div>
							{/if}
						{/foreach}
					</div>
				{else}
					{* No category name, display these links as buttons *}
					{foreach from=$linkCategory item=link key=linkName}
						{if $link->htmlContents}
							{$link->htmlContents}
						{else}
							<a href="{$link->url}" {if $link->openInNewTab}target="_blank"{/if}>
								<div class="header-menu-option {if $link->showInTopMenu || $link->alwaysShowIconInTopMenu}hidden-lg{/if}">
									{if !empty($link->iconName)}
										<i class="fas fa-{$link->iconName} fa-fw"></i>
									{/if}
									{if $link->published == 0}<em>{/if}
										<span>{$linkName|translate}</span>
									{if $link->published == 0}</em>{/if}
								</div>
							</a>
						{/if}
					{/foreach}
				{/if}
			{/if}
		{/foreach}
	{/if}

	{if count($validLanguages) > 1}
		<div class="header-menu-section" id="aspenLanguagesMenuSection">
			<i class="fas fa-globe fa-fw"></i>{translate text="Language"}
		</div>

		{foreach from=$validLanguages key=languageCode item=language}
			{if $userLang->code!=$languageCode}
			<a onclick="return AspenDiscovery.setLanguage('{$languageCode}')">
			{/if}
				<div class="header-menu-option languageSelect{if $userLang->code==$languageCode}ed{/if}">
					{if $userLang->code==$languageCode}
						<i class="fas fa-check fa-fw"></i>&nbsp;
					{/if}
					{$language->displayName}
				</div>
			{if $userLang->code!=$languageCode}
			</a>
			{/if}
		{/foreach}
	{/if}

	{if $masqueradeMode}
		<a class="btn btn-default btn-sm btn-block" onclick="AspenDiscovery.Account.endMasquerade()">{translate text="End Masquerade"}</a>
	{/if}

	<a href="/MyAccount/Logout" id="logoutLink" title="{translate text="Sign Out" inAttribute=true}" class="btn btn-default btn-sm btn-block" {if !$loggedIn}style="display:none"{/if}>
		{translate text="Sign Out"}
	</a>
</div>
{/strip}