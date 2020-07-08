{strip}
	<div id="scrollerTitle{$listName}{$key}" class="scrollerTitle row">
		<div class="col-tn-12 col-lg-8">
			<span class="scrollerTextOnlyListNumber">{$key+1}) </span>
			<a href="{$titleURL}" id="descriptionTrigger{$shortId}">
				<span class="scrollerTextOnlyListTitle">{$title}</span>
			</a>
		</div>
		<div class="col-tn-12 col-lg-4">
			<span class="scrollerTextOnlyListEventDate"> {$start_date|date_format:"%a %b %e, %Y (%l:%M%p"} - {$end_date|date_format:"%l:%M%p"})</span>
		</div>
	</div>
{/strip}

