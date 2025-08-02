{strip}
	<div class="result row cloudLibraryCheckout_{$record->recordId|escape}">

		{* Cover Column *}
		{if !empty($showCovers)}
			{*<div class="col-xs-4">*}
			<div class="col-xs-3 col-sm-4 col-md-3 checkedOut-covers-column">
				<div class="row">
					<div class="selectTitle hidden-xs col-sm-1">
						{if !isset($record->canRenew) || $record->canRenew == true}
							<input type="checkbox" name="selected[{$record->userId}|{$record->recordId}]" class="titleSelect" id="selected{$record->recordId}">
						{/if}
					</div>
					<div class="{*coverColumn *}text-center col-xs-12 col-sm-10">
						{if $disableCoverArt != 1}{*TODO: should become part of $showCovers *}
							{if $record->getCoverUrl()}
								{if $record->recordId && $record->getLinkUrl()}
									<a href="{$record->getLinkUrl()}" id="descriptionTrigger{$record->recordId|escape:"url"}" aria-hidden="true">
										<img src="{$record->getCoverUrl()}" class="listResultImage img-thumbnail img-responsive {$coverStyle}" alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}">
									</a>
								{else} {* Cover Image but no Record-View link *}
									<img src="{$record->getCoverUrl()}" class="listResultImage img-thumbnail img-responsive {$coverStyle}" alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}" aria-hidden="true">
								{/if}
							{/if}
						{/if}
					</div>
				</div>
			</div>
		{else}
			<div class="col-xs-1">
				{if !isset($record->canRenew) || $record->canRenew == true}
					<input type="checkbox" name="selected[{$record->userId}|{$record->recordId}]" class="titleSelect" id="selected{$record->recordId}">
				{/if}
			</div>
		{/if}

		{* Title Details Column *}
		<div class="{if !empty($showCovers)}col-xs-9 col-sm-8 col-md-9{else}col-xs-11{/if}">
			{* Title *}
			<div class="row">
				<div class="col-xs-12">
					<span class="result-index">{$resultIndex})</span>&nbsp;
					{if $record->getLinkUrl()}
						<a href="{$record->getLinkUrl()}" class="result-title notranslate">
							{if !$record->getTitle()|removeTrailingPunctuation}{translate text='Title not available' isPublicFacing=true}{else}{$record->getTitle()|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}
						</a>
					{else}
						<span class="result-title notranslate">
							{if !$record->getTitle()|removeTrailingPunctuation}{translate text='Title not available' isPublicFacing=true}{else}{$record->getTitle()|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}
						</span>
					{/if}
				</div>
			</div>
			<div class="row">
				<div class="resultDetails col-xs-12 col-md-8 col-lg-9">
					{if strlen($record->getAuthor()) > 0}
						<div class="row">
							<div class="result-label col-sm-12 col-md-5">{translate text='Author' isPublicFacing=true}</div>
							<div class="col-sm-12 col-md-7 result-value">{$record->getAuthor()}</div>
						</div>
					{/if}

					<div class="row">
						<div class="result-label col-sm-12 col-md-5">{translate text='Format' isPublicFacing=true}</div>
						<div class="col-sm-12 col-md-7 result-value">{implode subject=$record->getFormats() translate=true isPublicFacing=true} - cloudLibrary</div>
					</div>

					{if !empty($showRatings) && $record->getGroupedWorkId() && $record->getRatingData()}
						<div class="row">
							<div class="result-label col-sm-12 col-md-5">{translate text='Rating' isPublicFacing=true}&nbsp;</div>
							<div class="col-sm-12 col-md-7 result-value">
								{include file="GroupedWork/title-rating.tpl" id=$record->getGroupedWorkId() summId=$record->getGroupedWorkId() ratingData=$record->getRatingData() showNotInterested=false}
							</div>
						</div>
					{/if}

					{if !empty($hasLinkedUsers)}
						<div class="row">
							<div class="result-label col-sm-12 col-md-5">{translate text='Checked Out To' isPublicFacing=true}</div>
							<div class="col-sm-12 col-md-7 result-value">
								{$record->getUserName()|escape}
							</div>
						</div>
					{/if}

					<div class="row">
						<div class="result-label col-sm-12 col-md-5">{translate text='Expires' isPublicFacing=true}</div>
						<div class="col-sm-12 col-md-7 result-value">{$record->dueDate|date_format}</div>
					</div>
				</div>

				{* Actions for Title *}
				<div class="col-sm-12 col-md-4 col-lg-3">
					<div class="btn-group btn-group-vertical btn-block">
						{assign var=accessOnlineLink value=$record->accessOnlineUrl}
						{if !empty($accessOnlineLink)}
							<a href="{$accessOnlineLink}" target="_blank" class="btn btn-sm btn-action btn-wrap" aria-label="{translate text='Open in cloudLibrary' isPublicFacing=true} ({translate text='opens in new window' isPublicFacing=true})"><i class="fas fa-external-link-alt" role="presentation"></i> {translate text='Open in cloudLibrary' isPublicFacing=true}</a>
						{/if}
						{if array_key_exists('Palace Project', $enabledModules) && !empty($asccessOnlineLink)}
							<a onclick="AspenDiscovery.PalaceProject.showUsageInstructions();" target="_blank" class="btn btn-sm btn-action btn-wrap">{translate text='Access In Palace Project' isPublicFacing=true}</a>
						{/if}
						{if $record->canRenew}
							<a href="#" onclick="return AspenDiscovery.CloudLibrary.renewCheckout('{$record->userId}', '{$record->recordId}');" class="btn btn-sm btn-info btn-wrap">{translate text='Renew Checkout' isPublicFacing=true}</a>
						{/if}
						<a href="#" onclick="return AspenDiscovery.CloudLibrary.returnCheckout('{$record->userId}', '{$record->recordId}');" class="btn btn-sm btn-warning btn-wrap">{translate text='Return Now' isPublicFacing=true}</a>
					</div>
					{if !empty($showWhileYouWait)}
						<div class="btn-group btn-group-vertical btn-block">
							{if !empty($record->getGroupedWorkId())}
								<button onclick="return AspenDiscovery.GroupedWork.getYouMightAlsoLike('{$record->getGroupedWorkId()}', '{$record->getPrimaryFormat()}');" class="btn btn-sm btn-default btn-wrap">{translate text="You Might Also Like" isPublicFacing=true}</button>
							{/if}
						</div>
					{/if}
				</div>
			</div>
		</div>
	</div>
{/strip}
