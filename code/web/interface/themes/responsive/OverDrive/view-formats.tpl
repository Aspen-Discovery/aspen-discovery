{strip}
{if count($holdings) > 0}
	{foreach from=$holdings item=overDriveFormat key=index}
	<div id="itemRow{$overDriveFormat->id}" class="eContentHolding">
		<div class="eContentHoldingHeader">
			<div class="row">
				<div class="col-sm-9">
					<span class="eContentHoldingFormat">{$overDriveFormat->name}</span>
					{if $showEContentNotes}
						{$overDriveFormat->notes}
					{/if}
				</div>
			</div>

			<div class="row eContentHoldingUsage">
				<div class="col-sm-12">
					{$overDriveFormat->getFormatNotes()}
				</div>
			</div>
		</div>

		{if $overDriveFormat->size != 0 && strcasecmp($overDriveFormat->size, 'unknown') != 0}
			<div class="eContentHoldingNotes">
				{translate text="Size %1%" 1=$overDriveFormat->fileSize|file_size isPublicFacing=true}
			</div>
		{/if}

	</div>
	{/foreach}
{else}
	{translate text="No Copies Found" isPublicFacing=true}
{/if}

{/strip}