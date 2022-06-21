{strip}
{if $topFacetSet}
<div class="topFacets">
	<br>
	{foreach from=$topFacetSet item=cluster key=title}
		{if $cluster.isFormatCategory}
			<div class="formatCategories top-facet" id="formatCategories">
				<div id="categoryValues" class="row">
					{foreach from=$cluster.list item=thisFacet name="narrowLoop"}
						{if $thisFacet.isApplied}
							<div class="categoryValue categoryValue_{$thisFacet.value|lower|replace:' ':''} col-tn-2">
								<a href="{$thisFacet.removalUrl|escape}" class="removeFacetLink" title="Remove Filter">
									<div class="row">
										<div class="col-xs-6">
											<img src="{img filename=$thisFacet.imageNameSelected}" alt="{translate text=$thisFacet.value|escape inAttribute=true isPublicFacing=true}">
										</div>
										<div class="col-xs-6 formatCategoryLabel">
											{translate text=$thisFacet.display|escape isPublicFacing=true}
											<br>({translate text=Remove isPublicFacing=true})
										</div>
									</div>
								</a>
							</div>
						{else}
							<div class="categoryValue categoryValue_{translate inAttribute=true text=$thisFacet.value|lower|replace:' ':'' isPublicFacing=true} col-tn-2">
								<a href="{$thisFacet.url|escape}">
									<div class="row">
										<div class="col-xs-6">
											<img src="{img filename=$thisFacet.imageName}" alt="{translate text='Filter Format by %1%', 1=$thisFacet.value translateParameters=true isPublicFacing=true inAttribute=true}">
										</div>
										<div class="col-xs-6 formatCategoryLabel">
											{translate text=$thisFacet.display|escape isPublicFacing=true}<br>({$thisFacet.count|number_format:0:".":","})
										</div>
									</div>
								</a>
							</div>
						{/if}
					{/foreach}
				</div>
				<div class="clearfix"></div>
			</div>
		{elseif $cluster.isAvailabilityToggle}
			<div id="availabilityControlContainer" class="row top-facet" >
				<div id="availabilityControlCell" class="col-xs-12">
					<div id="availabilityControl" class="btn-group" data-toggle="buttons-radio" style="display: flex;align-items: center;justify-content: center;">
						{foreach from=$cluster.list item=thisFacet name="narrowLoop"}
							{if $thisFacet.isApplied}
								<button type="button" id="{$thisFacet.value|escape|regex_replace:'/[()\s]/':''}" class="btn btn-primary btn-wrap" name="availabilityControls">{translate text=$thisFacet.display isPublicFacing=true}{if $thisFacet.count > 0} ({$thisFacet.count|number_format:0:".":","}){/if}</button>
							{else}
								<button type="button" id="{$thisFacet.value|escape|regex_replace:'/[()\s]/':''}" class="btn btn-default btn-wrap" name="availabilityControls" data-url="{$thisFacet.url|escape}" onclick="window.location = $(this).data('url')" >{translate text=$thisFacet.display isPublicFacing=true}{if $thisFacet.count > 0} ({$thisFacet.count|number_format:0:".":","}){/if}</button>
							{/if}
						{/foreach}
					</div>
				</div>
			</div>
		{/if}
	{/foreach}
	</div>
{else}
	<br>
{/if}
{/strip}
