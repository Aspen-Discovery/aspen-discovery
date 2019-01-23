{strip}
	{if $recordDriver}
		<div class="row">
			<div class="result-label col-xs-2">Grouped Work ID: </div>
			<div class="col-xs-10 result-value">
				{$recordDriver->getPermanentId()}
			</div>
		</div>

		<div class="row">
			<div class="col-xs-12">
				<a href="{$path}/GroupedWork/{$recordDriver->getPermanentId()}" class="btn btn-sm btn-default">Go To Grouped Work</a>
				<button onclick="return VuFind.Record.reloadCover('{$recordDriver->getModule()}', '{$id}')" class="btn btn-sm btn-default">Reload Cover</button>
				<button onclick="return VuFind.GroupedWork.reloadEnrichment('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default" >Reload Enrichment</button>
				{if $staffClientUrl}
					<a href="{$staffClientUrl}" class="btn btn-sm btn-info">View in Staff Client</a>
				{/if}
				{if $loggedIn && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('cataloging', $userRoles))}
					{if $classicUrl}
						<a href="{$classicUrl}" class="btn btn-sm btn-info">View in Classic</a>
					{/if}
					<button onclick="return VuFind.GroupedWork.forceReindex('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">Force Reindex</button>
					<button onclick="return VuFind.GroupedWork.forceRegrouping('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">Force Regrouping</button>
					<a href="{$path}/{$recordDriver->getModule()}/{$id|escape:"url"}/AJAX?method=downloadMarc" class="btn btn-sm btn-default">{translate text="Download Marc"}</a>
				{/if}
				{if $loggedIn && $enableArchive && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('archives', $userRoles))}
					<button onclick="return VuFind.GroupedWork.reloadIslandora('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">Clear Islandora Cache</button>
				{/if}
			</div>
		</div>

		{* QR Code *}
		{if $showQRCode}
			<div id="record-qr-code" class="text-center hidden-xs visible-md"><img src="{$recordDriver->getQRCodeUrl()}" alt="QR Code for Record"></div>
		{/if}
	{/if}


	{if $hooplaExtract}
	<h3>Hoopla Extract Information</h3>
	<table class="table-striped table table-condensed notranslate">
		{foreach from=$hooplaExtract key='field' item='values'}
			<tr>
				<th>{$field|escape}</th>
				<td>
					{implode subject=$values glue=', ' sort=true}
				</td>
			</tr>
		{/foreach}
	</table>
	{/if}

	{if $marcRecord}
		<div id="formattedMarcRecord">
			<h3>MARC Record</h3>

			<table class="table-striped table table-condensed notranslate">
				<tr>
					<th>Last File Modification Time</th>
					<td>{$lastMarcModificationTime|date_format:"%b %d, %Y %r"}</td>
				</tr>
				<tr>
					<th>Last Grouped Work Modification Time</th>
					<td>{$lastGroupedWorkModificationTime|date_format:"%b %d, %Y %r"}</td>
				</tr>
			</table>



			<table class="citation" border="0">
				<tbody>
				{*Output leader*}
				<tr><th>LEADER</th><td colspan="3">{$marcRecord->getLeader()}</td></tr>
				{foreach from=$marcRecord->getFields() item=field}
					{if get_class($field) == "File_MARC_Control_Field"}
						<tr><th>{$field->getTag()}</th><td colspan="3">{$field->getData()|escape|replace:' ':'&nbsp;'}</td></tr>
					{else}
						<tr><th>{$field->getTag()}</th><th>{$field->getIndicator(1)}</th><th>{$field->getIndicator(2)}</th><td>
								{foreach from=$field->getSubfields() item=subfield}
									<strong>|{$subfield->getCode()}</strong>&nbsp;{$subfield->getData()|escape}
								{/foreach}
							</td></tr>
					{/if}

				{/foreach}
				</tbody>
			</table>
		</div>
	{/if}

	{if $solrRecord}
		<div id="formattedSolrRecord">
			<h3>Solr Record</h3>
			<dl>
				{foreach from=$solrRecord key='field' item='values'}
					<dt>{$field|escape}</dt>
					<dd>{implode subject=$values glue=", "}</dd>
				{/foreach}
			</dl>
		</div>
	{/if}

{/strip}