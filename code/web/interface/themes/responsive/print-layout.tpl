<!DOCTYPE html>
<html lang="{$userLang->code}" data-context="print-layout">
<head prefix="og: http://ogp.me/ns#">
    {strip}
		<title>{$pageTitleShortAttribute|truncate:64:"..."}{if empty($isMobile)} | {$librarySystemName|escape}{/if}</title>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
        {if !empty($google_verification_key)}
			<meta name="google-site-verification" content="{$google_verification_key}">
        {/if}

        {if !empty($metadataTemplate)}
            {include file=$metadataTemplate}
        {/if}
		<meta property="og:site_name" content="{$site.title|removeTrailingPunctuation|escape:html}"/>
        {if !empty($og_title)}
			<meta property="og:title" content="{$og_title|removeTrailingPunctuation|escape:html}"/>
        {/if}
        {if !empty($og_type)}
			<meta property="og:type" content="{$og_type|escape:html}"/>
        {/if}
        {if !empty($og_image)}
			<meta property="og:image" content="{$og_image|escape:html}"/>
        {/if}
        {if !empty($og_url)}
			<meta property="og:url" content="{$og_url|escape:html}"/>
        {/if}
		<link type="image/x-icon" href="{$favicon}" rel="shortcut icon">
        {include file="cssAndJsIncludes.tpl"}
    {/strip}
</head>
<body class="module_{$module} action_{$action}{if !empty($masqueradeMode)} masqueradeMode{/if}{if !empty($loggedIn)} loggedIn{else} loggedOut{/if}" id="{$module}-{$action}">
{strip}
	<div id="page-container">
		{if $printLibraryName || $printLibraryLogo}
		<div class="container-fluid">
			<div class="row">
				<div>
					{if $printLibraryLogo}
                    <div class="col-xs-12">
	                    <img src="{if !empty($responsiveLogo)}{$responsiveLogo}{else}{img filename="logo_responsive.png"}{/if}" alt="{$librarySystemName|escape}" title="{translate text=$logoAlt inAttribute=true isPublicFacing=true}" style="max-height: 150px;">
                    </div>
                    {/if}
					{if $printLibraryName}
                    <div class="col-xs-12">
	                    <h1>{$librarySystemName|escape}</h1>
                        {if !empty($headerText)}
	                        <p>{translate text=$headerText isPublicFacing=true isAdminEnteredData=true}</p>
                        {/if}
                    </div>
					{/if}
				</div>
			</div>
		</div>
		{/if}

       <div>
			<div id="content-container">
				<div class="row">
					<div class="col-xs-12" id="main-content">
						<div role="main">
                            {if !empty($module)}
                                {include file="$module/$pageTemplate"}
                            {else}
                                {include file="$pageTemplate"}
                            {/if}
						</div>
					</div>
				</div>
			</div>
		</div>

	</div>
    {include file="modal_dialog.tpl"}

    {include file="tracking.tpl"}

    {if !empty($semanticData)}
        {include file="jsonld.tpl"}
    {/if}
{/strip}

</body>
</html>