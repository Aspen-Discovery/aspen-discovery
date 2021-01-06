{strip}
<div id="main-content" class="col-tn-12 col-xs-12">
	<h1>Aspen Discovery Status</h1>
	<h2>Server Self Check</h2>
	<div class="alert {if $aspenStatus.aspen_health_status == 'critical'}alert-danger{elseif $aspenStatus.aspen_health_status == 'warning'}alert-warning{else}alert-success{/if}">
		{$aspenStatus.aspen_health_status|capitalize}
	</div>
	<table class="table table-bordered" aria-label="Self Checks">
		<thead>
			<th>Check Name</th>
			<th>Status</th>
		</thead>
		{foreach from=$aspenStatus.checks item=check}
			<tr>
				<td>{$check.name}</td>
				<td class="{if $check.status == 'critical'}danger{elseif $check.status == 'warning'}warning{else}success{/if}">{$check.status}</td>
			</tr>
		{/foreach}
	</table>

	<h2>Solr Cores</h2>

	{foreach from=$data item=searchIndex}
		<h3>{$searchIndex.name}</h3>
		<table class="table table-bordered" aria-label="Status of {$searchIndex.name} Solr index">
			<tr>
				<th>Record Count</th>
				<td>{$searchIndex.index.numDocs}</td>
			</tr>
			<tr>
				<th>Start Time</th>
				<td>{$searchIndex.startTime|date_format:"%b %d, %Y %l:%M:%S%p"}</td>
			</tr>
			<tr>
				<th>Last Modified</th>
				<td>{$searchIndex.index.lastModified|date_format:"%b %d, %Y %l:%M:%S%p"}</td>
			</tr>
			<tr>
				<th>Uptime</th>
				<td>{$searchIndexuptime|printms}</td>
			</tr>
			<tr>
				<th>Full Status</th>
				<td><a onclick="$('#searcherStatus_{$searchIndex.name|escape:css}').show();">Show full status</a>
					<div id="searcherStatus_{$searchIndex.name|escape:css}" style="display:none"><pre>{$searchIndex|print_r}</pre></div>
				</td>
			</tr>
		</table>
	{/foreach}
</div>
{/strip}