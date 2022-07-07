{strip}
<button onclick="return AspenDiscovery.GroupedWork.reloadCover('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Reload Cover" isAdminFacing=true}</button>
{if $loggedIn && in_array('Upload Covers', $userPermissions)}
	<button onclick="return AspenDiscovery.GroupedWork.getUploadCoverForm('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Upload Cover from Computer" isAdminFacing=true}</button>
	<button onclick="return AspenDiscovery.GroupedWork.getUploadCoverFormByURL('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Upload Cover by URL" isAdminFacing=true}</button>
	<button onclick="return AspenDiscovery.GroupedWork.clearUploadedCover('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Clear Uploaded Cover" isAdminFacing=true}</button>
{/if}
<button onclick="return AspenDiscovery.GroupedWork.reloadEnrichment('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Reload Enrichment" isAdminFacing=true}</button>
{if $loggedIn && in_array('Force Reindexing of Records', $userPermissions)}
	<button onclick="return AspenDiscovery.GroupedWork.forceReindex('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Force Reindex" isAdminFacing=true}</button>
{/if}
{if $loggedIn && in_array('Set Grouped Work Display Information', $userPermissions)}
	<button onclick="return AspenDiscovery.GroupedWork.getDisplayInfoForm('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Set Display Info" isAdminFacing=true}</button>
{/if}
{if $loggedIn && in_array('Manually Group and Ungroup Works', $userPermissions)}
	<button onclick="return AspenDiscovery.GroupedWork.getGroupWithForm(this, '{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Group With Work" isAdminFacing=true}</button>
{/if}

{include file="RecordDrivers/GroupedWork/grouping-information.tpl"}

{if !empty($details)}
	<h4>{translate text="Solr Details" isAdminFacing="true"}</h4>
	<table class="table-striped table table-condensed notranslate" style="display:block; overflow: auto;">
		{foreach from=$details key='field' item='values'}
			<tr>
				<th>{$field|escape}</th>
				<td>
					{if $field=='item_details'}
						<table>
							<thead>
								<tr style="vertical-align:bottom">
									<th>{translate text="Record Identifier" isPublicFacing=true}</th>
									<th>{translate text="Item Identifier" isPublicFacing=true}</th>
									<th>{translate text="Shelf Location" isPublicFacing=true}</th>
									<th>{translate text="Call Number" isPublicFacing=true}</th>
									<th>{translate text="Format" isPublicFacing=true}</th>
									<th>{translate text="Format Category" isPublicFacing=true}</th>
									<th>{translate text="Num Copies" isPublicFacing=true}</th>
									<th>{translate text="Order Item?" isPublicFacing=true}</th>
									<th>{translate text="eContent?" isPublicFacing=true}</th>
									<th>{translate text="eContent Source" isPublicFacing=true}</th>
									<th>{translate text="eContent Filename" isPublicFacing=true}</th>
									<th>{translate text="eContent Url" isPublicFacing=true}</th>
									<th>{translate text="Sub Format" isPublicFacing=true}</th>
									<th>{translate text="Detailed Status" isPublicFacing=true}</th>
									<th>{translate text="Last Check In" isPublicFacing=true}</th>
									<th>{translate text="Location Code" isPublicFacing=true}</th>
									<th>{translate text="Sub Location" isPublicFacing=true}</th>
								</tr>
							</thead>
							<tbody>
								{foreach from=$values item=detail}
									{assign var=detailColumms value="|"|explode:$detail}
									<tr style="vertical-align:top">
										{foreach from=$detailColumms item=detailColumm}
											<td>
												{$detailColumm}
											</td>
										{/foreach}
									</tr>
								{/foreach}
							</tbody>
						</table>
					{elseif $field=='record_details'}
						<table>
							<thead>
								<tr style="vertical-align:bottom">
									<th>{translate text="Record Identifier" isPublicFacing=true}</th>
									<th>{translate text="Format" isPublicFacing=true}</th>
									<th>{translate text="Format Category" isPublicFacing=true}</th>
									<th>{translate text="Edition" isPublicFacing=true}</th>
									<th>{translate text="Language" isPublicFacing=true}</th>
									<th>{translate text="Publisher" isPublicFacing=true}</th>
									<th>{translate text="Pub Date" isPublicFacing=true}</th>
									<th>{translate text="Physical Description" isPublicFacing=true}</th>
								</tr>
							</thead>
							{foreach from=$values item=detail}
								{assign var=detailColumms value="|"|explode:$detail}
								<tr style="vertical-align:top">
									{foreach from=$detailColumms item=detailColumm}
										<td>
											{$detailColumm}
										</td>
									{/foreach}
								</tr>
							{/foreach}
						</table>
					{elseif $field|strpos:'scoping_details_'===0}
						<table>
							<thead>
							<tr style="vertical-align:bottom">
								<th>{translate text="Item Full Identifier" isPublicFacing=true}</th>
								<th>{translate text="Item Identifier" isPublicFacing=true}</th>
								<th>{translate text="Grouped Status" isPublicFacing=true}</th>
								<th>{translate text="Status" isPublicFacing=true}</th>
								<th>{translate text="Locally Owned" isPublicFacing=true}</th>
								<th>{translate text="Available?" isPublicFacing=true}</th>
								<th>{translate text="Holdable?" isPublicFacing=true}</th>
								<th>{translate text="Bookable?" isPublicFacing=true}</th>
								<th>{translate text="In Library Use Only?" isPublicFacing=true}</th>
								<th>{translate text="Library Owned?" isPublicFacing=true}</th>
								<th>{translate text="Holdable PTypes" isPublicFacing=true}</th>
								<th>{translate text="Bookable PTypes" isPublicFacing=true}</th>
								<th>{translate text="Local URL" isPublicFacing=true}</th>
							</tr>
							</thead>
							{foreach from=$values item=detail}
								{assign var=detailColumms value="|"|explode:$detail}
								<tr style="vertical-align:top">
									{foreach from=$detailColumms item=detailColumm}
										<td>
											{$detailColumm}
										</td>
									{/foreach}
								</tr>
							{/foreach}
						</table>
					{else}
						{implode subject=$values glue='<br>' sort=true}
					{/if}

				</td>
			</tr>
		{/foreach}
	</table>
{/if}

