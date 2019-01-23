{strip}
	<div class="results-covers home-page-browse-thumbnails">
		{foreach from=$recordSet item=record name="recordLoop"}
			{* This is raw HTML -- do not escape it: *}
			{$record}
		{foreachelse}
			{include file="Browse/noResults.tpl"}
		{/foreach}
	</div>
{/strip}