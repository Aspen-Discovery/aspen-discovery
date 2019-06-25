{strip}
{if $recordCount > 0 || $filterList || ($sideFacetSet && $recordCount > 0)}
	<div id="searchFilterContainer">
		{if isset($checkboxFilters) && count($checkboxFilters) > 0}
			<p>
				{include file='checkboxFilters.tpl'}
			</p>
		{/if}
		{* Filters that have been applied *}
		{if $filterList}
			<div id="remove-search-label" class="sidebar-label"{if $displaySidebarMenu} style="display: none"{/if}>{translate text='Applied Filters'}</div>
			<div class="applied-filters"{if $displaySidebarMenu} style="display: none"{/if}>
			{foreach from=$filterList item=filters key=field }
				{foreach from=$filters item=filter}
					<div class="facetValue">{translate text=$field}: {$filter.display} <a href="{$filter.removalUrl|escape}"><img src="{$path}/images/silk/delete.png" alt="Delete"/></a></div>
				{/foreach}
			{/foreach}
			</div>
		{/if}

		{* Available filters *}
		{if $sideFacetSet && $recordCount > 0}
			<div id="narrow-search-label" class="sidebar-label"{if $displaySidebarMenu} style="display: none"{/if}>{translate text='Narrow Search'}</div>
			<div id="facet-accordion" class="accordion"{if $displaySidebarMenu} style="display: none"{/if}>
				{foreach from=$sideFacetSet item=cluster key=title name=facetSet}
					{if count($cluster.list) > 0}
						<div class="facetList">
							<div class="facetTitle panel-title {if $cluster.collapseByDefault}collapsed{else}expanded{/if}" onclick="$(this).toggleClass('expanded');$(this).toggleClass('collapsed');$('#facetDetails_{$title}').toggle()">
								{translate text=$cluster.label}
							</div>
							<div id="facetDetails_{$title}" class="facetDetails" {if $cluster.collapseByDefault}style="display:none"{/if}>

								{if $title == 'publishDate' || $title == 'birthYear' || $title == 'deathYear'}
									{include file="Search/Recommend/yearFacetFilter.tpl" cluster=$cluster title=$title}
								{elseif $title == 'rating_facet'}
									{include file="Search/Recommend/ratingFacet.tpl" cluster=$cluster title=$title}
								{elseif $title == 'lexile_score' || $title == 'accelerated_reader_reading_level' || $title == 'accelerated_reader_point_value'}
									{include file="Search/Recommend/sliderFacet.tpl" cluster=$cluster title=$title}
								{elseif !empty($cluster.showAsDropDown)}
									{include file="Search/Recommend/dropDownFacet.tpl" cluster=$cluster title=$title}
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