{if !empty($summAuthor)}
	<div class="result-author result-label col-sm-4 col-xs-12">{translate text="Author" isPublicFacing=true} </div>
	<div class="result-author result-value col-sm-8 col-xs-12 notranslate">
		{if is_array($summAuthor)}
			{foreach from=$summAuthor item=author}
				<a href='/Author/Home?author="{$author|escape:"url"}"'>{$author|highlight}</a>
			{/foreach}
		{else}
			<a href='/Author/Home?author="{$summAuthor|escape:"url"}"'>{$summAuthor|highlight}</a>
		{/if}
	</div>
{/if}
{if !empty($showSeries)}
	{if $summSeries && empty($summSeries.allHidden)}
		<div class="result-series series{$summISBN}">
			<div class="result-label col-sm-4 col-xs-12">{translate text="Series" isPublicFacing=true} </div>
			<div class="result-value col-sm-8 col-xs-12">
				{assign var=seriesLimit value=$numSeriesToShowBeforeMore+1}
				{include "GroupedWork/series-shared.tpl" summSeries=$summSeries seriesLimit=$seriesLimit}
			</div>
		</div>
	{/if}
{/if}
{if !empty($showPublisher) && $showPublisher}
	{if $alwaysShowSearchResultsMainDetails || $summPublisher}
		<div class="result-publisher result-label col-sm-4 col-xs-12">{translate text="Publisher" isPublicFacing=true} </div>
		<div class="result-publisher result-value col-sm-8 col-xs-12">
			{if !empty($summPublisher)}
				{$summPublisher}
			{elseif $alwaysShowSearchResultsMainDetails}
				{translate text="Not Supplied" isPublicFacing=true}
			{/if}
		</div>
	{/if}
{/if}
{if !empty($showPublicationDate) && $showPublicationDate}
	{if $alwaysShowSearchResultsMainDetails || $summPubDate}
		<div class="result-publication-date result-label col-sm-4 col-xs-12">{translate text="Publication Date" isPublicFacing=true} </div>
		<div class="result-publication-date result-value col-sm-8 col-xs-12">
			{if !empty($summPubDate)}
				{$summPubDate|escape}
			{elseif $alwaysShowSearchResultsMainDetails}
				{translate text="Not Supplied" isPublicFacing=true}
			{/if}
		</div>
	{/if}
{/if}
{if !empty($showPlaceOfPublication) && $showPlaceOfPublication}
	{if $alwaysShowSearchResultsMainDetails || $summPlaceOfPublication}
		<div class="result-place-of-publication result-label col-sm-4 col-xs-12">{translate text="Publication Places" isPublicFacing=true} </div>
		<div class="result-place-of-publication result-value col-sm-8 col-xs-12">
			{if !empty($summPlaceOfPublication)}
				{$summPlaceOfPublication|escape}
			{elseif $alwaysShowSearchResultsMainDetails}
				{translate text="Not Supplied" isPublicFacing=true}
			{/if}
		</div>
	{/if}
{/if}
{if !empty($sortValue) && $displaySortTermValues}
	{if $sortValue == "popularity desc"}
		<div class="result-total-checkouts result-label col-sm-4 col-xs-12">{translate text="Total Checkouts" isPublicFacing=true} </div>
		<div class="result-total-checkouts result-value col-sm-8 col-xs-12">{$totalCheckouts}</div>
	{elseif $sortValue =="days_since_added asc"}
		<div class="result-date-purchased result-label col-sm-4 col-xs-12">{translate text="Date Purchased" isPublicFacing=true} </div>
		<div class="result-date-purchased result-value col-sm-8 col-xs-12">{$datePurchased|date_format:"%m/%d/%Y"}</div>
	{elseif $sortValue =="callnumber_sort"}
		<div class="result-call-number result-label col-sm-4 col-xs-12">{translate text="Call Number" isPublicFacing=true} </div>
		<div class="result-call-number result-value col-sm-8 col-xs-12">{$callNumber}</div>
	{elseif $sortValue =="total_holds desc"}
		<div class="result-number-of-holds result-label col-sm-4 col-xs-12">{translate text="Number of Holds" isPublicFacing=true} </div>
		<div class="result-number-of-holds result-value col-sm-8 col-xs-12">{$totalHolds}</div>
	{/if}
{/if}
{if !empty($showEditions)}
	{if $alwaysShowSearchResultsMainDetails || $summEdition}
		<div class="result-edition result-label col-sm-4 col-xs-12">{translate text="Edition" isPublicFacing=true} </div>
		<div class="result-edition result-value col-sm-8 col-xs-12">
			{if !empty($summEdition)}
				{$summEdition}
			{elseif $alwaysShowSearchResultsMainDetails}
				{translate text="Not Supplied" isPublicFacing=true}
			{/if}
		</div>
	{/if}
{/if}
{if !empty($showAudience)}
	{if $alwaysShowSearchResultsMainDetails || $summAudience}
		{assign var=formats value=$recordDriver->getFormats()}
		{assign var=formats value=array_map('strstr', $formats, array_fill(0, count($formats), "#"))}
		<div class="result-audience result-{join(" result-", array_unique($formats))|replace:"#":""|replace:" ":"-"|lower}">
			<div class="result-label col-sm-4 col-xs-12">{translate text='Audience' isPublicFacing=true} </div>
			<div class="result-value col-sm-8 col-xs-12">
			{if !empty($summAudience)}
				{$summAudience}
			{elseif $alwaysShowSearchResultsMainDetails}
				{translate text="Not Supplied" isPublicFacing=true}
			{/if}
			</div>
		</div>
	{/if}
{/if}
{if !empty($showArInfo) && $summArInfo}
	<div class="result-accelerated-reader result-label col-sm-4 col-xs-12">{translate text='Accelerated Reader' isPublicFacing=true} </div>
	<div class="result-accelerated-reader result-value col-sm-8 col-xs-12">
		{$summArInfo}
	</div>
{/if}
{if !empty($showLexileInfo) && $summLexileInfo}
	<div class="result-lexile result-label col-sm-4 col-xs-12">{translate text='Lexile measure' isPublicFacing=true} </div>
	<div class="result-lexile result-value col-sm-8 col-xs-12">
		{$summLexileInfo}
	</div>
{/if}
{if !empty($showFountasPinnell) && $summFountasPinnell}
	<div class="result-fountas-pinnell result-label col-sm-4 col-xs-12">{translate text='Fountas &amp; Pinnell' isPublicFacing=true} </div>
	<div class="result-fountas-pinnell result-value col-sm-8 col-xs-12">
		{$summFountasPinnell}
	</div>
{/if}
{if !empty($showPhysicalDescriptions)}
	{if $alwaysShowSearchResultsMainDetails || $summPhysicalDesc}
		<div class="result-physical-description result-label col-sm-4 col-xs-12">{translate text='Physical Desc' isPublicFacing=true} </div>
		<div class="result-physical-description result-value col-sm-8 col-xs-12">
			{if !empty($summPhysicalDesc)}
				{$summPhysicalDesc}
			{elseif $alwaysShowSearchResultsMainDetails}
				{translate text="Not Supplied" isPublicFacing=true}
			{/if}
		</div>
	{/if}
{/if}
{if !empty($showLanguages) && $summLanguage}
	<div class="result-language result-label col-sm-4 col-xs-12">{translate text="Language" isPublicFacing=true} </div>
	<div class="result-language result-value col-sm-8 col-xs-12">
		{if is_array($summLanguage)}
			{implode subject=$summLanguage glue=', ' translate=true isPublicFacing=true isMetadata=true}
		{else}
			{translate text=$summLanguage isPublicFacing=true isMetadata=true}
		{/if}
	</div>
{/if}