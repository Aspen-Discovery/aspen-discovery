{strip}
	<div id="main-content" class="col-md-12">
		<h1>{translate text="%1% Aspen Data" 1=$readerName isAdminFacing=true}</h1>
		<form class="navbar form-inline row">
			<div class="form-group col-xs-12">
				<label for="overDriveId" class="control-label">{translate text="%1% ID" 1=$readerName isAdminFacing=true}</label>
				<input id ="overDriveId" type="text" name="overDriveId" class="form-control" value="{$overDriveId}">
				<button class="btn btn-primary" type="submit">{translate text=Go isAdminFacing=true}</button>
			</div>
		</form>
		{if !empty($canForceReindex)}
			<form method="post" action="/OverDrive/AspenData" class="form">
				<input type="hidden" name="overDriveId" value="{$overDriveId}">
				<div class="form-group">
					<label for="recordsToReindex" class="control-label">{translate text="Records to Reindex" isAdminFacing=true}</label>
					<textarea id="recordsToReindex" name="recordsToReindex" class="form-control" rows="3"></textarea>
					<small class="form-text text-muted">{translate text="Enter %1% Reserve IDs or Title IDs, separated by new lines or commas." 1=$readerName isAdminFacing=true}</small>
				</div>
				<button class="btn btn-primary" type="submit">{translate text="Reindex" isAdminFacing=true}</button>
			</form>
		{/if}
		{if !empty($titleIdsNotFound)}
			<div class="alert alert-warning">{translate text="These Title IDs were not found in Aspen, please enter the Reserve ID instead" isAdminFacing=true}: {', '|implode:$titleIdsNotFound}</div>
		{/if}
		{if !empty($alreadyInQueueIds)}
			<div class="alert alert-info">{translate text="Already waiting to be reindexed" isAdminFacing=true}: {', '|implode:$alreadyInQueueIds}</div>
		{/if}
		{if !empty($numQueued)}
			<div class="alert alert-success">{translate text="%1% record(s) added to the queue and will be reindexed within a few minutes." 1=$numQueued isAdminFacing=true}</div>
		{/if}

		{if !empty($errors)}
			<div class="alert alert-warning">{$errors}</div>
		{/if}
		{if !empty($overDriveProduct)}
			<h2>{$overDriveProduct->title}</h2>
			<div class="row"><div class="col-sm-4">{translate text="ID" isAdminFacing=true}</div><div class="col-sm-8">{$overDriveProduct->id}</div></div>
			<div class="row"><div class="col-sm-4">{translate text="%1% ID" 1=$readerName isAdminFacing=true}</div><div class="col-sm-8">{$overDriveProduct->overdriveId}</div></div>
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
				<h4>{$overDriveAvailability->getLibraryName()} - {$overDriveAvailability->getSettingDescription()}</h4>
				<div class="row"><div class="col-sm-4">{translate text="Available?" isAdminFacing=true}</div><div class="col-sm-8">{$overDriveAvailability->available}</div></div>
				<div class="row"><div class="col-sm-4">{translate text="Copies Owned" isAdminFacing=true}</div><div class="col-sm-8">{$overDriveAvailability->copiesOwned}</div></div>
				<div class="row"><div class="col-sm-4">{translate text="Copies Available" isAdminFacing=true}</div><div class="col-sm-8">{$overDriveAvailability->copiesAvailable}</div></div>
				<div class="row"><div class="col-sm-4">{translate text="Number of Holds" isAdminFacing=true}</div><div class="col-sm-8">{$overDriveAvailability->numberOfHolds}</div></div>
				<div class="row"><div class="col-sm-4">{translate text="Shared?" isAdminFacing=true}</div><div class="col-sm-8">{$overDriveAvailability->shared}</div></div>
			{/foreach}
		{/if}

		{if !empty($groupedWorkId)}
			<h3>Grouped Work Information</h3>
			<div class="row"><div class="col-sm-4">{translate text="Internal ID" isAdminFacing=true}</div><div class="col-sm-8">{$groupedWorkId}</div></div>
			<div class="row"><div class="col-sm-4">{translate text="Permanent ID" isAdminFacing=true}</div><div class="col-sm-8">{$groupedWork->permanent_id}</div></div>
			<div class="row"><div class="col-sm-4">{translate text="Grouping Category" isAdminFacing=true}</div><div class="col-sm-8">{$groupedWork->grouping_category}</div></div>
		{/if}

		{if !empty($aspenRecords)}
			<h3>Aspen Records</h3>
			<table class="table table-condensed table-responsive table-striped">
				<thead>
					<tr>
						<th>id</th>
						<th>source</th>
						<th>subSource</th>
						<th>format</th>
						<th>formatCategory</th>
						<th>edition</th>
						<th>publisher</th>
						<th>placeOfPublication</th>
						<th>publicationDate</th>
						<th>language</th>
					</tr>
				</thead>
				<tbody>
					{foreach from=$aspenRecords item=aspenRecord}
						<tr>
							<td>{$aspenRecord.id}</td>
							<td>{$aspenRecord.source}</td>
							<td>{$aspenRecord.subSource}</td>
							<td>{$aspenRecord.format}</td>
							<td>{$aspenRecord.formatCategory}</td>
							<td>{$aspenRecord.edition}</td>
							<td>{$aspenRecord.publisher}</td>
							<td>{$aspenRecord.placeOfPublication}</td>
							<td>{$aspenRecord.publicationDate}</td>
							<td>{$aspenRecord.language}</td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		{/if}

		{if !empty($aspenVariations)}
			<h3>Aspen Variations</h3>
			<table class="table table-condensed table-responsive table-striped">
				<thead>
					<tr>
						<th>id</th>
						<th>language</th>
						<th>eContentSource</th>
						<th>format</th>
						<th>formatCategory</th>
					</tr>
				</thead>
				<tbody>
					{foreach from=$aspenVariations item=aspenVariation}
						<tr>
							<td>{$aspenVariation.id}</td>
							<td>{$aspenVariation.language}</td>
							<td>{$aspenVariation.eContentSource}</td>
							<td>{$aspenVariation.format}</td>
							<td>{$aspenVariation.formatCategory}</td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		{/if}

		{if !empty($aspenItems)}
			<h3>Aspen Items</h3>
			<table class="table table-condensed table-responsive table-striped">
				<thead>
					<tr>
						<th>id</th>
						<th>groupedWorkRecordId</th>
						<th>groupedWorkVariationId</th>
						<th>itemId</th>
						<th>shelfLocation</th>
						<th>callNumber</th>
						<th>numCopies</th>
						<th>isOrderItem</th>
						<th>status</th>
						<th>available</th>
						<th>holdable</th>
					</tr>
				</thead>
				<tbody>
					{foreach from=$aspenItems item=aspenItem}
						<tr>
							<td>{$aspenItem.id}</td>
							<td>{$aspenItem.groupedWorkRecordId}</td>
							<td>{$aspenItem.groupedWorkVariationId}</td>
							<td>{$aspenItem.itemId}</td>
							<td>{$aspenItem.shelfLocation}</td>
							<td>{$aspenItem.callNumber}</td>
							<td>{$aspenItem.numCopies}</td>
							<td>{$aspenItem.isOrderItem}</td>
							<td>{$aspenItem.status}</td>
							<td>{$aspenItem.available}</td>
							<td>{$aspenItem.holdable}</td>
						</tr>
					{/foreach}
				</tbody>
			</table>
			<p>
				<em>For OverDrive Titles, the item id is the overdrive id:setting id:format</em>
			</p>
		{/if}
	</div>
{/strip}