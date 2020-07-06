{strip}
	<div class="result row rbdigitalMagazineCheckout_{$record.recordId|escape}">

		{* Cover Column *}
		{if $showCovers}
			{*<div class="col-xs-4">*}
			<div class="col-xs-3 col-sm-4 col-md-3 checkedOut-covers-column">
				<div class="row">
					<div class="selectTitle hidden-xs col-sm-1">

					</div>
					<div class="{*coverColumn *}text-center col-xs-12 col-sm-10">
						{if $disableCoverArt != 1}{*TODO: should become part of $showCovers *}
							{if $record.coverUrl}
								{if $record.recordId && $record.linkUrl}
									<a href="{$record.linkUrl}" id="descriptionTrigger{$record.recordId|escape:"url"}">
										<img src="{$record.coverUrl}" class="listResultImage img-thumbnail img-responsive" alt="{translate text='Cover Image' inAttribute=true}">
									</a>
								{else} {* Cover Image but no Record-View link *}
									<img src="{$record.coverUrl}" class="listResultImage img-thumbnail img-responsive" alt="{translate text='Cover Image' inAttribute=true}">
								{/if}
							{/if}
						{/if}
					</div>
				</div>
			</div>
		{else}
			<div class="col-xs-1">

			</div>
		{/if}

		{* Title Details Column *}
		<div class="{if $showCovers}col-xs-9 col-sm-8 col-md-9{else}col-xs-11{/if}">
			{* Title *}
			<div class="row">
				<div class="col-xs-12">
					<span class="result-index">{$resultIndex})</span>&nbsp;
					{if $record.linkUrl}
						<a href="{$record.linkUrl}" class="result-title notranslate">
							{if !$record.title|removeTrailingPunctuation}{translate text='Title not available'}{else}{$record.title|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}
						</a>
					{else}
						<span class="result-title notranslate">
							{if !$record.title|removeTrailingPunctuation}{translate text='Title not available'}{else}{$record.title|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}
						</span>
					{/if}
				</div>
			</div>
			<div class="row">
				<div class="resultDetails col-xs-12 col-md-9">
                    {if !empty($record.volume)}
						<div class="row">
							<div class="result-label col-tn-4 col-lg-3">{translate text='Volume'}</div>
							<div class="result-value col-tn-8 col-lg-9">{$record.volume|escape}</div>
						</div>
                    {/if}

					{if strlen($record.publisher) > 0}
						<div class="row">
							<div class="result-label col-tn-4 col-lg-3">{translate text='Publisher'}</div>
							<div class="result-value col-tn-8 col-lg-9">{$record.publisher}</div>
						</div>
					{/if}

					<div class="row">
						<div class="result-label col-tn-4 col-lg-3">{translate text='Format'}</div>
						<div class="result-value col-tn-8 col-lg-9">{$record.format|translate} - RBdigital</div>
					</div>

					{if $showRatings && $record.groupedWorkId && $record.ratingData}
						<div class="row">
							<div class="result-label col-tn-4 col-lg-3">{translate text='Rating'}&nbsp;</div>
							<div class="result-value col-tn-8 col-lg-9">
								{include file="GroupedWork/title-rating.tpl" id=$record.groupedWorkId ratingData=$record.ratingData showNotInterested=false}
							</div>
						</div>
					{/if}

					{if $hasLinkedUsers}
						<div class="row">
							<div class="result-label col-tn-4 col-lg-3">{translate text='Checked Out To'}</div>
							<div class="result-value col-tn-8 col-lg-9">
								{$record.user}
							</div>
						</div>
					{/if}
				</div>

				{* Actions for Title *}
				<div class="col-xs-9 col-sm-8 col-md-4 col-lg-3">
					<div class="btn-group btn-group-vertical btn-block">
						<a href="{$record.accessOnlineUrl}" target="_blank" class="btn btn-sm btn-primary">{translate text='Open in RBdigital'}</a>
						<a href="#" onclick="return AspenDiscovery.RBdigital.returnMagazine('{$record.userId}', '{$record.recordId}');" class="btn btn-sm btn-warning">{translate text='Return&nbsp;Now'}</a>
					</div>
					{if $showWhileYouWait}
						<div class="btn-group btn-group-vertical btn-block">
							{if !empty($record.groupedWorkId)}
								<button onclick="return AspenDiscovery.GroupedWork.getYouMightAlsoLike('{$record.groupedWorkId}');" class="btn btn-sm btn-default btn-wrap">{translate text="You Might Also Like"}</button>
							{/if}
						</div>
					{/if}
				</div>
			</div>
		</div>
	</div>
{/strip}