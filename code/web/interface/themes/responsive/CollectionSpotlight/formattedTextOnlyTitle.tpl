{strip}
	<div id="scrollerTitle{$listName}{$key}" class="scrollerTitle">
		<span class="scrollerTextOnlyListNumber">{$key+1}) </span>
		<a href="{$titleURL}" id="descriptionTrigger{$shortId}">
			<span class="scrollerTextOnlyListTitle">{$title}</span>
		</a>
		{if !empty($author)}
			{if strpos($shortId, ':') === false} {* Catalog Items *}
				<span class="scrollerTextOnlyListBySpan"> {translate text="by" isPublicFacing=true} </span>
				<a href="{$titleURL}" id="descriptionTrigger{$shortId}">
					<span class="scrollerTextOnlyListAuthor">{$author}</span>
				</a>
			{/if}
		{/if}
	</div>
{/strip}

