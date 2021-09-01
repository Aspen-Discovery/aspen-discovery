{strip}
	<div id="main-content" class="col-md-12">
		<h1>{translate text="OverDrive Aspen Data" isAdminFacing=true}</h1>
		<form class="navbar form-inline row">
			<div class="form-group col-xs-12">
				<label for="overDriveId" class="control-label">{translate text="OverDrive ID" isAdminFacing=true}</label>
				<input id ="overDriveId" type="text" name="overDriveId" class="form-control" value="{$overDriveId}">
				<button class="btn btn-primary" type="submit">{translate text=Go isAdminFacing=true}</button>
			</div>
		</form>

		{if !empty($errors)}
			<div class="alert alert-warning">{$errors}</div>
		{/if}
		{if !empty($overDriveProduct)}
			<h2>{$overDriveProduct->title}</h2>
			<div class="row"><div class="col-sm-4">{translate text="ID" isAdminFacing=true}</div><div class="col-sm-8">{$overDriveProduct->id}</div></div>
			<div class="row"><div class="col-sm-4">{translate text="OverDrive ID" isAdminFacing=true}</div><div class="col-sm-8">{$overDriveProduct->overdriveId}</div></div>
			<div class="row"><div class="col-sm-4">{translate text="Media Type" isAdminFacing=true}</div><div class="col-sm-8">{$overDriveProduct->mediaType}</div></div>
			<div class="row"><div class="col-sm-4">{translate text="Title" isAdminFacing=true}</div><div class="col-sm-8">{$overDriveProduct->title}</div></div>
			<div class="row"><div class="col-sm-4">{translate text="Subtitle" isAdminFacing=true}</div><div class="col-sm-8">{$overDriveProduct->subtitle}</div></div>
			<div class="row"><div class="col-sm-4">{translate text="Series" isAdminFacing=true}</div><div class="col-sm-8">{$overDriveProduct->series}</div></div>
			<div class="row"><div class="col-sm-4">{translate text="Primary Creator Role" isAdminFacing=true}</div><div class="col-sm-8">{$overDriveProduct->primaryCreatorRole}</div></div>
			<div class="row"><div class="col-sm-4">{translate text="Primary Creator Name" isAdminFacing=true}</div><div class="col-sm-8">{$overDriveProduct->primaryCreatorName}</div></div>
			<div class="row"><div class="col-sm-4">{translate text="Date Added" isAdminFacing=true}</div><div class="col-sm-8">{$overDriveProduct->dateAdded|date_format:"%D %T"}</div></div>
			<div class="row"><div class="col-sm-4">{translate text="Date Updated" isAdminFacing=true}</div><div class="col-sm-8">{$overDriveProduct->dateUpdated|date_format:"%D %T"}</div></div>
			<div class="row"><div class="col-sm-4">{translate text="Last Metadata Check" isAdminFacing=true}</div><div class="col-sm-8">{$overDriveProduct->lastMetadataCheck|date_format:"%D %T"}</div></div>
			<div class="row"><div class="col-sm-4">{translate text="Last Metadata Change" isAdminFacing=true}</div><div class="col-sm-8">{$overDriveProduct->lastMetadataChange|date_format:"%D %T"}</div></div>
			<div class="row"><div class="col-sm-4">{translate text="Last Availability Check" isAdminFacing=true}</div><div class="col-sm-8">{$overDriveProduct->lastAvailabilityCheck|date_format:"%D %T"}</div></div>
			<div class="row"><div class="col-sm-4">{translate text="Last Availability Change" isAdminFacing=true}</div><div class="col-sm-8">{$overDriveProduct->lastAvailabilityChange|date_format:"%D %T"}</div></div>
			<div class="row"><div class="col-sm-4">{translate text="Deleted?" isAdminFacing=true}</div><div class="col-sm-8">{if $overDriveProduct->deleted}{translate text="Yes" isAdminFacing=true}{else}{translate text="No" isAdminFacing=true}{/if}</div></div>
			<div class="row"><div class="col-sm-4">{translate text="Date Deleted" isAdminFacing=true}</div><div class="col-sm-8">{$overDriveProduct->dateDeleted|date_format:"%D %T"}</div></div>
		{/if}

		{if !empty($overDriveMetadata)}
			<h3>Metadata</h3>
			<div class="row"><div class="col-sm-4">{translate text="Sort Title" isAdminFacing=true}</div><div class="col-sm-8">{$overDriveMetadata->sortTitle}</div></div>
			<div class="row"><div class="col-sm-4">{translate text="Publisher" isAdminFacing=true}</div><div class="col-sm-8">{$overDriveMetadata->publisher}</div></div>
			<div class="row"><div class="col-sm-4">{translate text="Publish Date" isAdminFacing=true}</div><div class="col-sm-8">{$overDriveMetadata->publishDate}</div></div>
			<div class="row"><div class="col-sm-4">{translate text="Is Public Domain" isAdminFacing=true}</div><div class="col-sm-8">{$overDriveMetadata->isPublicDomain}</div></div>
			<div class="row"><div class="col-sm-4">{translate text="Is Public Performance Allowed" isAdminFacing=true}</div><div class="col-sm-8">{$overDriveMetadata->isPublicPerformanceAllowed}</div></div>
		{/if}

		{if !empty($overDriveAvailabilities)}
			<h3>Availabilities</h3>
			{foreach from=$overDriveAvailabilities item=overDriveAvailability}
				<h4>{$overDriveAvailability->getLibraryName()} {$overDriveAvailability->getSettingName()}</h4>
				<div class="row"><div class="col-sm-4">{translate text="Available?" isAdminFacing=true}</div><div class="col-sm-8">{$overDriveAvailability->available}</div></div>
				<div class="row"><div class="col-sm-4">{translate text="Copies Owned" isAdminFacing=true}</div><div class="col-sm-8">{$overDriveAvailability->copiesOwned}</div></div>
				<div class="row"><div class="col-sm-4">{translate text="Copies Available" isAdminFacing=true}</div><div class="col-sm-8">{$overDriveAvailability->copiesAvailable}</div></div>
				<div class="row"><div class="col-sm-4">{translate text="Number of Holds" isAdminFacing=true}</div><div class="col-sm-8">{$overDriveAvailability->numberOfHolds}</div></div>
				<div class="row"><div class="col-sm-4">{translate text="Shared?" isAdminFacing=true}</div><div class="col-sm-8">{$overDriveAvailability->shared}</div></div>
			{/foreach}
		{/if}
	</div>
{/strip}