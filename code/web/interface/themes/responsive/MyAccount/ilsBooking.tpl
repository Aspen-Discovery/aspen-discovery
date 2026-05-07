{strip}
	<div class="result row ilsBooking_{$record.id|escape}">
		{* Cover column *}
		{if !empty($showCovers)}
			<div class="col-xs-4 col-sm-3">
				<div class="text-center">
					{if !empty($record.coverUrl)}
						{if !empty($record.linkUrl)}
							<a href="{$record.linkUrl}" aria-hidden="true">
								<img src="{$record.coverUrl}"
									 class="listResultImage img-thumbnail{if $useOriginalCoverUrls} use-original-covers{/if} img-responsive {$coverStyle}"
									 alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}">
							</a>
						{else}
							<img src="{$record.coverUrl}"
								 class="listResultImage img-thumbnail{if $useOriginalCoverUrls} use-original-covers{/if} img-responsive {$coverStyle}"
								 alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}"
								 aria-hidden="true">
						{/if}
					{/if}
				</div>
			</div>
		{/if}

		{* Details column *}
		<div class="{if !empty($showCovers)}col-xs-8 col-sm-9{else}col-xs-12{/if}">
			{* Title *}
			<div class="row">
				<div class="col-xs-12">
					<span class="result-index">{$resultIndex})</span>&nbsp;
					{if !empty($record.linkUrl)}
						<a href="{$record.linkUrl}" class="result-title notranslate">
							{if empty($record.title)}{translate text='Title not available' isPublicFacing=true}{else}{$record.title|removeTrailingPunctuation|truncate:180:"..."}{/if}
						</a>
					{else}
						<span class="result-title notranslate">
							{if empty($record.title)}{translate text='Title not available' isPublicFacing=true}{else}{$record.title|removeTrailingPunctuation|truncate:180:"..."}{/if}
						</span>
					{/if}
				</div>
			</div>

			{* 2-column row: info + actions *}
			<div class="row">
				{* Info column *}
				<div class="resultDetails col-xs-12 col-md-8 col-lg-9">
					<div class="row">
						<div class="result-label col-tn-4">{translate text='Start Date' isPublicFacing=true}</div>
						<div class="col-tn-8 result-value">{$record.startDate|date_format:"%b %d, %Y"}</div>
					</div>
					<div class="row">
						<div class="result-label col-tn-4">{translate text='End Date' isPublicFacing=true}</div>
						<div class="col-tn-8 result-value">{$record.endDate|date_format:"%b %d, %Y"}</div>
					</div>
					{if !empty($record.pickupLibraryId)}
						<div class="row">
							<div class="result-label col-tn-4">{translate text='Pickup Location' isPublicFacing=true}</div>
							<div class="col-tn-8 result-value">{$record.pickupLibraryId|escape}</div>
						</div>
					{/if}
					{if !empty($record.status)}
						<div class="row">
							<div class="result-label col-tn-4">{translate text='Status' isPublicFacing=true}</div>
							<div class="col-tn-8 result-value">{translate text=$record.status isPublicFacing=true}</div>
						</div>
					{/if}
					{if !empty($record.notes)}
						<div class="row">
							<div class="result-label col-tn-4">{translate text='Notes' isPublicFacing=true}</div>
							<div class="col-tn-8 result-value">{$record.notes|escape}</div>
						</div>
					{/if}
					{if !empty($record.staffModified)}
						<div class="row">
							<div class="col-xs-12">
								<span class="label label-warning">{translate text='Updated by staff' isPublicFacing=true}</span>
							</div>
						</div>
					{/if}
				</div>

				{* Actions column *}
				{if $section == 'active'}
					<div class="col-xs-9 col-sm-8 col-md-4 col-lg-3">
						<div class="btn-group btn-group-vertical btn-block">
							<button onclick="return AspenDiscovery.Account.updateBookingForm('{$record.userId|escape}', '{$record.id|escape}');"
									class="btn btn-sm btn-default btn-wrap">{translate text="Update Booking" isPublicFacing=true}</button>
							<button onclick="return AspenDiscovery.Account.confirmCancelBooking('{$record.userId|escape}', '{$record.id|escape}');"
									class="btn btn-sm btn-warning btn-wrap cancelButton">{translate text="Cancel Booking" isPublicFacing=true}</button>
						</div>
					</div>
				{/if}
			</div>
		</div>
	</div>
{/strip}
