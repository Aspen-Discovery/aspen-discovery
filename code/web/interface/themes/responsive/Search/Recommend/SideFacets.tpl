{strip}
{if $filterList || $sideFacetSet}
	<div id="searchFilterContainer">
		<h2 aria-label="Filter Results" class="hiddenTitle">{translate text="Filter Results" isPublicFacing=true}</h2>
		{* Filters that have been applied *}
		{if $filterList}
			<div id="remove-search-label" class="sidebar-label">{translate text='Applied Filters' isPublicFacing=true}</div>
			<div class="applied-filters">
			{foreach from=$filterList item=filters key=field }
				{foreach from=$filters item=filter}
					<div class="facetValue">{translate text=$field isPublicFacing=true}: {$filter.display} <a href="{$filter.removalUrl|escape}" aria-label="{translate text="Remove Filter" inAttribute=true isPublicFacing=true}"><i class="fas fa-minus-circle fa-lg text-danger" style="display:inline; vertical-align: middle"></i></a></div>
				{/foreach}
			{/foreach}
			</div>
		{/if}

		{* Available filters *}
		{if $sideFacetSet}
			<div id="narrow-search-label" class="sidebar-label">{translate text='Narrow Search' isPublicFacing=true}</div>
			<div id="facet-accordion" class="accordion">
				{foreach from=$sideFacetSet item=cluster key=title name=facetSet}
					{if count($cluster.list) > 0}
						<div class="facetList">
							<div class="facetTitle panel-title {if $cluster.collapseByDefault && !$cluster.hasApplied}collapsed{else}expanded{/if}" onclick="$(this).toggleClass('expanded');$(this).toggleClass('collapsed');$('#facetDetails_{$title}').toggle()" onkeypress="$(this).toggleClass('expanded');$(this).toggleClass('collapsed');$('#facetDetails_{$title}').toggle()" tabindex="0" role="group">
								{translate text=$cluster.label isPublicFacing=true}

								{if $cluster.canLock}
									<span class="facetLock pull-right" id="facetLock_{$title}" {if !$cluster.hasApplied}style="display: none"{/if} title="{translate text="Locking a facet will retain the selected filters in new searches until they are cleared" inAttribute=true isPublicFacing=true}">
										<a id="facetLock_lockIcon_{$title}" {if $cluster.locked}style="display: none"{/if} onclick="return AspenDiscovery.Searches.lockFacet('{$title}');"><i class="fas fa-lock-open fa-lg fa-fw" style="vertical-align: middle"></i></a>
										<a id="facetLock_unlockIcon_{$title}" {if !$cluster.locked}style="display: none"{/if} onclick="return AspenDiscovery.Searches.unlockFacet('{$title}');"><i class="fas fa-lock fa-lg fa-fw" style="vertical-align: middle"></i></a>
									</span>
								{/if}

							</div>
							<div id="facetDetails_{$title}" class="facetDetails" {if $cluster.collapseByDefault && !$cluster.hasApplied}style="display:none"{/if}>

								{if $title == 'publishDate' || $title == 'birthYear' || $title == 'deathYear' || $title == 'publishDateSort'}
									{include file="Search/Recommend/yearFacetFilter.tpl" cluster=$cluster title=$title}
								{elseif $title == 'rating_facet'}
									{include file="Search/Recommend/ratingFacet.tpl" cluster=$cluster title=$title}
								{elseif $title == 'lexile_score' || $title == 'accelerated_reader_reading_level' || $title == 'accelerated_reader_point_value'}
									{include file="Search/Recommend/sliderFacet.tpl" cluster=$cluster title=$title}
								{elseif !empty($cluster.showAsDropDown)}
									{include file="Search/Recommend/dropDownFacet.tpl" cluster=$cluster title=$title}
								{elseif !empty($cluster.multiSelect)}
									{include file="Search/Recommend/multiSelectFacet.tpl" cluster=$cluster title=$title}
								{else}
									{include file="Search/Recommend/standardFacet.tpl" cluster=$cluster title=$title}
								{/if}
							</div>
						</div>
					{/if}
				{/foreach}
			</div>
		{/if}
	</div>
{/if}
{/strip}