{strip}
	{if $lastSearch}
		<a href="{$lastSearch|escape}#record{$id|escape:"url"}">{translate text="Archive Search Results"}</a> <span class="divider">&raquo;</span>
	{else}
		<a href="{$path}/Archive/Home">Local Digital Archive</a> <span class="divider">&raquo;</span>
	{/if}
	{if $breadcrumbText}
		<em>{$breadcrumbText|truncate:30:"..."|escape}</em> <span class="divider">&raquo;</span>
	{/if}
{/strip}