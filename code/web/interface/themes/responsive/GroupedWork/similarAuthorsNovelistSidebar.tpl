{strip}
	<div id="similar-authors" class="sidebar-links row">
		<div class="">
			<div id="similar-authors-label" class="sidebar-label" title="{translate text="NoveList provides detailed suggestions for other authors you might want to read if you enjoyed this book.  Suggestions are based on recommendations from librarians and other contributors." inAttribute=true isPublicFacing=true}" data-toggle="tooltip" data-placement="right">
				{translate text="Similar Authors" isPublicFacing=true}
			</div>
			<div class="similar-authors">
				{foreach from=$similarAuthors item=author name="recordLoop"}
					<div class="facetValue">
						{* This is raw HTML -- do not escape it: *}
						<div class="notranslate"><a href='{$author.link}' title="{$author.reason|escape:html}" data-toggle="tooltip" data-placement="bottom">{$author.name}</a></div>
					</div>
				{/foreach}
			</div>
		</div>
	</div>
{/strip}