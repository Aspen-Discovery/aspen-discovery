{strip}
	<div id="scrollerTitle{$listName}{$key}" class="scrollerTitle">
		<span class="scrollerTextOnlyListNumber">{$key+1}) </span>
		<a href="{$titleURL}" id="descriptionTrigger{$shortId}">
			<span class="scrollerTextOnlyListTitle">{$title}</span>
		</a>
		{if strpos($shortId, ':') === false} {* Catalog Items *}
			<span class="scrollerTextOnlyListBySpan"> by </span>
			<a href="{$titleURL}" id="descriptionTrigger{$shortId}">
				<span class="scrollerTextOnlyListAuthor">{$author}</span>
			</a>
		{else}{* Archive Objects *}
			<span class="scrollerTextOnlyListBySpan">; </span>
			<a href="{$titleURL}" id="descriptionTrigger{$shortId}">
				<span class="scrollerTextOnlyListAuthor">{$author}</span>
			</a>
		{/if}
	</div>
{/strip}

