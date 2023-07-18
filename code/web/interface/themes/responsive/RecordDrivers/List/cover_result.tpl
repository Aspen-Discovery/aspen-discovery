{strip}
	{if $browseMode == '1'}
		<div class="browse-list grid-item col-tn-12 col-xs-6 col-sm-6 col-md-4 col-lg-3">
			<a  href="{$summUrl}">
				<img class="img-responsive" src="{$bookCoverUrl}" alt="{$summTitle} by {$summAuthor}" title="{$summTitle} by {$summAuthor}">
				<div><strong>{$summTitle}</strong><br> by {$summAuthor}</div>
			</a>
		</div>

	{else}{*Default Browse Mode (covers) *}

		<div class="browse-thumbnail grid-item {if $browseStyle == 'grid'}col-tn-6 col-xs-4 col-sm-4 col-md-3 col-lg-2{/if}">
			<a href="{$summUrl}">
				{*  TODO: add pop-up for list *}
				<div>
					<img src="{$bookCoverUrlMedium}" alt="{$summTitle} by {$summAuthor}" title="{$summTitle} by {$summAuthor}">
				</div>
			</a>
		</div>
	{/if}
{/strip}