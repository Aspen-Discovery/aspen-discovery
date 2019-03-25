{strip}
	{if $lastsearch}
		<a href="{$lastsearch|escape}#record{$id|escape:"url"}">{translate text="Archive Search Results"}</a> <span class="divider">&raquo;</span>
	{else}
		<a href="{$path}/Redwood/Home">Digital Archive</a> <span class="divider">&raquo;</span>
	{/if}
	{if $breadcrumbText}
		<em>{$breadcrumbText|truncate:30:"..."|escape}</em> <span class="divider">&raquo;</span>
	{/if}
	{if $subTemplate!=""}
		<em>{$subTemplate|replace:'view-':''|replace:'.tpl':''|replace:'../MyResearch/':''|capitalize|translate}</em>
	{/if}
{/strip}