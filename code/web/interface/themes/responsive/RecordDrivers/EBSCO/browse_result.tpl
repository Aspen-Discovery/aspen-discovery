{strip}
	{if $browseMode == '1'}
		<div class="{*browse-title *}browse-list">
			<a href="{$summUrl}">
				<img class="img-responsive" src="{$bookCoverUrl}" alt="{$summTitle}" title="{$summTitle}">
				<div><strong>{$summTitle}</strong></div>
			</a>
		</div>

	{else}{*Default Browse Mode (covers) *}
		<div class="browse-thumbnail">
			<a href="{$summUrl}">
				<div>
					<img src="{$bookCoverUrlMedium}" alt="{$summTitle}" title="{$summTitle}">
				</div>
			</a>
		</div>
	{/if}
{/strip}