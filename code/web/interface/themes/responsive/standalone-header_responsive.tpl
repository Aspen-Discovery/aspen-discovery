{strip}

	{* In mobile view this is the top div and spans across the screen *}
	{* Logo Div *}
	<div class="col-xs-12 col-sm-3 col-md-3 col-lg-3">
		<a href="{$logoLink}/">
			<img src="{if $responsiveLogo}{$responsiveLogo}{else}{img filename="logo_responsive.png"}{/if}" alt="{$librarySystemName}" title="{translate text=$logoAlt inAttribute=true isPublicFacing=true}" id="header-logo" {if $showDisplayNameInHeader && $librarySystemName}class="pull-left"{/if}>
		</a>
	</div>
	{* Heading Info Div *}
	<div id="headingInfo" class="hidden-xs hidden-sm col-md-5 col-lg-5">
		{if $showDisplayNameInHeader && $librarySystemName}
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
{/strip}