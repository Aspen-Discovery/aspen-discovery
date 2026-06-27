{strip}
	{* Overall hold *}
	<div class="result row ilsHold_{$record->sourceId|escapeCSS}_{$record->cancelId|escapeCSS}">
		{* Cover column *}
		{if !empty($showCovers)}
			<div class="col-xs-4 col-sm-3">
				<div class="text-center">
					{if !empty($record->getCoverUrl())}
						{if !empty($record->getLinkUrl())}
							<a href="{$record->getLinkUrl()}" id="descriptionTrigger{$record->recordId|escape:"url"}" aria-hidden="true">
								<img src="{$record->getCoverUrl()}"
									 class="listResultImage img-thumbnail{if $useOriginalCoverUrls} use-original-covers{/if} img-responsive {$coverStyle}"
									 alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}">
							</a>
						{else} {* Cover Image but no Record-View link *}
							<img src="{$record->getCoverUrl()}"
								 class="listResultImage img-thumbnail{if $useOriginalCoverUrls} use-original-covers{/if} img-responsive {$coverStyle}"
								 alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}"
								 aria-hidden="true">
						{/if}
					{/if}
				</div>
			</div>
		{/if}

		{* Details Column*}
		<div class="{if !empty($showCovers)}col-xs-8 col-sm-9{else}col-xs-12{/if}">
			{* Title *}
			<div class="row">
				<div class="col-xs-12">
					<span class="result-index">{$resultIndex})</span>&nbsp;
					{if $record->getLinkUrl()}
						<a href="{$record->getLinkUrl()}" class="result-title notranslate">
							{if !$record->getTitle()|removeTrailingPunctuation} {translate text='Title not available' isPublicFacing=true}{else}{$record->getTitle()|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}
						</a>
					{else}
						<span class="result-title notranslate">
							{if !$record->getTitle()|removeTrailingPunctuation} {translate text='Title not available' isPublicFacing=true}{else}{$record->getTitle()|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}
						</span>
					{/if}
					{if !empty($record->title2)}
						<div class="searchResultSectionInfo">
							{$record->title2|removeTrailingPunctuation|truncate:180:"..."|highlight}
						</div>
					{/if}
				</div>
			</div>

			<div class="row">
				<div class="resultDetails col-xs-12">
					{if !empty($record->volume)}
						<div class="row">
							<div class="result-label col-tn-4 col-xs-4">{translate text='Volume' isPublicFacing=true}</div>
							<div class="col-tn-8 col-xs-8 result-value">
								{$record->volume|format_float_with_min_decimals}
							</div>
						</div>
					{/if}

					{if !empty($record->getAuthor())}
						<div class="row">
							<div class="result-label col-tn-4 col-xs-4">{translate text='Author' isPublicFacing=true}</div>
							<div class="col-tn-8 col-xs-8 result-value">
								{if is_array($record->getAuthor())}
									{foreach from=$record->getAuthor() item=author}
										<a href='/Search/Results?lookfor={$author|escape:"url"}&searchIndex=Author'>{$author|highlight}</a>
									{/foreach}
								{else}
									<a href='/Search/Results?lookfor={$record->getAuthor()|escape:"url"}&searchIndex=Author'>{$record->getAuthor()|highlight}</a>
								{/if}
							</div>
						</div>
					{/if}

					{if !empty($record->getFormats())}
						<div class="row">
							<div class="result-label col-tn-4 col-xs-4">{translate text='Format' isPublicFacing=true}</div>
							<div class="col-tn-8 col-xs-8 result-value">
								{implode subject=$record->getFormats() glue=", " translate=true isPublicFacing=true}
							</div>
						</div>
					{/if}

					<div class="row">
						<div class="result-label col-tn-4 col-xs-4">{translate text='Pickup Location' isPublicFacing=true}</div>
						<div class="col-tn-8 col-xs-8 result-value">
							{$record->pickupLocationName|escape}
						</div>
					</div>

					{if $record->expirationDate}
						<div class="row">
							<div class="result-label col-tn-4 col-xs-4">{translate text='Pickup By' isPublicFacing=true}</div>
							<div class="col-tn-8 col-xs-8 result-value">
								<strong>{$record->expirationDate|date_format:"%b %d, %Y"}</strong>
							</div>
						</div>
					{/if}
				</div>
			</div>
		</div>
	</div>
{/strip}
