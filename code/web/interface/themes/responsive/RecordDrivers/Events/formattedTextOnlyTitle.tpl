{strip}
	<div id="scrollerTitle{$listName}{$key}" class="scrollerTitle row">
		<div class="col-tn-12 col-lg-8">
			<span class="scrollerTextOnlyListNumber">{$key+1}) </span>
			<a href="{$titleURL}" id="descriptionTrigger{$shortId}">
				<span class="scrollerTextOnlyListTitle">{$title}</span>
			</a>
		</div>
		<div class="col-tn-12 col-lg-4">
			<span class="scrollerTextOnlyListEventDate"> {$start_date|format_date_locale:'full'} ({$start_date|format_time_range_locale:$end_date})</span>
		</div>
	</div>
{/strip}

