{strip}
<button onclick="return AspenDiscovery.GroupedWork.reloadCover('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Reload Cover" isAdminFacing=true}</button>
{if $loggedIn && in_array('Upload Covers', $userPermissions)}
	<button onclick="return AspenDiscovery.GroupedWork.getUploadCoverForm('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Upload Cover from Computer" isAdminFacing=true}</button>
	<button onclick="return AspenDiscovery.GroupedWork.getUploadCoverFormByURL('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Upload Cover by URL" isAdminFacing=true}</button>
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
{/strip}