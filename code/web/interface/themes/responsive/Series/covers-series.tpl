{strip}
	<div id="home-page-browse-results" class="results-covers-view home-page-browse-results-grid {if $browseStyle == 'grid'}home-page-browse-results-grid-grid{else}home-page-browse-results-grid-masonry{/if}" style="padding-top:2em">
		{if $browseStyle == 'masonry'}
		<div class="masonry grid">
			<div class="grid-col grid-col--1"></div>
			<div class="grid-col grid-col--2"></div>
			<div class="grid-col grid-col--3"></div>
			<div class="grid-col grid-col--4"></div>
			<div class="grid-col grid-col--5"></div>
			<div class="grid-col grid-col--6"></div>
			{/if}
			{foreach from=$recordSet item=record name="recordLoop"}
				{* This is raw HTML -- do not escape it: *}
				{$record}
				{foreachelse}
				{include file="Browse/noResults.tpl"}
			{/foreach}
			{if $browseStyle == 'masonry'}</div>{/if}
	</div>
{/strip}