{if isset($variationData)}
	<h4>{translate text="Record Details from Database" isAdminFacing="true"}</h4>
	<table class="table-striped table table-condensed notranslate">
		<tr>
			<th>{translate text="Grouped Work Internal ID" isAdminFacing=true}</th>
			<td>{$groupedWorkInternalId}</td>
		</tr>
		<tr>
			<th>{translate text="Active Scope ID" isAdminFacing=true}</th>
			<td>{$activeScopeId}</td>
		</tr>
	</table>

	<h4>{translate text="Variation Details from Database" isAdminFacing="true"}</h4>
	<table class="table-striped table table-condensed notranslate" style="display:block; overflow: auto;">
		<thead>
			<tr>
				<th>{translate text="ID" isAdminFacing="true"}</th>
				<th>{translate text="Language" isAdminFacing="true"}</th>
				<th>{translate text="eContent Source" isAdminFacing="true"}</th>
				<th>{translate text="Format" isAdminFacing="true"}</th>
				<th>{translate text="Format Category" isAdminFacing="true"}</th>
			</tr>
		</thead>
		<tbody>
			{foreach from=$variationData item=row}
				<tr>
					<th>{$row.id}</th>
					<th>{$row.language}</th>
					<th>{$row.eContentSource}</th>
					<th>{$row.format}</th>
					<th>{$row.formatCategory}</th>
				</tr>
			{/foreach}
		</tbody>
	</table>
{/if}

