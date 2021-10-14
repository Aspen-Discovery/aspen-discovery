{strip}
	{if $browseMode == '1'}
		<div class="browse-list grid-item">
			<a  href="{$summUrl}">
				<img class="img-responsive" src="{$bookCoverUrl}" alt="{$summTitle} by {$summAuthor}" title="{$summTitle} by {$summAuthor}">
				<div><strong>{$summTitle}</strong><br> by {$summAuthor}</div>
			</a>
		</div>

	{else}{*Default Browse Mode (covers) *}

		<div class="browse-thumbnail grid-item">
			<a href="{$summUrl}">
				{*  TODO: add pop-up for list *}
				<div>
					<img src="{$bookCoverUrlMedium}" alt="{$summTitle} by {$summAuthor}" title="{$summTitle} by {$summAuthor}">
				</div>
			</a>
		</div>
	{/if}
{/strip}