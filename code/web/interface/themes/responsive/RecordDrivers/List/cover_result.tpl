{strip}
	{if $browseMode == 'grid'}
	<div class="{*browse-title *}browse-list">
		<a {*onclick="return alert('{$summId}'" *} href="{$summUrl}">
			{*<div>*}
			<img class="img-responsive" src="{img filename="lists.png"}" alt="{$summTitle} by {$summAuthor}" title="{$summTitle} by {$summAuthor}">
			{*</div>*}
			<div><strong>{$summTitle}</strong><br> by {$summAuthor}</div>
		</a>
	</div>

	{else}{*Default Browse Mode (covers) *}

	<div class="browse-thumbnail">
		<a href="{$summUrl}">
			{*  TODO: add pop-up for list *}
			<div>
				<img src="{img filename="lists.png"}{*$bookCoverUrlMedium*}" alt="{$summTitle} by {$summAuthor}" title="{$summTitle} by {$summAuthor}">
			</div>
		</a>
	</div>
	{/if}
{/strip}