{strip}
	<div id="main-content" class="col-md-12">
		<h1>{translate text="OverDrive Aspen Data"}</h1>
		<form class="navbar form-inline row">
			<div class="form-group col-xs-12">
				<label for="overDriveId" class="control-label">{translate text="OverDrive ID"}</label>
				<input id ="overDriveId" type="text" name="overDriveId" class="form-control" value="{$overDriveId}">
				<button class="btn btn-primary" type="submit">{translate text=Go}</button>
			</div>
		</form>

		{if !empty($errors)}
			<div class="alert alert-warning">{$errors}</div>
		{/if}
		{if !empty($overDriveProduct)}
			<h2>{$overDriveProduct->title}</h2>
			<div class="row"><div class="col-sm-4">ID</div><div class="col-sm-8">{$overDriveProduct->id}</div></div>
			<div class="row"><div class="col-sm-4">OverDrive ID</div><div class="col-sm-8">{$overDriveProduct->overdriveId}</div></div>
			<div class="row"><div class="col-sm-4">Media Type</div><div class="col-sm-8">{$overDriveProduct->mediaType}</div></div>
			<div class="row"><div class="col-sm-4">Title</div><div class="col-sm-8">{$overDriveProduct->title}</div></div>
			<div class="row"><div class="col-sm-4">Subtitle</div><div class="col-sm-8">{$overDriveProduct->subtitle}</div></div>
			<div class="row"><div class="col-sm-4">Series</div><div class="col-sm-8">{$overDriveProduct->series}</div></div>
			<div class="row"><div class="col-sm-4">Primary Creator Role</div><div class="col-sm-8">{$overDriveProduct->primaryCreatorRole}</div></div>
			<div class="row"><div class="col-sm-4">Primary Creator Name</div><div class="col-sm-8">{$overDriveProduct->primaryCreatorName}</div></div>
			<div class="row"><div class="col-sm-4">Date Added</div><div class="col-sm-8">{$overDriveProduct->dateAdded|date_format:"%D %T"}</div></div>
			<div class="row"><div class="col-sm-4">Date Updated</div><div class="col-sm-8">{$overDriveProduct->dateUpdated|date_format:"%D %T"}</div></div>
			<div class="row"><div class="col-sm-4">Last Metadata Check</div><div class="col-sm-8">{$overDriveProduct->lastMetadataCheck|date_format:"%D %T"}</div></div>
			<div class="row"><div class="col-sm-4">Last Metadata Change</div><div class="col-sm-8">{$overDriveProduct->lastMetadataChange|date_format:"%D %T"}</div></div>
			<div class="row"><div class="col-sm-4">Last Availability Check</div><div class="col-sm-8">{$overDriveProduct->lastAvailabilityCheck|date_format:"%D %T"}</div></div>
			<div class="row"><div class="col-sm-4">Last Availability Change</div><div class="col-sm-8">{$overDriveProduct->lastAvailabilityChange|date_format:"%D %T"}</div></div>
			<div class="row"><div class="col-sm-4">Deleted?</div><div class="col-sm-8">{if $overDriveProduct->deleted}Yes{else}No{/if}</div></div>
			<div class="row"><div class="col-sm-4">Date Deleted</div><div class="col-sm-8">{$overDriveProduct->dateDeleted|date_format:"%D %T"}</div></div>
		{/if}

		{if !empty($overDriveMetadata)}
			<h3>Metadata</h3>
			<div class="row"><div class="col-sm-4">Sort Title</div><div class="col-sm-8">{$overDriveMetadata->sortTitle}</div></div>
			<div class="row"><div class="col-sm-4">Publisher</div><div class="col-sm-8">{$overDriveMetadata->publisher}</div></div>
			<div class="row"><div class="col-sm-4">Publish Date</div><div class="col-sm-8">{$overDriveMetadata->publishDate}</div></div>
			<div class="row"><div class="col-sm-4">Is Public Domain</div><div class="col-sm-8">{$overDriveMetadata->isPublicDomain}</div></div>
			<div class="row"><div class="col-sm-4">Is Public Performance Allowed</div><div class="col-sm-8">{$overDriveMetadata->isPublicPerformanceAllowed}</div></div>
		{/if}

		{if !empty($overDriveAvailabilities)}
			<h3>Availabilities</h3>
			{foreach from=$overDriveAvailabilities item=overDriveAvailability}
				<h4>{$overDriveAvailability->getLibraryName()} {$overDriveAvailability->getSettingName()}</h4>
				<div class="row"><div class="col-sm-4">Available?</div><div class="col-sm-8">{$overDriveAvailability->available}</div></div>
				<div class="row"><div class="col-sm-4">Copies Owned</div><div class="col-sm-8">{$overDriveAvailability->copiesOwned}</div></div>
				<div class="row"><div class="col-sm-4">Copies Available</div><div class="col-sm-8">{$overDriveAvailability->copiesAvailable}</div></div>
				<div class="row"><div class="col-sm-4">Number of Holds</div><div class="col-sm-8">{$overDriveAvailability->numberOfHolds}</div></div>
				<div class="row"><div class="col-sm-4">Shared?</div><div class="col-sm-8">{$overDriveAvailability->shared}</div></div>
			{/foreach}
		{/if}
	</div>
{/strip}