<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0">
	<channel>
		<title>Results For {$lookfor|escape}</title>
		{if $result.response.params.rows > $result.response.numFound}
			<description>Displaying the top {$result.response.numFound} search results of {$result.response.params.rows} found.</description>
		{else}
			<description>Displaying {$result.response.numFound} search results.</description>
		{/if}
		<link>{$searchUrl|escape}</link>

		{foreach from=$result.response.docs item="doc"}
			<item>
				<title>{$doc.title_display|escape}</title>
				<link>{$doc.recordUrl|escape}</link>
				{if $doc.author_display}
					<author>{$doc.author_display|escape}</author>
				{/if}
				<guid isPermaLink="true">{$doc.recordUrl|escape}</guid>
				{if !empty($doc.publishDateSort)}
					<pubDate>01 Jan {$doc.publishDateSort} 00:00:00 GMT</pubDate>
				{/if}
				<description>{$doc.rss_description|escape}</description>
			</item>
		{/foreach}
	</channel>
</rss>
