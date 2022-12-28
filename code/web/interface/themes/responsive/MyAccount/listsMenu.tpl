{strip}
	{foreach from=$lists item=list}
		{if $list.id != -1}
			<div class="myAccountLink"><a href="{$list.url}">{$list.name}{if !empty($list.numTitles)} ({$list.numTitles}){/if}</a></div>
			{*<div class="myAccountLink"><a href="{$list.url}">{$list.name}{if !empty($list.numTitles)} <span class="badge">{$list.numTitles}</span>{/if}</a></div>*}
		{/if}
	{/foreach}
{/strip}