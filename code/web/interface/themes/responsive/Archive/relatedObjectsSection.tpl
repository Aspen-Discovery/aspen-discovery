{strip}
	{if !empty($solrSearchDebug)}
		<div id="solrSearchOptionsToggle" onclick="$('#solrSearchOptions').toggle()">{translate text="Show Search Options"}</div>
		<div id="solrSearchOptions" style="display:none">
			<pre>Search options: {$solrSearchDebug}</pre>
		</div>
	{/if}

	{if !empty($solrLinkDebug)}
		<div id='solrLinkToggle' onclick='$("#solrLink").toggle()'>{translate text="Show Solr Link"}</div>
		<div id='solrLink' style='display:none'>
			<pre>{$solrLinkDebug}</pre>
		</div>
	{/if}
	{include file="Archive/accordion-items.tpl" relatedItems=$directlyRelatedObjects.objects}
{/strip}