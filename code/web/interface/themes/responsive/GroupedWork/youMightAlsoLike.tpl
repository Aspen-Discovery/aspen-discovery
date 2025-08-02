{strip}
{if $numTitles == 0}
	<div class="alert alert-info">{translate text="Sorry, we could not find any additional titles." isPublicFacing=true}</div>
{else}
	<div class="row">
		{foreach from=$youMightAlsoLikeTitles item=title}
			<div class="col-tn-12 col-sm-4" style="text-align: center">
				<div class="row">
					<div class="col-tn-12">
						<a href="{$title->getLinkUrl()}">
							<img src="{$title->getBookcoverUrl('medium')}" class="listResultImage img-thumbnail {$coverStyle}" alt="{$title->getTitle()|escape}">
						</a>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-12" style="padding-top: 5px">
					<a href="{$title->getLinkUrl()}?activeSearchSorce={$activeSearchSource}&activeFormat={$activeFormat}" class="btn btn-primary btn-sm">{translate text="More Info" isPublicFacing=true}</a>
					</div>
				</div>
			</div>
		{/foreach}
	</div>
{/if}
{/strip}
