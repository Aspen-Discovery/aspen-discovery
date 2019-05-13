<strip>
<button onclick="return VuFind.GroupedWork.reloadCover('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">Reload Cover</button>
<button onclick="return VuFind.GroupedWork.reloadEnrichment('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">Reload Enrichment</button>
{if $loggedIn && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('catalogging', $userRoles))}
	<button onclick="return VuFind.GroupedWork.forceReindex('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">Force Reindex</button>
	<button onclick="return VuFind.GroupedWork.forceRegrouping('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">Force Regrouping</button>
{/if}
{if $loggedIn && $enableArchive && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('archives', $userRoles))}
	<button onclick="return VuFind.GroupedWork.reloadIslandora('{$recordDriver->getUniqueID()}')" class="btn btn-sm btn-default">Clear Islandora Cache</button>
{/if}

<h4>Grouping Information</h4>
<table class="table-striped table table-condensed notranslate">
	<tr>
		<th>Grouped Work ID</th>
		<td>{$recordDriver->getPermanentId()}</td>
	</tr>
	{foreach from=$groupedWorkDetails key='field' item='value'}
	<tr>
		<th>{$field|escape}</th>
		<td>
			{$value}
		</td>
	</tr>
	{/foreach}
</table>

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
</strip>