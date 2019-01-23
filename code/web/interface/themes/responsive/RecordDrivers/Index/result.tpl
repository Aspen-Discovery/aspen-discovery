{strip}
{* //QUESTION: is this Depricated *}
<div id="record{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}" class="resultsList row">
	<div class="imageColumn col-md-3">
		<div class="row">
			<div class="selectTitle hidden-phone col-md-4">
				<label for="selected{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}" class="resultIndex checkbox"><strong>{$resultIndex}</strong>
					<input type="checkbox" class="titleSelect" name="selected[{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}]" id="selected{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}" />&nbsp;
				</label>
			</div>
			{/strip}
			<div class="col-md-7 text-center">
				{if $disableCoverArt != 1}
					{*<div class='descriptionContent{$summShortId|escape}' style='display:none'>{$summDescription}</div>*}
					<a href="{$summUrl}">
						<img src="{$bookCoverUrlMedium}"
						     class="listResultImage img-polaroid {*descriptionTrigger*}"
						     alt="{translate text='Cover Image'}"
						     {*data-record_id="{$summId}"
						     data-source="VuFind"
						     data-content_class=".descriptionContent{$summShortId|escape}"*}>
					</a>
				{/if}
				{strip}
				{include file="Record/title-rating.tpl" ratingClass="" recordId=$summId shortId=$summShortId ratingData=$summRating}
				{* Only Grouped Work Ratings implemented now, the above template does not exist. (Is this page in use?) plb 6-29-2015 *}
			</div>
		</div>
	</div>

	<div class="col-md-9">
		<div class="row">
			{if $summScore}({$summScore}) {/if}
			<strong>
				<a href="{$summUrl}" class="title">{if !$summTitle|removeTrailingPunctuation}{translate text='Title not available'}{else}{$summTitle|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}</a>
				{if $summTitleStatement}
					&nbsp;-&nbsp;{$summTitleStatement|removeTrailingPunctuation|truncate:180:"..."|highlight}
				{/if}
			</strong>
		</div>

		<div class="row">
			<div class="resultDetails col-md-9">
				{if $summAuthor}
					<div class="row">
						<div class="result-label col-md-3">Author: </div>
						<div class="col-md-9 result-value">
							{if is_array($summAuthor)}
								{foreach from=$summAuthor item=author}
									<a href='{$path}/Author/Home?author="{$author|escape:"url"}"'>{$author|highlight}</a>
								{/foreach}
							{else}
								<a href='{$path}/Author/Home?author="{$summAuthor|escape:"url"}"'>{$summAuthor|highlight}</a>
							{/if}
						</div>
					</div>
				{/if}

				{if $summISBN}
					<div class="series{$summISBN} row">
						<div class="result-label col-md-3">Series: </div>
						<div class="col-md-9 result-value">Loading...</div>
					</div>
				{/if}

				{if $summEditions}
					<div class="row hidden-phone">
						<div class="result-label col-md-3" id="resultInformationEdition{$summShortId|escape}">{translate text='Edition'}:</div>
						<div class="col-md-9 result-value">{$summEditions.0|escape}</div>
					</div>
				{/if}

				{if $summPublicationDates || $summPublishers || $summPublicationPlaces}
					<div class="row">

						<div class="result-label col-md-3">Published: </div>
						<div class="col-md-9 result-value">
							{$summPublicationPlaces.0|escape}{$summPublishers.0|escape}{$summPublicationDates.0|escape}
						</div>
					</div>
				{/if}

				{* Highlighted term *}
				{if !empty($summSnippetCaption) || !!empty($summSnippet)}
					<div class="row hidden-phone">
						{if !empty($summSnippetCaption)}<div class="result-label col-md-3">{translate text=$summSnippetCaption}:</div>{/if}
						{if !empty($summSnippet)}<div class="col-md-9 result-value"><span class="quotestart">&#8220;</span>...{$summSnippet|highlight}...<span class="quoteend">&#8221;</span></div>{/if}
					</div>
				{/if}

				<div class="row">
					<div class="result-label col-md-3">Format: </div>
					<div class="col-md-9 result-value">
						<strong>
							{if is_array($summFormats)}
								{foreach from=$summFormats item=format}
									<span class="iconlabel" >{translate text=$format}</span>&nbsp;
								{/foreach}
							{else}
								<span class="iconlabel">{translate text=$summFormats}</span>
							{/if}
						</strong>
					</div>
				</div>

				{if $summPhysical}
					<div class="row hidden-phone">
						<div class="result-label col-md-3">{translate text='Physical Desc'}:</div>
						<div class="col-md-9 result-value">{$summPhysical.0|escape}</div>
					</div>
				{/if}

				<div class="row">
					<div class="result-label col-md-3">{translate text='Location'}:</div>
					<div class="col-md-9 bold result-value" id="locationValue{$summShortId|escape}">Loading...</div>
				</div>

				<div class="row">
					<div class="result-label col-md-3">{translate text='Call Number'}:</div>
					<div class="col-md-9 bold result-value" id="callNumberValue{$summShortId|escape}">Loading...</div>
				</div>

				<div class="row">
					<div class="result-label col-md-3">{translate text='Status'}:</div>
					<div class="col-md-9 bold statusValue result-value" id="statusValue{$summShortId|escape}">Loading...</div>
				</div>

				<div class="row">
					<div class="col-md-12 result-value" id="descriptionValue{$summShortId|escape}">{$summDescription}</div>
				</div>
			</div>

			<div class="resultActions col-md-3">
				{include file='Record/result-tools.tpl' id=$summId shortId=$shortId module=$summModule summTitle=$summTitle ratingData=$summRating recordUrl=$summUrl}
			</div>
		</div>
	</div>

	<script type="text/javascript">
		VuFind.ResultsList.addIdToStatusList('{$summId|escape}', 'VuFind', '{$useUnscopedHoldingsSummary}');
		{if $summISBN}
		VuFind.ResultsList.addIdToSeriesList('{$summISBN}');
		{/if}
	</script>
</div>
{/strip}