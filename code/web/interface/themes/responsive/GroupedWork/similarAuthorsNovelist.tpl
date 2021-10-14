{strip}
	<div class="alert alert-info">{translate text="NoveList provides detailed suggestions for other authors you might want to read if you enjoyed this book.  Suggestions are based on recommendations from librarians and other contributors." isPublicFacing=true}</div>
	<div id="similarAuthorsNoveList" class="striped div-striped">
		{foreach from=$similarAuthors item=author name="recordLoop"}
			<div class="novelist-similar-item">
				{* This is raw HTML -- do not escape it: *}
				<div class="novelist-similar-item-header notranslate"><a href='{$author.link}'>{$author.name}</a></div>
				<div class="novelist-similar-item-reason">
					{$author.reason}
				</div>
			</div>
		{/foreach}
	</div>
{/strip}