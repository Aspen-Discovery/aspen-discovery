{strip}
	<div class="row" id="prospectorSection">
		<div class="col-tn-12 col-sm-9">
			<h2>{translate text="In %1%" 1=$interLibraryLoanName isPublicFacing=true}</h2>
			{translate text="Request items from other %1% libraries to be delivered to your local library for pickup." 1=$interLibraryLoanName isPublicFacing=true}
		</div>
	</div>

    {if !empty($prospectorResults)}
		<div class="row" id="prospectorSearchResultsSection">
			<div class="col-tn-12">
				<table class="table table-striped">
					<th>
						{translate text="Title" isPublicFacing=true}
					</th>
					<th>
						{translate text="Author" isPublicFacing=true}
					</th>
					<th>
						{translate text="Pub. Date" isPublicFacing=true}
					</th>
					<th>
						{translate text="Format" isPublicFacing=true}
					</th>
					{foreach from=$prospectorResults item=prospectorTitle}
					  {if $similar.recordId != -1}
						  <tr>
							  <td>
						      <a href="{$prospectorTitle.link}" rel="external" onclick="window.open (this.href, 'child'); return false"><h5>{$prospectorTitle.title|removeTrailingPunctuation|escape}</h5></a>
							  </td>

						    <td>
								  {if $prospectorTitle.author}<small>{$prospectorTitle.author|escape}</small>{/if}
						    </td>
							  <td>
								  {if $prospectorTitle.pubDate}<small>{$prospectorTitle.pubDate|escape}</small>{/if}
							  </td>
							  <td>
								  {if $prospectorTitle.format}<small>{$prospectorTitle.format|escape}</small>{/if}
							  </td>
						  </tr>
					  {/if}
					{/foreach}
				</table>
			</div>
		</div>
    {/if}

	<div class="row" id="prospectorLinkSection">
		<div class="col-tn-12">
			<br>
			<button class="btn btn-sm btn-info pull-right" onclick="window.open('{$prospectorLink}', 'child'); return false">{translate text="See more results in %1%" 1=$interLibraryLoanName isPublicFacing=true}</button>
		</div>
	</div>

	<style type="text/css">
		{literal}
		#prospectorSection,#prospectorSearchResultsSection {
			padding-top: 15px;
		}
		#prospectorLinkSection {
			padding-bottom: 15px;
		}
		{/literal}
	</style>
{/strip}
