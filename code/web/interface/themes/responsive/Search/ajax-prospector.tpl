<ProspectorSearchResults>{strip}
	<![CDATA[
	<div id='prospectorSearchResults'>
		<div id='prospectorSearchResultsHeader'>
			<img id='prospectorMan' src='{$path}/interface/themes/responsive/images/prospector_man.png'/>

			<div id='prospectorSearchResultsTitle'>In Prospector</div>
			<div id='prospectorSearchResultsNote'>Request items from other Prospector libraries to be delivered to your local library for pickup.</div>
			<div class='clearfix'>&nbsp;</div>
		</div>
		{if $prospectorResults}
			<div class="striped">
				{foreach from=$prospectorResults item=prospectorResult}
					<div class='result'>
						<div class='resultItemLine1'>
							<a class="title" href='{$prospectorResult.link}' rel="external" onclick="window.open (this.href, 'child'); return false">
								{$prospectorResult.title}
							</a>
						</div>
						<div class='resultItemLine2'>by {$prospectorResult.author} Published {$prospectorResult.pubDate}</div>
					</div>
				{/foreach}
			</div>
		{/if}
		<div id='prospectorSearchResultsFooter'>
			<div id='moreResultsFromProspector'>
				<button class="btn btn-sm btn-info" onclick="window.open ('{$prospectorLink}', 'child'); return false">See more results in Prospector</button>
			</div>
			<div class='clearfix'>&nbsp;</div>
		</div>
	</div>
	]]>
{/strip}</ProspectorSearchResults>