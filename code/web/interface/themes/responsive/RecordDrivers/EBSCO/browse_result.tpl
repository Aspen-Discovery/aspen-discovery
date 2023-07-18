{strip}
	{if $browseMode == '1'}
		<div class="{*browse-title *}browse-list grid-item {$coverStyle} col-tn-12 col-xs-6 col-sm-6 col-md-4 col-lg-3">
			<a href="{$summUrl}" target="_blank">
				<img class="img-responsive" src="{$bookCoverUrl}" alt="{$summTitle}" title="{$summTitle}">
				<div><strong>{$summTitle}</strong></div>
			</a>
		</div>

	{else}{*Default Browse Mode (covers) *}
		<div class="browse-thumbnail grid-item {$coverStyle} {if $browseStyle == 'grid'}col-tn-6 col-xs-4 col-sm-4 col-md-3 col-lg-2{/if}">
			<a href="{$summUrl}" target="_blank">
				<div>
					<img src="{$bookCoverUrlMedium}" alt="{$summTitle}" title="{$summTitle}">
				</div>
			</a>
		</div>
	{/if}
{/strip}