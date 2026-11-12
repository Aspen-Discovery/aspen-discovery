{if $openWebResourcesDirectly}
	<a href="{$resourceUrl|escape}"{if !empty($linkClass)} class="{$linkClass}"{/if}{if !empty($linkTabIndex)} tabindex="{$linkTabIndex}"{/if} onclick="return AspenDiscovery.WebBuilder.getWebResource('{$resourceId}');" aria-label="{$resourceName|escape}{if $openInNewTab} ({translate text='opens in new tab' isPublicFacing=true inAttribute=true}){/if}">{$linkContent}</a>
{else}
	<a href="/WebBuilder/WebResource?id={$resourceId}"{if !empty($linkClass)} class="{$linkClass}"{/if}{if !empty($linkTabIndex)} tabindex="{$linkTabIndex}"{/if} aria-label="{$resourceName|escape}">{$linkContent}</a>
{/if}
