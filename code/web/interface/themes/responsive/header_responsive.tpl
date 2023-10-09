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
		<div id="language-selection-header" class="col-tn-12 col-xs-4 col-sm-4 col-md-4 col-lg-4 pull-right">
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
{/strip}