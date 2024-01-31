<h1>{translate text="No Results Found" isPublicFacing=true}</h1>
<p class="alert alert-info">
	{if (empty($lookfor))}
		{translate text="Your search did not match any resources." isPublicFacing=true}
	{else}
		{translate text="Your search - <b>%1%</b> - did not match any resources." 1=$lookfor|escape:html isPublicFacing=true}
	{/if}
</p>

{if !empty($parseError)}
	<p class="error">{translate text='nohit_parse_error'}</p>
{/if}

{if !empty($showExploreMoreBar)}
	<div id="explore-more-bar-placeholder"></div>
	<script type="text/javascript">
		$(document).ready(
			function () {ldelim}
				AspenDiscovery.Searches.loadExploreMoreBar('{$exploreMoreSection}', '{$exploreMoreSearchTerm|escape:"html"}');
			{rdelim}
		);
	</script>
{/if}