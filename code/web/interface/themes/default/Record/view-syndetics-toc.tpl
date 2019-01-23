{strip}
	{* This template gets loaded into tableofContentsPlaceHolder via AJAX *}
	<div class="tableOfContents">
		{foreach from=$tocData item=entry}
			{if $entry.label}
				<div class="tocEntry">
					<span class="tocLabel">{$entry.label} </span>
					<span class="tocTitle">{$entry.title} </span>
					<span class="tocPage">{$entry.page}</span>
				</div>
			{else}
				<div>
					<span class="trackNumber">{$entry.number} </span>
					<span class="trackName">{$entry.name}</span>
				</div>
			{/if}
		{/foreach}
	</div>
{/strip}