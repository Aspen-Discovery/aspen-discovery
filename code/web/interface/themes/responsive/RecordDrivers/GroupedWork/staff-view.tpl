{strip}
<button onclick="return AspenDiscovery.GroupedWork.reloadCover('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Reload Cover"}</button>
{if $loggedIn && in_array('Upload Covers', $userPermissions)}
	<button onclick="return AspenDiscovery.GroupedWork.getUploadCoverForm('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Upload Cover from Computer"}</button>
	<button onclick="return AspenDiscovery.GroupedWork.getUploadCoverFormByURL('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Upload Cover by URL"}</button>
{/if}
<button onclick="return AspenDiscovery.GroupedWork.reloadEnrichment('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Reload Enrichment"}</button>
{if $loggedIn && in_array('Force Reindexing of Records', $userPermissions)}
	<button onclick="return AspenDiscovery.GroupedWork.forceReindex('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Force Reindex"}</button>
{/if}
{if $loggedIn && in_array('Set Grouped Work Display Information', $userPermissions)}
	<button onclick="return AspenDiscovery.GroupedWork.getDisplayInfoForm('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Set Display Info"}</button>
{/if}
{if $loggedIn && in_array('Manually Group and Ungroup Works', $userPermissions)}
	<button onclick="return AspenDiscovery.GroupedWork.getGroupWithForm(this, '{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Group With Work"}</button>
{/if}

{include file="RecordDrivers/GroupedWork/grouping-information.tpl"}

<h4>Solr Details</h4>
<table class="table-striped table table-condensed notranslate" style="display:block; overflow: auto;">
	{foreach from=$details key='field' item='values'}
		<tr>
			<th>{$field|escape}</th>
			<td>
				{if $field=='item_details'}
					<table>
						<thead>
							<tr style="vertical-align:bottom">
								<th>Record Identifier</th>
								<th>Item Identifier</th>
								<th>Shelf Location</th>
								<th>Call Number</th>
								<th>Format</th>
								<th>Format Category</th>
								<th>Num Copies</th>
								<th>Order Item?</th>
								<th>eContent?</th>
								<th>eContent Source</th>
								<th>eContent Filename</th>
								<th>eContent Url</th>
								<th>Sub Format</th>
								<th>Detailed Status</th>
								<th>Last Check In</th>
								<th>Location Code</th>
								<th>Sub Location</th>
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
								<th>Record Identifier</th>
								<th>Format</th>
								<th>Format Category</th>
								<th>Edition</th>
								<th>Language</th>
								<th>Publisher</th>
								<th>Pub Date</th>
								<th>Physical Description</th>
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
							<th>Item Full Identifier</th>
							<th>Item Identifier</th>
							<th>Grouped Status</th>
							<th>Status</th>
							<th>Locally Owned</th>
							<th>Available?</th>
							<th>Holdable?</th>
							<th>Bookable?</th>
							<th>In Library Use Only?</th>
							<th>Library Owned?</th>
							<th>Holdable PTypes</th>
							<th>Bookable PTypes</th>
							<th>Local URL</th>
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
{/strip}