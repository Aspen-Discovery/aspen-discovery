{strip}
<div id="main-content" class="col-tn-12 col-xs-12">
	<h1>Aspen Discovery Status</h1>
	<hr>
	{if $aspenStatus}
	<h2>Aspen Discovery Status</h2>
		<table class="table table-bordered">
			<tr class="{if $aspenStatus == 'critical'}danger{elseif $aspenStatus == 'warning'}warning{else}success{/if}">
				<th>{$aspenStatus|capitalize}</th>
			</tr>
			{foreach from=$aspenStatusMessages item=message}
				<tr>
					<td>{$message}</td>
				</tr>
			{/foreach}
		</table>
	{/if}

	<h2>Solr Cores</h2>

	{foreach from=$data item=searchIndex}
		<h3>{$searchIndex.name}</h3>
		<table class="table table-bordered">
			<tr>
				<th>Record Count: </th>
				<td>{$searchIndex.index.numDocs}</td>
			</tr>
			<tr>
				<th>Start Time: </th>
				<td>{$searchIndex.startTime|date_format:"%b %d, %Y %l:%M:%S%p"}</td>
			</tr>
			<tr>
				<th>Last Modified: </th>
				<td>{$searchIndex.index.lastModified|date_format:"%b %d, %Y %l:%M:%S%p"}</td>
			</tr>
			<tr>
				<th>Uptime: </th>
				<td>{$searchIndexuptime|printms}</td>
			</tr>
			<tr>
				<th>Full Status: </th>
				<td><a onclick="$('#searcherStatus').show();">Show full status</a>
					<div id="searcherStatus" style="display:none"><pre>{$searchIndex|print_r}</pre></div>
				</td>
			</tr>
		</table>
	{/foreach}
</div>
{/strip}