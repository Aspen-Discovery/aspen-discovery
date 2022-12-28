{strip}
<div id="record{$summId|escape}" class="resultsList">
	<div class="row">
		{if !empty($showCovers)}
			<div class="coversColumn col-xs-3 col-sm-3{if !empty($viewingCombinedResults)} col-md-3 col-lg-2{/if} text-center" aria-hidden="true" role="presentation">
				<a href="/Person/{$summId}" tabindex="-1">
					{if !empty($summPicture)}
					<img src="/files/thumbnail/{$summPicture}" class="alignleft listResultImage" alt="{$summTitle|removeTrailingPunctuation|truncate:180:"..."|highlight}"/><br />
					{else}
					<img src="/interface/themes/responsive/images/person.png" class="alignleft listResultImage" alt="{translate text='No Cover Image' inAttribute=true isPublicFacing=true}"/><br />
					{/if}
				</a>
			</div>
		{/if}

		<div class="{if empty($showCovers)}col-xs-12{else}col-xs-9 col-sm-9{if !empty($viewingCombinedResults)} col-md-9 col-lg-10{/if}{/if}">{* May turn out to be more than one situation to consider here *}
			<div class="row">
				<div class="col-xs-12">
					<span class="result-index">{$resultIndex})</span>&nbsp;

					<a href="/Person/{$summId}" class="result-title notranslate">
						{if empty($summTitle)} {translate text='Title not available' isPublicFacing=true}{else}{$summTitle|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}
					</a>
					{if isset($summScore)}
						&nbsp;(<a href="#" onclick="return AspenDiscovery.showElementInPopup('Score Explanation', '#scoreExplanationValue{$summId|escape}');">{$summScore}</a>)
					{/if}
				</div>
			</div>

			<div class="row">
				<div class="resultDetails col-md-9">
					{if !empty($birthDate)}
						<div class="row">
							<div class='result-label col-md-3'>{translate text="Born" isPublicFacing=true} </div>
							<div class="col-md-9 result-value">{$birthDate}</div>
						</div>
					{/if}
					{if !empty($deathDate)}
						<div class="row">
							<div class='result-label col-md-3'>{translate text="Died" isPublicFacing=true} </div>
							<div class="col-md-9 result-value">{$deathDate}</div>
						</div>
					{/if}
					{if !empty($numObits)}
						<div class="row">
							<div class='result-label col-md-3'>{translate text="Num. Obits" isPublicFacing=true} </div>
							<div class="col-md-9 result-value">{$numObits}</div>
						</div>
					{/if}
					{if !empty($dateAdded)}
						<div class="row">
							<div class='result-label col-md-3'>{translate text="Added" isPublicFacing=true} </div>
							<div class="col-md-9 result-value">{$dateAdded|date_format}</div>
						</div>
					{/if}
					{if !empty($lastUpdate)}
						<div class="row">
							<div class='result-label col-md-3'>{translate text="Last Updated" isPublicFacing=true} </div>
							<div class="col-md-9 result-value">{$lastUpdate|date_format}</div>
						</div>
					{/if}
				</div>

				{if empty($viewingCombinedResults)}
					<div class="row">
						<div class="col-xs-12">
							{include file='Genealogy/result-tools-horizontal.tpl' recordUrl=$summUrl showMoreInfo=true}
						</div>
					</div>
				{/if}
			</div>
		</div>
	</div>
</div>
{/strip}