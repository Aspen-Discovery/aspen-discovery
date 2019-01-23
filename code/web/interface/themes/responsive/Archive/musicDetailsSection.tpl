{strip}
	{if $musicGenres}
		<div class="row">
			<div class="result-label col-sm-4">Genres: </div>
			<div class="result-value col-sm-8">
				{foreach from=$musicGenres item="musicGenre"}
					{if $musicGenre.link}<a href="{$musicGenre.link}">{/if}
					{$musicGenre.label}{if $musicGenre.lccn} ({$musicGenre.lccn}){/if}
					{if $musicGenre.link}</a>{/if}<br/>
				{/foreach}
			</div>
		</div>
	{/if}

	{if $albums}
		<div class="row">
			<div class="result-label col-sm-4">Album Information: </div>
			<div class="result-value col-sm-8">
				{foreach from=$albums item="album"}
					{$album.title}
					{if $album.track}
						&nbsp;Track {$album.track}
					{/if}
					{if $album.disc}
						&nbsp;Disc {$album.disc}
					{/if}
					{if $album.recordLabel}
						&nbsp;- {$album.recordLabel}
					{/if}
					<br/>
				{/foreach}
			</div>
		</div>
	{/if}
{/strip}