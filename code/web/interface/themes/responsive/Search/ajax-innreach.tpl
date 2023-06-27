{strip}
	<div class="row" id="innReachSection">
		<div class="col-tn-12">
			<h2>{translate text="In %1%" 1=$interLibraryLoanName isPublicFacing=true}</h2>
			{translate text="Request items from other %1% libraries to be delivered to your local library for pickup." 1=$interLibraryLoanName isPublicFacing=true}
		</div>
	</div>

    {if !empty($innReachResults)}
		<div class="row" id="innReachSearchResultsSection">
			<div class="col-tn-12">

				<div class="striped">
					{foreach from=$innReachResults item=innReachResult}
						<div class="result">
							<div class="resultItemLine1">
								<a class="title" href='{$innReachResult.link}' rel="external" onclick="window.open(this.href, 'child'); return false">
									{$innReachResult.title}
								</a>
							</div>
							<div class="resultItemLine2">{if !empty($innReachResult.author)}by {$innReachResult.author} {/if}{if !empty($innReachResult.pubDate)}Published {$innReachResult.pubDate}{/if}</div>
						</div>
					{/foreach}
				</div>

			</div>
		</div>
    {/if}

	<div class="row" id="innReachLinkSection">
		<div class="col-tn-12">
			<br>
			<button class="btn btn-sm btn-info pull-right" onclick="window.open('{$innReachLink}', 'child'); return false">{translate text="See more results in %1%" 1=$interLibraryLoanName isPublicFacing=true}</button>
		</div>
	</div>

	<style>
		{literal}
		#innReachSection,#innReachSearchResultsSection {
			padding-top: 15px;
		}
		#innReachLinkSection {
			padding-bottom: 15px;
		}
		{/literal}
	</style>
{/strip}
