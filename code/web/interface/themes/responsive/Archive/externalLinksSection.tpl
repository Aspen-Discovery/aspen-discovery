{strip}
	{foreach from=$externalLinks item=link}
		<div>
			<a href="{$link.link}" target="_blank">
				{$link.text}
			</a>
		</div>
	{/foreach}
{/strip}