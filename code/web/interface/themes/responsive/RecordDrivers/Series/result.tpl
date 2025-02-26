{strip}
<div id="record{$summId|escape}" class="resultsList row">
	{if !empty($showCovers)}
		<div class="coversColumn col-xs-3 col-sm-3 col-md-3 col-lg-2 text-center" aria-hidden="true" role="presentation">
			{if $disableCoverArt != 1}
				<a href="/Series/{$summShortId}" class="alignleft listResultImage" tabindex="-1">
					<div class="listResultImage border">
						{if !empty($isNew)}<span class="list-cover-badge">{translate text="New!" isPublicFacing=true}</span> {/if}
						<img src="{$bookCoverUrl}" class="img-thumbnail {$coverStyle}" alt="{$summTitle|removeTrailingPunctuation|highlight|escapeCSS|truncate:180:"..."}">
					</div>
				</a>
			{/if}
		</div>
	{/if}


	<div class="{if empty($showCovers)}col-xs-12{else}col-xs-9 col-sm-9 col-md-9 col-lg-10{/if}">{* May turn out to be more than one situation to consider here *}
		{* Title Row *}

		<div class="row">
			<div class="col-xs-12">
	<span class="result-index">{$resultIndex})</span>&nbsp;
				<a href="/Series/{$summShortId}" class="result-title notranslate">
					{if !$summTitle|removeTrailingPunctuation} {translate text='Title not available' isPublicFacing=true}{else}{$summTitle|removeTrailingPunctuation|highlight|truncate:180:"..."}{/if}
				</a>
				{if isset($summScore)}
					&nbsp;(<a href="#" onclick="return AspenDiscovery.showElementInPopup('Score Explanation', '#scoreExplanationValue{$summId|escape}');">{$summScore}</a>)
				{/if}
			</div>
		</div>
		{if !empty($summAuthor)}
			<div class="row">
				<div class="result-label col-tn-3">{if is_array($summAuthor) && count($summAuthor) > 1}{translate text="Authors" isPublicFacing=true}{else}{translate text="Author" isPublicFacing=true}{/if} </div>
				<div class="result-value col-tn-9 notranslate">
					{if is_array($summAuthor)}
						{foreach from=$summAuthor item=author}
							{if $author == "Various"}
								{translate text="Various" isPublicFacing=true}
							{else}
								<a href='/Author/Home?author="{$author|escape:"url"}"'>{$author|highlight}</a> <br/>
							{/if}
						{/foreach}
					{else}
						{if $author == "Various"}
							{translate text="Various" isPublicFacing=true}
						{else}
							<a href='/Author/Home?author="{$summAuthor|escape:"url"}"'>{$summAuthor|highlight}</a>
						{/if}
					{/if}
				</div>
			</div>
		{/if}

		{if !empty($summAudience)}
			<div class="row">
				<div class="result-label col-tn-3">{translate text="Audience" isPublicFacing=true} </div>
				<div class="result-value col-sm-8 col-xs-12">
					{if is_array($summAudience)}
						{implode subject=$summAudience glue=', ' translate=true isPublicFacing=true isMetadata=true}
					{else}
						{translate text=$summAudience isPublicFacing=true isMetadata=true}
					{/if}
				</div>
			</div>
		{/if}

		{if !empty($summNumTitles)}
			<div class="row">
				<div class="result-label col-tn-3">{translate text="Number of Titles" isPublicFacing=true} </div>
				<div class="result-value col-tn-9 notranslate">
					{translate text="%1% titles are in this series." 1=$summNumTitles isPublicFacing=true}
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
		{if !empty($summDescription)}
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
			{include file='Series/result-tools.tpl' id=$summId recordUrl=$summUrl showMoreInfo=true}
		</div>
	</div>
</div>
{/strip}