{if isset($recordData)}
	<table class="table-striped table table-condensed notranslate" style="display:block; overflow: auto;">
		<thead>
		<tr>
			<th>{translate text="ID" isAdminFacing="true"}</th>
			<th>{translate text="Record Identifier" isAdminFacing="true"}</th>
			<th>{translate text="Source" isAdminFacing="true"}</th>
			<th>{translate text="Sub Source" isAdminFacing="true"}</th>
			<th>{translate text="Edition" isAdminFacing="true"}</th>
			<th>{translate text="Publisher" isAdminFacing="true"}</th>
			<th>{translate text="Publication Date" isAdminFacing="true"}</th>
			<th>{translate text="Physical Description" isAdminFacing="true"}</th>
			<th>{translate text="Format" isAdminFacing="true"}</th>
			<th>{translate text="Format Category" isAdminFacing="true"}</th>
			<th>{translate text="Language" isAdminFacing="true"}</th>
			<th>{translate text="Closed Captioned?" isAdminFacing="true"}</th>
		</tr>
		</thead>
		<tbody>
        {foreach from=$recordData item=row}
			<tr>
				<th>{$row.id}</th>
				<th>{$row.recordIdentifier}</th>
				<th>{$row.source}</th>
				<th>{$row.subSource}</th>
				<th>{$row.edition}</th>
				<th>{$row.publisher}</th>
				<th>{$row.publicationDate}</th>
				<th>{$row.physicalDescription}</th>
				<th>{$row.format}</th>
				<th>{$row.formatCategory}</th>
				<th>{$row.language}</th>
				<th>{if $row.isClosedCaptioned}{translate text="Yes" isAdminFacing=true}{else}{translate text="No" isAdminFacing=true}{/if}</th>
			</tr>
        {/foreach}
		</tbody>
	</table>
{/if}

{if isset($itemData)}
	<h4>{translate text="Item Details from Database" isAdminFacing="true"}</h4>
	<table class="table-striped table table-condensed notranslate" style="display:block; overflow: auto;">
		<thead>
		<tr>
			<th>{translate text="ID" isAdminFacing="true"}</th>
			<th>{translate text="Available" isAdminFacing="true"}</th>
			<th>{translate text="Holdable" isAdminFacing="true"}</th>
			<th>{translate text="In Library Use Only" isAdminFacing="true"}</th>
			<th>{translate text="Location Owned Scopes" isAdminFacing="true"}</th>
			<th>{translate text="Library Owned Scopes" isAdminFacing="true"}</th>
			<th>{translate text="Grouped Status" isAdminFacing="true"}</th>
			<th>{translate text="Status" isAdminFacing="true"}</th>
			<th>{translate text="Grouped Work Record ID" isAdminFacing="true"}</th>
			<th>{translate text="Grouped Work Variation ID" isAdminFacing="true"}</th>
			<th>{translate text="Item ID" isAdminFacing="true"}</th>
			<th>{translate text="Call Number" isAdminFacing="true"}</th>
			<th>{translate text="Shelf Location" isAdminFacing="true"}</th>
			<th>{translate text="Number of Copies" isAdminFacing="true"}</th>
			<th>{translate text="Order Item?" isAdminFacing="true"}</th>
			<th>{translate text="Date Added" isAdminFacing="true"}</th>
			<th>{translate text="Location Code" isAdminFacing="true"}</th>
			<th>{translate text="Sub Location Code" isAdminFacing="true"}</th>
			<th>{translate text="Last Check In Date" isAdminFacing="true"}</th>
		</tr>
		</thead>
		<tbody>
		{foreach from=$itemData item=row}
			<tr>
				<th>{$row.groupedWorkItemId}</th>
				<th>{$row.available}</th>
				<th>{$row.holdable}</th>
				<th>{$row.inLibraryUseOnly}</th>
				<th>{$row.locationOwnedScopes}</th>
				<th>{$row.libraryOwnedScopes}</th>
				<th>{$row.groupedStatus}</th>
				<th>{$row.status}</th>
				<th>{$row.groupedWorkRecordId}</th>
				<th>{$row.groupedWorkVariationId}</th>
				<th>{$row.itemId}</th>
				<th>{$row.callNumber}</th>
				<th>{$row.shelfLocation}</th>
				<th>{$row.numCopies}</th>
				<th>{$row.isOrderItem}</th>
				<th>{$row.dateAdded}</th>
				<th>{$row.locationCode}</th>
				<th>{$row.subLocationCode}</th>
				<th>{$row.lastCheckInDate}</th>
			</tr>
		{/foreach}
		</tbody>
	</table>
{/if}
{/strip}