{strip}
{if $topFacetSet}
<div class="topFacets">
	<br>
	{foreach from=$topFacetSet item=cluster key=title}
		{if $cluster.label == 'Category' || $cluster.label == 'Format Category'}
			{if ($categorySelected == false)}
				<div class="formatCategories top-facet" id="formatCategories">
					<div id="categoryValues" class="row">
						{foreach from=$cluster.list item=thisFacet name="narrowLoop"}
							{if $thisFacet.isApplied}
								<div class="categoryValue categoryValue_{translate text=$thisFacet.value|lower|replace:' ':''} col-tn-2">
									<a href="{$thisFacet.removalUrl|escape}" class="removeFacetLink" onclick="trackEvent('Remove Facet', 'formatCategory', '{$thisFacet.value|escape}');" title="Remove Filter">
										<div class="row">
											<div class="col-xs-6">
												<img src="{img filename=$thisFacet.imageNameSelected}" alt="{translate text=$thisFacet.value|escape}">
											</div>
											<div class="col-xs-6 formatCategoryLabel">
												{$thisFacet.value|escape}
												<br>(Remove)
											</div>
										</div>
									</a>
								</div>
							{else}
								<div class="categoryValue categoryValue_{translate text=$thisFacet.value|lower|replace:' ':''} col-tn-2">
									<a href="{$thisFacet.url|escape}" onclick="trackEvent('Apply Facet', 'formatCategory', '{$thisFacet.value|escape}');">
										<div class="row">
											<div class="col-xs-6">
												<img src="{img filename=$thisFacet.imageName}" alt="{translate text=$thisFacet.value|escape}">
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
			{/if}
		{elseif preg_match('/available/i', $cluster.label)}
			<div id="availabilityControlContainer" class="row text-center top-facet">
				<div id="availabilityControl" class="btn-group" data-toggle="buttons-radio">
					{foreach from=$cluster.list item=thisFacet name="narrowLoop"}
						{if $thisFacet.isApplied}
							<button type="button" id="{$thisFacet.value|escape|regex_replace:'/[()\s]/':''}" class="btn btn-primary" name="availabilityControls">{$thisFacet.value|escape}{if $thisFacet.count > 0} ({$thisFacet.count|number_format:0:".":","}){/if}</button>
						{else}
							<button type="button" id="{$thisFacet.value|escape|regex_replace:'/[()\s]/':''}" class="btn btn-default" name="availabilityControls" data-url="{$thisFacet.url|escape}" onclick="window.location = $(this).data('url')" >{$thisFacet.value|escape}{if $thisFacet.count > 0} ({$thisFacet.count|number_format:0:".":","}){/if}</button>
						{/if}
					{/foreach}
				</div>
			</div>
		{else}
			<div class="authorbox top-facet">
				<h5>{translate text=$cluster.label}<span>{translate text="top_facet_suffix"}</span></h5>
				<table class="facetsTop navmenu narrow_begin">
					{foreach from=$cluster.list item=thisFacet name="narrowLoop"}
						{if $smarty.foreach.narrowLoop.iteration == ($topFacetSettings.rows * $topFacetSettings.cols) + 1}
							<tr id="more{$title}"><td><a href="#" onclick="moreFacets('{$title}'); return false;">{translate text='more'} ...</a></td></tr>
							</table>
							<table class="facetsTop navmenu narrowGroupHidden" id="narrowGroupHidden_{$title}">
							<tr><th colspan="{$topFacetSettings.cols}"><div class="top_facet_additional_text">{translate text="top_facet_additional_prefix"}{translate text=$cluster.label}<span>{translate text="top_facet_suffix"}</span></div></th></tr>
						{/if}
						{if $smarty.foreach.narrowLoop.iteration % $topFacetSettings.cols == 1}
							<tr>
						{/if}
						{if $thisFacet.isApplied}
							<td>{$thisFacet.value|escape}</a> <img src="{$path}/images/silk/tick.png" alt="Selected" > <a href="{$thisFacet.removalUrl|escape}" class="removeFacetLink" onclick="trackEvent('Remove Facet', '{$cluster.label}', '{$thisFacet.value|escape}');">(remove)</a></td>
						{else}
							<td><a href="{$thisFacet.url|escape}" onclick="trackEvent('Apply Facet', '{$cluster.label}', '{$thisFacet.value|escape}');">{$thisFacet.value|escape}</a> ({$thisFacet.count})</td>
						{/if}
						{if $smarty.foreach.narrowLoop.iteration % $topFacetSettings.cols == 0 || $smarty.foreach.narrowLoop.last}
							</tr>
						{/if}
						{if $smarty.foreach.narrowLoop.total > ($topFacetSettings.rows * $topFacetSettings.cols) && $smarty.foreach.narrowLoop.last}
							<tr><td><a href="#" onclick="lessFacets('{$title}'); return false;">{translate text='less'} ...</a></td></tr>
						{/if}
					{/foreach}
				</table>
			</div>
		{/if}
	{/foreach}
	</div>
{else}
	<br>
{/if}
{/strip}
