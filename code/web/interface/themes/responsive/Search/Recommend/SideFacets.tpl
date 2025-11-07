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
						<a class="btn btn-default btn-sm facetValueBadge" href="{$filter.removalUrl|escape}" aria-label="{translate text="Remove Filter" inAttribute=true isPublicFacing=true}">
							<i class="fas fa-xmark text-danger remove-filter-icon" style="display:inline; vertical-align: middle"></i> {$filter.display}
						</a>
					{/foreach}
				{/foreach}
			</div>
			{if !empty($removeAllFiltersUrl)}
				<div class="removeAllFilters">
					<a class="btn btn-default btn-sm removeAllFiltersBtn" href="{$removeAllFiltersUrl}">{translate text="Clear All" isPublicFacing=true}</a>
				</div>
			{/if}
		{/if}

		{* Available filters *}
		{if !empty($sideFacetSet)}
			<h3 id="narrow-search-label" class="sidebar-label">{translate text='Narrow Search' isPublicFacing=true}</h3>
			<div id="facet-accordion" class="accordion">
				{foreach from=$sideFacetSet item=cluster key=title name=facetSet}
					{* Show facets that have values loaded OR are placeholders for async loading *}
					{if count($cluster.list) > 0 || (isset($cluster.loadedValues) && $cluster.loadedValues === false)}
						<div class="facetList">
							<div id="facetToggle_{$title}" aria-controls="facetDetails_{$title}" class="facetTitle panel-title {if !empty($cluster.collapseByDefault) && empty($cluster.hasApplied)}collapsed{else}expanded{/if}" tabindex="0" role="button" aria-expanded="{if !empty($cluster.collapseByDefault) && empty($cluster.hasApplied)}false{else}true{/if}" data-facet-loaded="{if !empty($cluster.loadedValues) || count($cluster.list) > 0}true{else}false{/if}" data-search-id="{$searchId}" data-facet-name="{if !empty($cluster.field)}{$cluster.field}{else}{$title}{/if}">
								{translate text=$cluster.label isPublicFacing=true}

								{if !empty($cluster.canLock)}
									<span class="facetLock pull-right" id="facetLock_{$title}" {if empty($cluster.hasApplied)}style="display: none"{/if} title="{translate text="Locking a facet will retain the selected filters in new searches until they are cleared" inAttribute=true isPublicFacing=true}">
										<a id="facetLock_lockIcon_{$title}" {if !empty($cluster.locked)}style="display: none"{/if} onclick="return AspenDiscovery.Searches.lockFacet('{$title}');"><i class="fas fa-lock-open fa-lg fa-fw" style="vertical-align: middle"></i></a>
										<a id="facetLock_unlockIcon_{$title}" {if empty($cluster.locked)}style="display: none"{/if} onclick="return AspenDiscovery.Searches.unlockFacet('{$title}');"><i class="fas fa-lock fa-lg fa-fw" style="vertical-align: middle"></i></a>
									</span>
								{/if}

							</div>
							<div id="facetDetails_{$title}" class="facetDetails" {if !empty($cluster.collapseByDefault) && empty($cluster.hasApplied)}style="display:none"{/if} role="region" aria-labelledby="facetToggle_{$title}">
								{* Loading spinner for async facet loading *}
								<div class="facet-loading-spinner" style="display:none; padding:15px; text-align:center;">
									<i class="fas fa-spinner fa-spin"></i> {translate text="Loading..." isPublicFacing=true}
								</div>

								{* Facet content container *}
								<div class="facet-list-container">
								{if count($cluster.list) > 0}
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
								{else}
									{* Empty placeholder for unloaded facets *}
								{/if}
								</div>{* Close facet-list-container *}
							</div>
						</div>
						<script type="text/javascript">
							{literal}
							$("#facetToggle_{/literal}{$title}{literal}").on('click', function(e) {
								e.preventDefault();
								var toggleButton = $(this);

								// Check if we need to load facet values first (async facet loading)
								if (toggleButton.attr('data-facet-loaded') === 'false') {
									AspenDiscovery.Searches.loadFacetValues(toggleButton);
								} else {
									// Normal expand/collapse toggle
									toggleButton.toggleClass('expanded collapsed');
									$('#facetDetails_{/literal}{$title}{literal}').toggle();
									var isExpanded = toggleButton.attr("aria-expanded") === "true";
									toggleButton.attr("aria-expanded", !isExpanded);
								}
								return false;
							});

							$("#facetToggle_{/literal}{$title}{literal}").on('keypress', function(e) {
								if (e.key === 'Enter' || e.key === ' ') {
									e.preventDefault();
									var toggleButton = $(this);

									// Check if we need to load facet values first (async facet loading)
									if (toggleButton.attr('data-facet-loaded') === 'false') {
										AspenDiscovery.Searches.loadFacetValues(toggleButton);
									} else {
										// Normal expand/collapse toggle
										toggleButton.toggleClass('expanded collapsed');
										$('#facetDetails_{/literal}{$title}{literal}').toggle();
										var isExpanded = toggleButton.attr("aria-expanded") === "true";
										toggleButton.attr("aria-expanded", !isExpanded);
									}
									return false;
								}
							});
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
