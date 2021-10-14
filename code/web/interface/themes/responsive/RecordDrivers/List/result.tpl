{strip}
<div id="record{$summId|escape}" class="resultsList row">
	{if $showCovers}
		<div class="coversColumn col-xs-3 col-sm-3 col-md-3 col-lg-2 text-center" aria-hidden="true" role="presentation">
			{if $disableCoverArt != 1}
				<a href="/MyAccount/MyList/{$summShortId}" class="alignleft listResultImage" tabindex="-1">
					<img src="{$bookCoverUrl}" class="listResultImage img-thumbnail" alt="{$summTitle|removeTrailingPunctuation|highlight|escape:css|truncate:180:"..."}">
				</a>
			{/if}
		</div>
	{/if}


	<div class="{if !$showCovers}col-xs-12{else}col-xs-9 col-sm-9 col-md-9 col-lg-10{/if}">{* May turn out to be more than one situation to consider here *}
		{* Title Row *}

		<div class="row">
			<div class="col-xs-12">
				<span class="result-index">{$resultIndex})</span>&nbsp;
				<a href="/MyAccount/MyList/{$summShortId}" class="result-title notranslate">
					{if !$summTitle|removeTrailingPunctuation} {translate text='Title not available' isPublicFacing=true}{else}{$summTitle|removeTrailingPunctuation|highlight|truncate:180:"..."}{/if}
				</a>
				{if isset($summScore)}
					&nbsp;(<a href="#" onclick="return AspenDiscovery.showElementInPopup('Score Explanation', '#scoreExplanationValue{$summId|escape}');">{$summScore}</a>)
				{/if}
			</div>
		</div>

		{if $summAuthor}
			<div class="row">
				<div class="result-label col-tn-3">{translate text="Created By" isPublicFacing=true} </div>
				<div class="result-value col-tn-9 notranslate">
					{if is_array($summAuthor)}
						{foreach from=$summAuthor item=author}
							{$author|highlight}
						{/foreach}
					{else}
						{$summAuthor|highlight}
					{/if}
				</div>
			</div>
		{/if}

		{if $summNumTitles}
			<div class="row">
				<div class="result-label col-tn-3">{translate text="Number of Titles" isPublicFacing=true} </div>
				<div class="result-value col-tn-9 notranslate">
					{translate text="%1% titles are in this list." 1=$summNumTitles}
				</div>
			</div>
		{/if}

		{if count($appearsOnLists) > 0}
			<div class="row">
				<div class="result-label col-tn-3">
					{if count($appearsOnLists) > 1}
						{translate text="Appears on these lists" isPublicFacing=true}
					{else}
						{translate text="Appears on list" isPublicFacing=true}
					{/if}
				</div>
				<div class="result-value col-tn-8">
					{assign var=showMoreLists value=false}
					{if count($appearsOnLists) >= 5}
						{assign var=showMoreLists value=true}
					{/if}
					{foreach from=$appearsOnLists item=appearsOnList name=loop}
					<a href="{$appearsOnList.link}">{$appearsOnList.title}</a><br/>
					{if !empty($showMoreLists) && $smarty.foreach.loop.iteration == 3}
					<a onclick="$('#moreLists_List{$recordDriver->getId()}').show();$('#moreListsLink_List{$recordDriver->getId()}').hide();" id="moreListsLink_List{$recordDriver->getId()}">{translate text="More Lists..." isPublicFacing=true}</a>
					<div id="moreLists_List{$recordDriver->getId()}" style="display:none">
						{/if}
						{/foreach}
						{if !empty($showMoreLists)}
					</div>
					{/if}
				</div>
			</div>
		{/if}

		{* Description Section *}
		{if $summDescription}
			<div class="row visible-xs">
				<div class="result-label col-tn-3 col-xs-3">{translate text="Description" isPublicFacing=true}</div>
				<div class="result-value col-tn-9 col-xs-9"><a id="descriptionLink{$summId|escape}" href="#" onclick="$('#descriptionValue{$summId|escape},#descriptionLink{$summId|escape}').toggleClass('hidden-xs');return false;">{translate text="Click to view" isPublicFacing=true}</a></div>
			</div>

			<div class="row">
				{* Hide in mobile view *}
				<div class="result-value hidden-xs col-sm-12" id="descriptionValue{$summId|escape}">
					{$summDescription|highlight|truncate_html:450:"..."}
				</div>
			</div>
		{/if}


		<div class="resultActions row">
			{include file='Lists/result-tools.tpl' id=$summId shortId=$shortId module=$summModule summTitle=$summTitle ratingData=$summRating recordUrl=$summUrl}
		</div>
	</div>
</div>
{/strip}