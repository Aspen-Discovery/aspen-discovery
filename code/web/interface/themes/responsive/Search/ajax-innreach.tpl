{strip}
	<div class="row" id="prospectorSection">
		<div class="col-tn-12">
			<h2>{translate text="In %1%" 1=$interLibraryLoanName isPublicFacing=true}</h2>
			{translate text="Request items from other %1% libraries to be delivered to your local library for pickup." 1=$interLibraryLoanName isPublicFacing=true}
		</div>
	</div>

    {if !empty($prospectorResults)}
		<div class="row" id="prospectorSearchResultsSection">
			<div class="col-tn-12">

				<div class="striped">
					{foreach from=$prospectorResults item=prospectorResult}
						<div class="result">
							<div class="resultItemLine1">
								<a class="title" href='{$prospectorResult.link}' rel="external" onclick="window.open(this.href, 'child'); return false">
									{$prospectorResult.title}
								</a>
							</div>
							<div class="resultItemLine2">{if !empty($prospectorResult.author)}by {$prospectorResult.author} {/if}Published {$prospectorResult.pubDate}</div>
						</div>
					{/foreach}
				</div>

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
