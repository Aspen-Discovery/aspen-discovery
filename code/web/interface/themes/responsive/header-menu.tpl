{strip}
<div id="header-menu" class="dropdown-menu dropdownMenu" aria-labelledby="header-menu-dropdown">
	{if !empty($showLoginButton)}
		<div id="hamburger-menu-my-account" class="header-menu-option">
			<a href="/MyAccount/Home"><i class="fas fa-user fa-fw"></i><span>{translate text='Your Account' isPublicFacing=true}</span></a>
		</div>
	{/if}

	{if !empty($userPermissions)}
		<div id="admin-home-button" class="header-menu-option">
			<a href="/Admin/Home"><i class="fas fa-tools fa-fw"></i><span>{translate text='Aspen Administration' isAdminFacing=true}</span></a>
		</div>
	{/if}

	{if !empty($homeLink)}
		<div id="home-page-home-button" class="header-menu-option">
			<a href="{$homeLink}"><i class="fas fa-landmark fa-fw"></i><span>{translate text='Library Home Page' isAdminFacing=true}</span></a>
		</div>
	{/if}

	{if !empty($showLibraryHoursAndLocationsLink)}
		<a href="/AJAX/JSON?method=getHoursAndLocations" data-title="{translate text="Library Hours and Locations" inAttribute=true isAdminFacing=true}" class="modalDialogTrigger">
			<div id="home-page-hours-locations" class="header-menu-option">
				<i class="fas fa-map-marker-alt fa-fw"></i>
				<span>
				{if $numLocations == 1}
					{if !isset($hasValidHours) || $hasValidHours}
						{translate text="Library Hours & Location" isAdminFacing=true}
					{else}
						{translate text="Location" isAdminFacing=true}
					{/if}
				{else}
					{if !isset($hasValidHours) || $hasValidHours}
						{translate text="Library Hours & Location" isAdminFacing=true}
					{else}
						{translate text="Locations" isAdminFacing=true}
					{/if}
				{/if}
				</span>
			</div>
		</a>
	{/if}

	{if !empty($libraryLinks)}
		{foreach from=$libraryLinks item=linkCategory key=categoryName name=linkLoop}
			{assign var=firstCategory value=$linkCategory|@reset}
			{if !$firstCategory->alwaysShowIconInTopMenu}
				{if !empty($categoryName) && !preg_match('/none-\\d+/', $categoryName) && count($linkCategory) > 1}
					{* Put the links within a collapsible section *}
					<a onclick="return AspenDiscovery.toggleMenuSection('{$categoryName|escapeCSS}');" {if $firstCategory->showInTopMenu == 1 || $firstCategory->alwaysShowIconInTopMenu == 1}class="hidden-lg"{/if}>
						<div class="header-menu-section" id="{$categoryName|escapeCSS}MenuSection">
							<i class="fas {if !array_key_exists($categoryName, $expandedLinkCategories)}fa-caret-right{else}fa-caret-down{/if}"></i>
							{if $firstCategory->published == 0}<em>{/if}
							{translate text=$categoryName isPublicFacing=true}
							{if $firstCategory->published == 0}</em>{/if}
						</div>
					</a>
					<div id="{$categoryName|escapeCSS}MenuSectionBody" class="menuSectionBody {if $firstCategory->showInTopMenu == 1 || $firstCategory->alwaysShowIconInTopMenu == 1}hidden-lg{/if}" {if !array_key_exists($categoryName, $expandedLinkCategories)}style="display: none" {/if}>
						{foreach from=$linkCategory item=link key=linkName}
							{if !empty($link->htmlContents)}
								{$link->htmlContents}
							{else}
								<div class="header-menu-option {if !empty($categoryName) && !preg_match('/none-\\d+/', $categoryName)}childMenuItem{/if}">
									<a href="{$link->url}" {if $link->openInNewTab}target="_blank"{/if}>
										{if !empty($link->iconName)}
											<i class="fas fa-{$link->iconName} fa-fw"></i>
										{/if}
										{if $link->published == 0}<em>{/if}
											<span>{translate text=$linkName isPublicFacing=true}</span>
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
										<span>{translate text=$linkName isPublicFacing=true}</span>
									{if $link->published == 0}</em>{/if}
								</div>
							</a>
						{/if}
					{/foreach}
				{/if}
			{/if}
		{/foreach}
	{/if}

	{if count($validLanguages) >= 0}
		<div class="header-menu-section" id="aspenLanguagesMenu" style="color:#3174AF; cursor:auto; font-weight:normal">
			<i class="fas fa-globe fa-fw" style="color:#3174AF; cursor:auto"></i>&nbsp;&nbsp;{translate text="Language" isPublicFacing=true}
		</div>

		{foreach from=$validLanguages key=languageCode item=language}
			{if $userLang->code!=$languageCode}
			<a onclick="return AspenDiscovery.setLanguage('{$languageCode}')">
			{/if}
				<div class="header-menu-option languageSelect{if $userLang->code==$languageCode}ed{/if}" style="color:#3174AF">
					{if $userLang->code==$languageCode}
						&nbsp;&nbsp;<i class="fas fa-check fa-fw" style="color:#3174AF; cursor:auto;"></i>&nbsp;
					{/if}
					{$language->displayName}
				</div>
			{if $userLang->code!=$languageCode}
			</a>
			{/if}
		{/foreach}
	{/if}

	{if count($allActiveThemes) >= 0}
		<div class="header-menu-section" id="aspenThemesMenuSection" style="color:#3174AF; cursor:auto;">
			<i class="fas fa-cog" style="color:#3174AF; cursor:auto"></i>&nbsp;&nbsp;{translate text="Display" isPublicFacing=true}
		</div>

		{foreach from=$allActiveThemes key=themeId item=themeName}
			{if $themeId === $activeTheme}
				<i class="fas fa-check fa-fw" style="color:#3174AF; cursor:auto"></i>&nbsp;
			{/if}
			<li><a onclick="return AspenDiscovery.setTheme('{$themeId}')">
				{$themeName}
			</a></li>
		{/foreach}
	{/if}

	{if !empty($masqueradeMode)}
		<a class="btn btn-default btn-sm btn-block" onclick="AspenDiscovery.Account.endMasquerade()">{translate text="End Masquerade" isAdminFacing=true}</a>
	{/if}

	<a href="/MyAccount/Logout" id="logoutLink" title="{translate text="Sign Out" inAttribute=true isPublicFacing=true}" class="btn btn-default btn-sm btn-block" {if empty($loggedIn)}style="display:none"{/if}>
		{translate text="Sign Out" isPublicFacing=true}
	</a>
</div>
{/strip}