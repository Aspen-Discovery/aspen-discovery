{strip}
    {if $showCovers == true}
		<div class="coversColumn col-xs-3 col-sm-3 col-md-3 col-lg-2 text-center" aria-hidden="true" role="presentation">
			<a href="/MyAccount/MyList/{$list->id}" class="alignleft listResultImage">
				<img src="/bookcover.php?type=list&amp;id={$list->id}&amp;size=medium&amp;dateUpdated={$list->dateUpdated}" class="listResultImage img-thumbnail{if $useOriginalCoverUrls} use-original-covers{/if} {$coverStyle}" alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}">
			</a>
		</div>
    {/if}
	<div class="{if empty($showCovers)}col-xs-11{else}col-xs-8 col-sm-8 col-md-8 col-lg-9{/if}">{* May turn out to be more than one situation to consider here *}

    {* Title Row *}

	<div class="row">
		<div class="col-xs-12">
			<span class="result-index">{$resultIndex+1+$startingNumber})</span>&nbsp;
			<a href="/MyAccount/MyList/{$list->id}" class="result-title notranslate">
                {$list->title}
			</a>
		</div>
	</div>

	<div class="row">
		<div class="result-label col-tn-3">{translate text="Number of Titles" isPublicFacing=true} </div>
		<div class="result-value col-tn-9 notranslate">
            {translate text="%1% titles are in this list." 1=$list->numValidListItems() isPublicFacing=true}
		</div>
	</div>

    {* Description Section *}
    {if $list->description && $enableListDescriptions}
		<div class="row visible-xs">
			<div class="result-label col-tn-3 col-xs-3">{translate text="Description" isPublicFacing=true}</div>
			<div class="result-value col-tn-9 col-xs-9"><a id="descriptionLink{$list->id|escape}" href="#" onclick="$('#descriptionValue{$list->id|escape},#descriptionLink{$list->id|escape}').toggleClass('hidden-xs');return false;">Click to view</a></div>
		</div>

		<div class="row">
            {* Hide in mobile view *}
			<div class="result-value hidden-xs col-sm-12" id="descriptionValue{$list->id|escape}">
                {$list->description|truncate_html:450:"..."}
			</div>
		</div>
    {/if}
	<div class="row">

		<div class="col-xs-12">
			<p class="text-muted"><small>{translate text='Created on' isPublicFacing=true} {$list->created|date_format:"%B %e, %Y %l:%M %p"}<br>
                    {translate text='Last Updated' isPublicFacing=true} {$list->dateUpdated|date_format:"%B %e, %Y %l:%M %p"}</small></p>
		</div>
	</div>

	<div class="row">
		<div class="col-xs-12"><span class="badge">{if $list->public == '0'}{translate text="Private" isPublicFacing=true}{else}{translate text="Public" isPublicFacing=true}{/if}</span> {if $list->searchable == '1' && $list->public == '1'}<span class="badge">{translate text="Searchable" isPublicFacing=true}</span> {/if}{if $list->displayListAuthor == '1'}<span class="badge">{translate text="Display List Author" isPublicFacing=true}{/if}</div>
	</div>

</div>
{/strip}
