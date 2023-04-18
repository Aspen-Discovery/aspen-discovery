{strip}

	{* In mobile view this is the top div and spans across the screen *}
	{* Logo Div *}
    {if ($showDisplayNameInHeader && !empty($librarySystemName)) || !empty($headerText)}
		<div class="col-tn-12 col-xs-8 col-sm-8 col-md-3 col-lg-3" id="header-logo-container">
			<a href="{$logoLink}/">
				<img src="{if !empty($responsiveLogo)}{$responsiveLogo}{else}{img filename="logo_responsive.png"}{/if}" alt="{$librarySystemName}" title="{translate text=$logoAlt inAttribute=true isPublicFacing=true}" id="header-logo" {if !empty($showDisplayNameInHeader) && $librarySystemName}class="pull-left"{/if}>
			</a>
		</div>
		{* Heading Info Div *}
		<div id="headingInfo" class="hidden-xs hidden-sm col-md-5 col-lg-5">
			<h1 style="line-height:0; font-size: 0;"><span class="hidden">{$librarySystemName}</span></h1>
			{if !empty($showDisplayNameInHeader) && $librarySystemName}
				<span id="library-name-header" class="hidden-xs visible-sm">
					{if strlen($librarySystemName) < 30}<br/>{/if} {* Move the library system name down a little if it won't wrap *}
					{$librarySystemName}
				</span>
			{/if}

			{if !empty($headerText)}
				<div id="headerTextDiv">{*An id of headerText would clash with the input textarea on the Admin Page*}
					{translate text=$headerText isPublicFacing=true isAdminEnteredData=true}
				</div>
			{/if}
		</div>
	{else}
		{* Show the logo full width *}
		<div class="col-tn-12 col-xs-8 col-sm-8 col-md-8 col-lg-8" id="header-logo-container">
			<a href="{$logoLink}/">
				<img src="{if !empty($responsiveLogo)}{$responsiveLogo}{else}{img filename="logo_responsive.png"}{/if}" alt="{$librarySystemName}" title="{translate text=$logoAlt inAttribute=true isPublicFacing=true}" id="header-logo" {if !empty($showDisplayNameInHeader) && $librarySystemName}class="pull-left"{/if}>
			</a>
		</div>
	{/if}
	{if count($validLanguages) > 1 || count($allActiveThemes) > 1}
		<div id="language-selection-header" class="col-tn-12 col-xs-4 col-sm-4 col-md-4 col-lg-4 pull-right">
			<a id="theme-selection-dropdown" class="btn btn-default btn-sm" {if !empty($loggedIn)}href="/MyAccount/MyPreferences" {else} onclick="AspenDiscovery.showDisplaySettings()"{/if}>
				{if count($validLanguages) > 1 && count($allActiveThemes) > 1}
					{translate text="Languages & Display" isPublicFacing=true}&nbsp;<i class="fa fa-cog"></i>
				{elseif count($validLanguages) > 1}
					{translate text="Languages" isPublicFacing=true}&nbsp;<i class="fa fa-cog"></i>
				{else}
					{translate text="Display" isPublicFacing=true}&nbsp;<i class="fa fa-cog"></i>
				{/if}
			</a>
			{if count($validLanguages) > 1}
				{if !empty($loggedIn) && in_array('Translate Aspen', $userPermissions)}
					<div id="translationMode" style="padding-top:.5em">
						{if !empty($translationModeActive)}
							<a onclick="return AspenDiscovery.changeTranslationMode(false)" class="btn btn-primary btn-xs active" role="button">{translate text="Exit Translation Mode" isPublicFacing=true}</a>
						{else}
							<a onclick="return AspenDiscovery.changeTranslationMode(true)" class="btn btn-primary btn-xs" role="button">{translate text="Start Translation Mode" isPublicFacing=true}</a>
						{/if}
					</div>
				{/if}
			{/if}
		</div>
		<div style="display: none">
		<div id="language-selection-header" class="col-tn-12 col-xs-4 col-sm-4 col-md-4 col-lg-4 pull-right">
			{if count($validLanguages) == 2}
				<div class="btn-group btn-group-sm" role="group">
				{foreach from=$validLanguages key=languageCode item=language}
					<div class="availableLanguage btn btn-sm btn-default {if $userLang->code==$languageCode}active{/if}">
					{if $userLang->code!=$languageCode}
					<a onclick="return AspenDiscovery.setLanguage('{$languageCode}')" role="button">
					{/if}
						<div>
							{$language->displayName}
						</div>
					{if $userLang->code!=$languageCode}
					</a>
					{/if}
					</div>
				{/foreach}
				</div>
				{if !empty($loggedIn) && in_array('Translate Aspen', $userPermissions)}
					<div id="translationMode" style="padding-top:.5em">
						{if !empty($translationModeActive)}
							<a onclick="return AspenDiscovery.changeTranslationMode(false)" class="btn btn-primary btn-xs active" role="button">{translate text="Exit Translation Mode" isPublicFacing=true}</a>
						{else}
							<a onclick="return AspenDiscovery.changeTranslationMode(true)" class="btn btn-primary btn-xs" role="button">{translate text="Start Translation Mode" isPublicFacing=true}</a>
						{/if}
					</div>
				{/if}
			{elseif count($validLanguages) >= 3}
				<div class="dropdown">
					<button class="btn btn-default btn-sm dropdown-toggle" type="button" id="language-selection-dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
						{translate text="Languages" isPublicFacing=true}&nbsp;<span class="caret"></span>
					</button>
					<ul id="select-language" class="dropdown-menu" aria-labelledby="language-selection-dropdown">
						{foreach from=$validLanguages key=languageCode item=language}
							<li><a onclick="return AspenDiscovery.setLanguage('{$languageCode}')">{$language->displayName}</a></li>
						{/foreach}
					</ul>
				</div>

				{if !empty($loggedIn) && in_array('Translate Aspen', $userPermissions)}
					<div id="translationMode">
						{if !empty($translationModeActive)}
							<a onclick="return AspenDiscovery.changeTranslationMode(false)" class="btn btn-primary btn-xs active" role="button">{translate text="Exit Translation Mode" isPublicFacing=true}</a>
						{else}
							<a onclick="return AspenDiscovery.changeTranslationMode(true)" class="btn btn-primary btn-xs" role="button">{translate text="Start Translation Mode" isPublicFacing=true}</a>
						{/if}
					</div>
				{/if}
			{/if}
			{if count($allActiveThemes) > 1}
				<div class="dropdown">
					<button class="btn btn-default btn-sm dropdown-toggle" type="button" id="theme-selection-dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
						{translate text="Display" isPublicFacing=true}&nbsp;<span class="caret"></span>
					</button>
					<ul id="select-theme" class="dropdown-menu" aria-labelledby="theme-selection-dropdown">
						{foreach from=$allActiveThemes key=themeId item=themeName}
							<li><a onclick="return AspenDiscovery.setTheme('{$themeId}')">{$themeName}</a></li>
						{/foreach}
					</ul>
				</div>
			{/if}
		</div>
		</div>
	{/if}
{/strip}