{strip}
	<div id="hoopla_{$record.recordId|escape}" class="result row">

		{* Cover Column *}
		{if $showCovers}
			{*<div class="col-xs-4">*}
			<div class="col-xs-3 col-sm-4 col-md-3 checkedOut-covers-column">
				<div class="row">
					<div class="selectTitle hidden-xs col-sm-1">
						&nbsp;{* Can't renew hoopla titles*}
					</div>
					<div class="{*coverColumn *}text-center col-xs-12 col-sm-10">
						{if $disableCoverArt != 1}{*TODO: should become part of $showCovers *}
							{if $record.coverUrl}
								{if $record.recordId && $record.linkUrl}
									<a href="{$record.linkUrl}" id="descriptionTrigger{$record.recordId|escape:"url"}">
										<img src="{$record.coverUrl}" class="listResultImage img-thumbnail img-responsive" alt="{translate text='Cover Image'}">
									</a>
								{else} {* Cover Image but no Record-View link *}
									<img src="{$record.coverUrl}" class="listResultImage img-thumbnail img-responsive" alt="{translate text='Cover Image'}">
								{/if}
							{/if}
						{/if}
					</div>
				</div>
			</div>
		{else}
			<div class="col-xs-1">
				&nbsp;{* Can't renew hoopla titles*}
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
					{*				{if $record.recordId != -1}
									<a href="{$record.recordUrl}" class="result-title notranslate">{/if}
										{$record.title}{if $record.recordId == -1}OverDrive Record {$record.overDriveId}{/if}{if $record.recordId != -1}
									</a>
									{/if}*}
				</div>
			</div>
			<div class="row">
				<div class="resultDetails col-xs-12 col-md-9">
					{if strlen($record.author) > 0}
						<div class="row">
							<div class="result-label col-tn-4 col-lg-3">{translate text='Author'}</div>
							<div class="result-value col-tn-8 col-lg-9">{$record.author}</div>
						</div>
					{/if}

					{if $record.checkoutDate}
						<div class="row">
							<div class="result-label col-tn-4 col-lg-3">{translate text='Checked Out'}</div>
							<div class="result-value col-tn-8 col-lg-9">{$record.checkoutDate|date_format}</div>
						</div>
					{/if}

					<div class="row">
						<div class="result-label col-tn-4 col-lg-3">{translate text='Format'}</div>
						<div class="result-value col-tn-8 col-lg-9">{$record.format} - Hoopla</div>
					</div>

					{if $showRatings && $record.groupedWorkId && $record.ratingData}
						<div class="row">
							<div class="result-label col-tn-4 col-lg-3">Rating&nbsp;</div>
							<div class="result-value col-tn-8 col-lg-9">
								{include file="GroupedWork/title-rating.tpl" ratingClass="" id=$record.groupedWorkId ratingData=$record.ratingData showNotInterested=false}
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

					<div class="row">
						<div class="result-label col-tn-4 col-lg-3">{translate text='Expires'}</div>
						<div class="result-value col-tn-8 col-lg-9">{$record.dueDate|date_format}</div>
					</div>


					{if isset($record.borrowsRemaining)}
						<div class="row">
							<div class="col-tn-12">You can borrow <strong>{$record.borrowsRemaining}</strong> more Hoopla title{if $record.borrowsRemaining !=1}s{/if} this month.</div>
							{*<div class="result-label col-tn-4 col-lg-3">{translate text='Expires'}</div>*}
							{*<div class="result-value col-tn-8 col-lg-9">{$record.dueDate|date_format}</div>*}
						</div>
					{/if}
				</div>

				{* Actions for Title *}
				<div class="col-xs-9 col-sm-8 col-md-4 col-lg-3">
					<div class="btn-group btn-group-vertical btn-block">
						{if $record.hooplaUrl}
						<a href="{$record.hooplaUrl}" target="_blank" {*onclick="alert('Dummy button');return false"*} class="btn btn-sm btn-primary">Access&nbsp;Online</a>
						{/if}
						<a href="#" onclick="return VuFind.Hoopla.returnHooplaTitle('{$record.userId}', '{$record.hooplaId}');" class="btn btn-sm btn-warning">Return&nbsp;Now</a>

					</div>
				</div>
			</div>
		</div>
	</div>
{/strip}