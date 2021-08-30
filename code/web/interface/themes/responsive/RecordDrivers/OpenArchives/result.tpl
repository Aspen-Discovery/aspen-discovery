{strip}
<div id="openArchivesResult{$resultIndex|escape}" class="resultsList row">
	{if $showCovers}
		<div class="coversColumn col-xs-3 col-sm-3 col-md-3 col-lg-2 text-center" aria-hidden="true" role="presentation">
			{if $disableCoverArt != 1}
				<a href="{$openArchiveUrl}" class="alignleft listResultImage" onclick="AspenDiscovery.OpenArchives.trackUsage('{$id}')" target="_blank" tabindex="-1">
					<img src="{$bookCoverUrl}" class="listResultImage img-thumbnail" alt="{$title|removeTrailingPunctuation|highlight|truncate:180:"..."}">
				</a>
			{/if}
		</div>
	{/if}


	<div class="{if !$showCovers}col-xs-12{else}col-xs-9 col-sm-9 col-md-9 col-lg-10{/if}">{* May turn out to be more than one situation to consider here *}
		{* Title Row *}

		<div class="row">
			<div class="col-xs-12">
				<span class="result-index">{$resultIndex})</span>&nbsp;
				<a href="{$openArchiveUrl}" class="result-title notranslate" onclick="AspenDiscovery.OpenArchives.trackUsage('{$id}')" target="_blank">
					{if !$title|removeTrailingPunctuation} {translate text='Title not available' isPublicFacing=true}{else}{$title|removeTrailingPunctuation|highlight|truncate:180:"..."}{/if}
				</a>
				{if isset($summScore)}
					&nbsp;(<a href="#" onclick="return AspenDiscovery.showElementInPopup('Score Explanation', '#scoreExplanationValue{$summId|escape}');">{$summScore}</a>)
				{/if}
			</div>
		</div>

		{if !empty($type)}
			<div class="row">
				<div class="result-label col-tn-3">{translate text="Type"} </div>
				<div class="result-value col-tn-8 notranslate">
					{implode subject=$type}
				</div>
			</div>
		{/if}

		{if !empty($source)}
			<div class="row">
				<div class="result-label col-tn-3">{translate text="Source"} </div>
				<div class="result-value col-tn-8 notranslate">
					{implode subject=$source glue="<br/>"}
				</div>
			</div>
		{/if}

		{if !empty($publisher)}
			<div class="row">
				<div class="result-label col-tn-3">{translate text="Publisher"} </div>
				<div class="result-value col-tn-8 notranslate">
					{implode subject=$publisher}
				</div>
			</div>
		{/if}

		{if !empty($date)}
			<div class="row">
				<div class="result-label col-tn-3">{translate text="Date" isPublicFacing=true} </div>
				<div class="result-value col-tn-8 notranslate">
					{implode subject=$date}
				</div>
			</div>
		{/if}

		{if count($appearsOnLists) > 0}
			<div class="row">
				<div class="result-label col-tn-3">
					{if count($appearsOnLists) > 1}
						{translate text="Appears on these lists"}
					{else}
						{translate text="Appears on list"}
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
							<a onclick="$('#moreLists_OpenArchives{$recordDriver->getId()}').show();$('#moreListsLink_OpenArchives{$recordDriver->getId()}').hide();" id="moreListsLink_OpenArchives{$recordDriver->getId()}">{translate text="More Lists..."}</a>
							<div id="moreLists_OpenArchives{$recordDriver->getId()}" style="display:none">
						{/if}
					{/foreach}
					{if !empty($showMoreLists)}
						</div>
					{/if}
				</div>
			</div>
		{/if}

		{* Description Section *}
		{if $description}
			<div class="row visible-xs">
				<div class="result-label col-tn-3 col-xs-3">{translate text="Description"}</div>
				<div class="result-value col-tn-9 col-xs-9"><a id="descriptionLink{$resultIndex|escape}" href="#" onclick="$('#descriptionValue{$resultIndex|escape},#descriptionLink{$resultIndex|escape}').toggleClass('hidden-xs');return false;">Click to view</a></div>
			</div>

			<div class="row">
				{* Hide in mobile view *}
				<div class="result-value hidden-xs col-sm-12" id="descriptionValue{$resultIndex|escape}">
					{$description|highlight|truncate_html:450:"..."}
				</div>
			</div>
		{/if}

		<div class="row">
			<div class="col-xs-12">
				{include file='OpenArchives/result-tools-horizontal.tpl' recordUrl=$openArchiveUrl showMoreInfo=true}
			</div>
		</div>
	</div>
</div>
{/strip}