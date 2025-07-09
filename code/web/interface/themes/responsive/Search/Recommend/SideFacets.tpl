{strip}
{if $filterList || $sideFacetSet}
	<div id="searchFilterContainer">
		<h2 aria-label="Filter Results" class="hiddenTitle">{translate text="Filter Results" isPublicFacing=true}</h2>
		{* Filters that have been applied *}
		{if !empty($filterList)}
			<h3 id="remove-search-label" class="sidebar-label">{translate text='Applied Filters' isPublicFacing=true}</h3>
			<div class="applied-filters">
			{foreach from=$filterList item=filters key=field }
				{foreach from=$filters item=filter}
					<div class="facetValue">{translate text=$field isPublicFacing=true}: {$filter.display} <a href="{$filter.removalUrl|escape}" aria-label="{translate text="Remove Filter" inAttribute=true isPublicFacing=true}"><i class="fas fa-minus-circle fa-lg text-danger" style="display:inline; vertical-align: middle"></i></a></div>
				{/foreach}
			{/foreach}
			</div>
		{/if}

		{* Available filters *}
		{if !empty($sideFacetSet)}
			<h3 id="narrow-search-label" class="sidebar-label">{translate text='Narrow Search' isPublicFacing=true}</h3>
			<div id="facet-accordion" class="accordion">
				{foreach from=$sideFacetSet item=cluster key=title name=facetSet}
					{if count($cluster.list) > 0}
						<div class="facetList">
							<div id="facetToggle_{$title}" aria-controls="facetDetails_{$title}" class="facetTitle panel-title {if !empty($cluster.collapseByDefault) && empty($cluster.hasApplied)}collapsed{else}expanded{/if}" tabindex="0" role="button" aria-expanded="{if !empty($cluster.collapseByDefault) && empty($cluster.hasApplied)}false{else}true{/if}">
								{translate text=$cluster.label isPublicFacing=true}

								{if !empty($cluster.canLock)}
									<span class="facetLock pull-right" id="facetLock_{$title}" {if empty($cluster.hasApplied)}style="display: none"{/if} title="{translate text="Locking a facet will retain the selected filters in new searches until they are cleared" inAttribute=true isPublicFacing=true}">
										<a id="facetLock_lockIcon_{$title}" {if !empty($cluster.locked)}style="display: none"{/if} onclick="return AspenDiscovery.Searches.lockFacet('{$title}');"><i class="fas fa-lock-open fa-lg fa-fw" style="vertical-align: middle"></i></a>
										<a id="facetLock_unlockIcon_{$title}" {if empty($cluster.locked)}style="display: none"{/if} onclick="return AspenDiscovery.Searches.unlockFacet('{$title}');"><i class="fas fa-lock fa-lg fa-fw" style="vertical-align: middle"></i></a>
									</span>
								{/if}

							</div>
							<div id="facetDetails_{$title}" class="facetDetails" {if !empty($cluster.collapseByDefault) && empty($cluster.hasApplied)}style="display:none"{/if} role="region" aria-labelledby="facetToggle_{$title}">

								{if $title == 'publishDate' || $title == 'birthYear' || $title == 'deathYear' || $title == 'publishDateSort'}
									{include file="Search/Recommend/yearFacetFilter.tpl" cluster=$cluster title=$title}
								{elseif $title == 'rating_facet'}
									{include file="Search/Recommend/ratingFacet.tpl" cluster=$cluster title=$title}
								{elseif $title == 'lexile_score' || $title == 'accelerated_reader_reading_level' || $title == 'accelerated_reader_point_value'}
									{include file="Search/Recommend/sliderFacet.tpl" cluster=$cluster title=$title}
								{elseif $title == 'start_date'}
									{include file="Search/Recommend/calendarFacet.tpl" cluster=$cluster title=$title}
								{elseif !empty($cluster.showAsDropDown)}
									{include file="Search/Recommend/dropDownFacet.tpl" cluster=$cluster title=$title}
								{elseif !empty($cluster.multiSelect)}
									{include file="Search/Recommend/multiSelectFacet.tpl" cluster=$cluster title=$title}
								{else}
									{include file="Search/Recommend/standardFacet.tpl" cluster=$cluster title=$title}
								{/if}
							</div>
						</div>
						<script type="text/javascript">
							{* Initiate any checkbox with a data attribute set to data-switch=""  as a bootstrap switch *}
							{literal}
							$("#facetToggle_{/literal}{$title}{literal}").click(function() {
								var toggleButton = $(this);
								$(this).toggleClass('expanded');
								$(this).toggleClass('collapsed');
								$('#facetDetails_{/literal}{$title}{literal}').toggle()
								if (toggleButton.attr("aria-expanded") === "true") {
									$(this).attr("aria-expanded","false");
								}
								else if (toggleButton.attr("aria-expanded") === "false") {
									$(this).attr("aria-expanded","true");
								}
								return false;
							})
							$("#facetToggle_{/literal}{$title}{literal}").keypress(function() {
								var toggleButton = $(this);
								$(this).toggleClass('expanded');
								$(this).toggleClass('collapsed');
								$('#facetDetails_{/literal}{$title}{literal}').toggle()
								if (toggleButton.attr("aria-expanded") === "true") {
									$(this).attr("aria-expanded","false");
								}
								else if (toggleButton.attr("aria-expanded") === "false") {
									$(this).attr("aria-expanded","true");
								}
								return false;
							})
							{/literal}
						</script>
					{/if}
				{/foreach}
			</div>
		{/if}
	</div>
{/if}
{/strip}

{if !empty($talpaSearchLink) && !empty($tryThisSearchInTalpaSidebarSwitch) && $tryThisSearchInTalpaSidebarSwitch == 1 }
	{include file="Search/tryThisSearchOnTalpa.tpl"}
{/if}
