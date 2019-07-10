{strip}
{if $topFacetSet}
<div class="topFacets">
	<br>
	{foreach from=$topFacetSet item=cluster key=title}
		{if $cluster.label == 'Category' || $cluster.label == 'Format Category'}
			<div class="formatCategories top-facet" id="formatCategories">
				<div id="categoryValues" class="row">
					{foreach from=$cluster.list item=thisFacet name="narrowLoop"}
						{if $thisFacet.isApplied}
							<div class="categoryValue categoryValue_{$thisFacet.value|lower|replace:' ':''} col-tn-2">
								<a href="{$thisFacet.removalUrl|escape}" class="removeFacetLink" title="Remove Filter">
									<div class="row">
										<div class="col-xs-6">
											<img src="{img filename=$thisFacet.imageNameSelected}" alt="{translate text=$thisFacet.value|escape inAttribute=true}">
										</div>
										<div class="col-xs-6 formatCategoryLabel">
											{translate text=$thisFacet.value|escape}
											<br>({translate text=Remove})
										</div>
									</div>
								</a>
							</div>
						{else}
							<div class="categoryValue categoryValue_{translate inAttribute=true text=$thisFacet.value|lower|replace:' ':''} col-tn-2">
								<a href="{$thisFacet.url|escape}">
									<div class="row">
										<div class="col-xs-6">
											<img src="{img filename=$thisFacet.imageName}" alt="{translate inAttribute=true text=$thisFacet.value|escape}">
										</div>
										<div class="col-xs-6 formatCategoryLabel">
											{translate text=$thisFacet.value|escape}<br>({$thisFacet.count|number_format:0:".":","})
										</div>
									</div>
								</a>
							</div>
						{/if}
					{/foreach}
				</div>
				<div class="clearfix"></div>
			</div>
		{elseif preg_match('/available/i', $cluster.label)}
			<div id="availabilityControlContainer" class="row text-center top-facet">
				<div id="availabilityControl" class="btn-group" data-toggle="buttons-radio">
					{foreach from=$cluster.list item=thisFacet name="narrowLoop"}
						{if $thisFacet.isApplied}
							<button type="button" id="{$thisFacet.value|escape|regex_replace:'/[()\s]/':''}" class="btn btn-primary" name="availabilityControls">{$thisFacet.value|translate}{if $thisFacet.count > 0} ({$thisFacet.count|number_format:0:".":","}){/if}</button>
						{else}
							<button type="button" id="{$thisFacet.value|escape|regex_replace:'/[()\s]/':''}" class="btn btn-default" name="availabilityControls" data-url="{$thisFacet.url|escape}" onclick="window.location = $(this).data('url')" >{$thisFacet.value|translate}{if $thisFacet.count > 0} ({$thisFacet.count|number_format:0:".":","}){/if}</button>
						{/if}
					{/foreach}
				</div>
			</div>
		{/if}
	{/foreach}
	</div>
{else}
	<br>
{/if}
{/strip}
