{strip}
	<div class="result row overdrive_checkout_{$record->recordId|escape}">

		{* Cover Column *}
		{if $showCovers}
			{*<div class="col-xs-4">*}
			<div class="col-xs-3 col-sm-4 col-md-3 checkedOut-covers-column">
				<div class="row">
					<div class="selectTitle hidden-xs col-sm-1">
						&nbsp;{* Can't renew overdrive titles*}
					</div>
					<div class="{*coverColumn *}text-center col-xs-12 col-sm-10">
						{if $disableCoverArt != 1}{*TODO: should become part of $showCovers *}
							{if $record->getCoverUrl()}
								{if $record->recordId && $record->getLinkUrl()}
									<a href="{$record->getLinkUrl()}" id="descriptionTrigger{$record->recordId|escape:"url"}" aria-hidden="true">
										<img src="{$record->getCoverUrl()}" class="listResultImage img-thumbnail img-responsive" alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}">
									</a>
								{else} {* Cover Image but no Record-View link *}
									<img src="{$record->getCoverUrl()}" class="listResultImage img-thumbnail img-responsive" alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}" aria-hidden="true">
								{/if}
							{/if}
						{/if}
					</div>
				</div>
			</div>
		{else}
			<div class="col-xs-1">
				&nbsp;{* Can't renew overdrive titles*}
			</div>
		{/if}

		{* Title Details Column *}
		<div class="{if $showCovers}col-xs-9 col-sm-8 col-md-9{else}col-xs-11{/if}">
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
				</div>
			</div>
			<div class="row">
				<div class="resultDetails col-xs-12 col-md-9">
					{if strlen($record->getAuthor()) > 0}
						<div class="row">
							<div class="result-label col-tn-4 col-lg-3"> {translate text='Author' isPublicFacing=true}</div>
							<div class="result-value col-tn-8 col-lg-9">{$record->getAuthor()}</div>
						</div>
					{/if}

					{if $record->checkoutDate}
						<div class="row">
							<div class="result-label col-tn-4 col-lg-3">{translate text='Checked Out' isPublicFacing=true}</div>
							<div class="result-value col-tn-8 col-lg-9">{$record->checkoutDate|date_format}</div>
						</div>
					{/if}

					<div class="row">
						<div class="result-label col-tn-4 col-lg-3">{translate text='Format' isPublicFacing=true}</div>
						<div class="result-value col-tn-8 col-lg-9">{implode subject=$record->getFormats() translate=true isPublicFacing=true} - Overdrive</div>
					</div>

					{if $showRatings && $record->getGroupedWorkId() && $record->getRatingData()}
						<div class="row">
							<div class="result-label col-tn-4 col-lg-3">{translate text='Rating' isPublicFacing=true}&nbsp;</div>
							<div class="result-value col-tn-8 col-lg-9">
								{include file="GroupedWork/title-rating.tpl" id=$record->getGroupedWorkId() ratingData=$record->getRatingData() showNotInterested=false}
							</div>
						</div>
					{/if}

					{if $hasLinkedUsers}
						<div class="row">
							<div class="result-label col-tn-4 col-lg-3">{translate text='Checked Out To' isPublicFacing=true}</div>
							<div class="result-value col-tn-8 col-lg-9">
								{$record->getUserName()}
							</div>
						</div>
					{/if}

					<div class="row">
						<div class="result-label col-tn-4 col-lg-3">{translate text='Expires' isPublicFacing=true}</div>
						<div class="result-value col-tn-8 col-lg-9">{$record->dueDate|date_format}</div>
					</div>

					{if $record->allowDownload}
						<div class="row econtent-download-row">
							<div class="result-label col-md-4 col-lg-3">{translate text='Download' isPublicFacing=true}</div>
							<div class="result-value col-md-8 col-lg-9">
								{if $record->formatSelected}
									{translate text="You downloaded the <strong>%1%</strong> format of this title." 1=$record->selectedFormatName isPublicFacing=true}
								{else}
									<div class="form-inline">
										<label for="downloadFormat_{$record->recordId}">{translate text="Select one format to download." isPublicFacing=true}</label>
										<br>
										<select name="downloadFormat_{$record->recordId}" id="downloadFormat_{$record->recordId}_{$smarty.now}" class="input-sm form-control">
											<option value="-1">{translate text="Select a Format" isPublicFacing=true}</option>
											{foreach from=$record->formats item=format}
												<option value="{$format.id}">{translate text=$format.name isPublicFacing=true}</option>
											{/foreach}
										</select>
										<a href="#" onclick="AspenDiscovery.OverDrive.selectOverDriveDownloadFormat('{$record->userId}', '{$record->recordId}', '{$smarty.now}')" class="btn btn-sm btn-primary">{translate text="Download" isPublicFacing=true}</a>
									</div>
								{/if}
							</div>
						</div>
					{/if}
				</div>

				{* Actions for Title *}
				<div class="col-xs-9 col-sm-8 col-md-4 col-lg-3">
					<div class="btn-group btn-group-vertical btn-block">
						{if $record->overdriveRead}
							<a href="#" onclick="return AspenDiscovery.OverDrive.followOverDriveDownloadLink('{$record->userId}', '{$record->recordId}', 'ebook-overdrive')" class="btn btn-sm btn-action"><i class="fas fa-external-link-alt"></i> {translate text="Read Online" isPublicFacing=true}</a>
						{/if}
						{if $record->overdriveListen}
							<a href="#" onclick="return AspenDiscovery.OverDrive.followOverDriveDownloadLink('{$record->userId}', '{$record->recordId}', 'audiobook-overdrive')" class="btn btn-sm btn-action"><i class="fas fa-external-link-alt"></i> {translate text="Listen Online" isPublicFacing=true}</a>
						{/if}
						{if !empty($record->overdriveVideo)}
							<a href="#" onclick="return AspenDiscovery.OverDrive.followOverDriveDownloadLink('{$record->userId}', '{$record->recordId}', 'video-streaming')" class="btn btn-sm btn-action"><i class="fas fa-external-link-alt"></i> {translate text="Watch Online" isPublicFacing=true}</a>
						{/if}
						{if $record->overdriveMagazine}
							<a href="#" onclick="return AspenDiscovery.OverDrive.followOverDriveDownloadLink('{$record->userId}', '{$record->recordId}', 'magazine-overdrive')" class="btn btn-sm btn-action"><i class="fas fa-external-link-alt"></i> {translate text="Read Online" isPublicFacing=true}</a>
						{/if}
						{if $record->formatSelected && empty($record->overdriveVideo)}
							<a href="#" onclick="return AspenDiscovery.OverDrive.followOverDriveDownloadLink('{$record->userId}', '{$record->recordId}', '{$record->selectedFormatValue}')" class="btn btn-sm btn-action">{translate text="Download Again" isPublicFacing=true}</a>
						{/if}
						{if !empty($record->supplementalMaterials)}
							{foreach from=$record->supplementalMaterials item=supplement}
								<a href="#" onclick="return AspenDiscovery.OverDrive.followOverDriveDownloadLink('{$record->userId}', '{$supplement->recordId}', '{$supplement->selectedFormatValue}')" class="btn btn-sm btn-default btn-wrap">{translate text="Download Supplemental %1%" 1=$supplement->selectedFormatName isPublicFacing=true}</a>
							{/foreach}
						{/if}
						{if $record->canRenew}
							<a href="#" onclick="return AspenDiscovery.OverDrive.renewCheckout('{$record->userId}', '{$record->recordId}');" class="btn btn-sm btn-info">{translate text='Renew Checkout' isPublicFacing=true}</a>
						{/if}
						{if $record->canReturnEarly}
							<a href="#" onclick="return AspenDiscovery.OverDrive.returnCheckout('{$record->userId}', '{$record->recordId}');" class="btn btn-sm btn-warning">{translate text="Return Now" isPublicFacing=true}</a>
						{/if}
					</div>
					{if $showWhileYouWait}
						<div class="btn-group btn-group-vertical btn-block">
							{if !empty($record->getGroupedWorkId())}
								<button onclick="return AspenDiscovery.GroupedWork.getYouMightAlsoLike('{$record->getGroupedWorkId()}');" class="btn btn-sm btn-default btn-wrap">{translate text="You Might Also Like" isPublicFacing=true}</button>
							{/if}
						</div>
					{/if}
				</div>
			</div>
		</div>
	</div>
{/strip}