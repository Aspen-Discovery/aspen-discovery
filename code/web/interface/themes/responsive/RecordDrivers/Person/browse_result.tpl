{strip}
	{if $browseMode == '1'}
		<div class="{*browse-title *}browse-list grid-item">
			<a href="{$summUrl}">
				<img class="img-responsive" src="{$bookCoverUrl}" alt="{$summTitle}" title="{$summTitle}">
				<div><strong>{$summTitle}</strong></div>
			</a>
		</div>

	{else}{*Default Browse Mode (covers) *}
		<div class="browse-thumbnail grid-item">
			<a href="{$summUrl}">
				<div>
					<img src="{$bookCoverUrlMedium}" alt="{$summTitle}" title="{$summTitle}">
					<div><strong>{$summTitle}</strong></div>
				</div>
			</a>
		</div>
	{/if}
{/strip}