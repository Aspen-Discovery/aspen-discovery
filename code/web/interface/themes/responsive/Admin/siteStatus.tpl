{strip}
<div id="main-content" class="col-tn-12 col-xs-12">
	<h1>{translate text="Aspen Discovery Status" isAdminFacing=true}</h1>
	<h2>{translate text="Server Self Check" isAdminFacing=true}</h2>
	<div class="alert {if $aspenStatus.aspen_health_status == 'critical'}alert-danger{elseif $aspenStatus.aspen_health_status == 'warning'}alert-warning{else}alert-success{/if}">
		{translate text=$aspenStatus.aspen_health_status|capitalize isAdminFacing=true}
	</div>
	<table class="table table-bordered" aria-label="Self Checks">
		<thead>
			<th>{translate text="Check Name" isAdminFacing=true}</th>
			<th>{translate text="Status" isAdminFacing=true}</th>
		</thead>
		{foreach from=$aspenStatus.checks item=check}
			<tr>
				<td>{translate text=$check.name isAdminFacing=true}</td>
				<td class="{if $check.status == 'critical'}danger{elseif $check.status == 'warning'}warning{else}success{/if}">{translate text=$check.status isAdminFacing=true}</td>
			</tr>
		{/foreach}
	</table>

	<h2>{translate text="Solr Cores" isAdminFacing=true}</h2>

	{foreach from=$data item=searchIndex}
		<h3>{$searchIndex.name}</h3>
		<table class="table table-bordered" aria-label="{translate text="Status of %1% Solr index" 1=$searchIndex.name isAdminFacing=true inAttribute=true}">
			<tr>
				<th>{translate text="Record Count" isAdminFacing=true}</th>
				<td>{$searchIndex.index.numDocs}</td>
			</tr>
			<tr>
				<th>{translate text="Start Time" isAdminFacing=true}</th>
				<td>{$searchIndex.startTime|date_format:"%b %d, %Y %l:%M:%S%p"}</td>
			</tr>
			<tr>
				<th>{translate text="Last Modified" isAdminFacing=true}</th>
				<td>{$searchIndex.index.lastModified|date_format:"%b %d, %Y %l:%M:%S%p"}</td>
			</tr>
			<tr>
				<th>{translate text="Uptime" isAdminFacing=true}</th>
				<td>{$searchIndexuptime|printms}</td>
			</tr>
			<tr>
				<th>{translate text="Full Status" isAdminFacing=true}</th>
				<td><a onclick="$('#searcherStatus_{$searchIndex.name|escapeCSS}').show();">{translate text="Show full status" isAdminFacing=true}</a>
					<div id="searcherStatus_{$searchIndex.name|escapeCSS}" style="display:none"><pre>{$searchIndex|print_r}</pre></div>
				</td>
			</tr>
		</table>
	{/foreach}
</div>
{/strip}