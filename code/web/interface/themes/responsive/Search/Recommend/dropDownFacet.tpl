<select class="facetDropDown" onchange="changeDropDownFacet('facetDropDown-{$title}', '{$cluster.label}')" id="facetDropDown-{$title}">
	<option selected="selected">Choose {$cluster.label}</option>
	{foreach from=$cluster.list item=thisFacet name="narrowLoop"}
		<option data-destination="{$thisFacet.url}" data-label="{$thisFacet.display|escape}">{$thisFacet.display|escape}{if $thisFacet.count != ''}&nbsp;({$thisFacet.count}){/if}</option>
	{/foreach}
</select>