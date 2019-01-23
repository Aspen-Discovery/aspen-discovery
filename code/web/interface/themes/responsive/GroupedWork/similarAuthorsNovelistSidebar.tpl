{strip}
	{* TODO: put this in a help pop-up
	<div class="alert alert-info">NoveList provides detailed suggestions for other authors you might want to read if you enjoyed this book.  Suggestions are based on recommendations from librarians and other contributors.</div>
	*}
	<div id="similarAuthorsNoveList" class="jcarousel ajax-carousel col-tn-12">
		<ul>
		{foreach from=$similarAuthors item=author name="recordLoop"}
			<li{* class="novelist-similar-item"*}>
				{* This is raw HTML -- do not escape it: *}
				<div class="novelist-similar-item-header notranslate"><a href='{$author.link}'>{$author.name}</a></div>
				<div class="novelist-similar-item-reason">
					{$author.reason}
				</div>
			</li>
		{/foreach}
		</ul>
	</div>
{/strip}