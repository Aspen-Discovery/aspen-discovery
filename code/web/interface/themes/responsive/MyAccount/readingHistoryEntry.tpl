<div class="row result">
	{* Cover Column *}
	{if $showCovers}
		<div class="col-tn-12 col-sm-3">
			<div class="row">
				<div class="col-xs-12 col-sm-1">
					<input type="checkbox" name="selected[{$record.permanentId|escape:"url"}]" class="titleSelect" value="rsh{$record.permanentId}" aria-label="Select {$record.title|removeTrailingPunctuation|truncate:180:"..."|escape}">
				</div>
				<div class="col-xs-12 col-sm-10" style="text-align: center">
					{if $record.coverUrl}
						{if $record.recordId && $record.linkUrl}
							<a href="{$record.linkUrl}" id="descriptionTrigger{$record.recordId|escape:"url"}">
								<img src="{$record.coverUrl}" class="listResultImage img-thumbnail img-responsive" alt="{translate text='Cover Image' inAttribute=true}">
							</a>
						{else} {* Cover Image but no Record-View link *}
							<img src="{$record.coverUrl}" class="listResultImage img-thumbnail img-responsive" alt="{translate text='Cover Image' inAttribute=true}">
						{/if}
					{/if}
				</div>
			</div>
		</div>
	{else}
		<div class="col-tn-1">
			{if !$record.checkedOut}
				<input type="checkbox" name="selected[{$record.permanentId|escape:"url"}]" class="titleSelect" value="rsh{$record.itemindex}" id="rsh{$record.itemindex}" aria-label="Select {$record.title|removeTrailingPunctuation|truncate:180:"..."|escape}">
			{/if}
		</div>
	{/if}

	{* Title Details Column *}
	<div class="{if $showCovers}col-tn-12 col-sm-9{else}col-tn-11{/if}">
		<div class="row">
			<div class="col-xs-12 result-title notranslate">
				{$record.index})&nbsp;
				{if $record.linkUrl}
					<a href="{$record.linkUrl}" class="title">{if !$record.title|removeTrailingPunctuation}{translate text='Title not available'}{else}{$record.title|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}</a>
				{else}
					{if !$record.title|removeTrailingPunctuation}{translate text='Title not available'}{else}{$record.title|removeTrailingPunctuation}{/if}
				{/if}
				{if !empty($record.title2)}
					<div class="searchResultSectionInfo">
						{$record.title2|removeTrailingPunctuation|truncate:180:"..."|highlight}
					</div>
				{/if}
			</div>
		</div>

		{if $record.author}
			<div class="row">
				<div class="result-label col-tn-3">{translate text='Author'}</div>
				<div class="result-value col-tn-9">
					{if is_array($record.author)}
						{foreach from=$summAuthor item=author}
							<a href='{$path}/Author/Home?author="{$author|escape:"url"}"'>{$author|highlight}</a>
						{/foreach}
					{else}
						<a href='{$path}/Author/Home?author="{$record.author|escape:"url"}"'>{$record.author|highlight}</a>
					{/if}
				</div>
			</div>
		{/if}

		<div class="row">
			<div class="result-label col-tn-3">{translate text='Format'}</div>
			<div class="result-value col-tn-9">
				{if is_array($record.format)}
					{implode subject=$record.format glue=", " translate=true}
				{else}
					{$record.format|translate}
				{/if}
			</div>
		</div>

		<div class="row">
			<div class="result-label col-tn-3">{translate text='Last Used'}</div>
			<div class="result-value col-tn-9">
				{if $record.checkedOut}
					{translate text="In Use"}
				{else}
					{if is_numeric($record.checkout)}
						{$record.checkout|date_format:"%b %Y"}
					{else}
						{$record.checkout|escape}
					{/if}
				{/if}
			</div>
		</div>

		<div class="row">
			<div class="result-label col-tn-3">{translate text='Times Used'}</div>
			<div class="result-value col-tn-9">
				{$record.timesUsed}
			</div>
		</div>

		{if $showRatings == 1}
			{if !empty($record.permanentId) && $record.permanentId != -1 && $record.ratingData}
				<div class="row">
					<div class="result-label col-tn-3">Rating&nbsp;</div>
					<div class="result-value col-tn-9">
						{include file="GroupedWork/title-rating.tpl" ratingClass="" id=$record.permanentId ratingData=$record.ratingData showNotInterested=false}
					</div>
				</div>
			{/if}
		{/if}

		{if !empty($record.permanentId) && $record.permanentId != -1}
			<div class="row">
				<div class="col-xs-12">
					{include file='GroupedWork/result-tools-horizontal.tpl' recordDriver=$record.recordDriver id=$record.permanentId shortId=$record.permanentId ratingData=$record.ratingData recordUrl=$record.linkUrl showMoreInfo=true}
					{* TODO: id & shortId shouldn't be needed to be specified here, otherwise need to note when used.
						summTitle only used by cart div, which is disabled as of now. 12-28-2015 plb *}
				</div>
			</div>
		{/if}
	</div>

</div>