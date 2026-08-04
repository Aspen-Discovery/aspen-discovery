<div id="main-content" class="col-xs-12">
	<h1>{translate text="Aspen API Documentation" isAdminFacing=true}</h1>
	<hr>
	<form class="navbar form-inline row">
		<div class="form-group col-xs-12">
			<label for="apiSelector" class="control-label">Select an API</label>&nbsp;&nbsp;
			<select id="apiSelector" name="apiSelector" class="form-control input-sm" onchange="return AspenDiscovery.Admin.displayApiDocs()">
				{foreach from=$apiFiles key=apiName item=filename}
					<option value="{$apiName}" {if $apiName==$activeApiFile}selected="selected"{/if}>{$apiName}</option>
				{/foreach}
			</select>
		</div>
	</form>
	<rapi-doc allow-spec-url-load="false" allow-spec-file-load="false" allow-advanced-search="false" render-style="view" allow-try="false" allow-authentication="false" default-schema-tab="schema"
		spec-url="{$activeApiFileFullPath}"
		server-url="{$url}{$apiBasePath}"
		default-api-server="{$url}{$apiBasePath}"
		bg-color="{$bodyBackgroundColor}"
		header-color="{$bodyBackgroundColor}"
		regular-font="{$bodyFont}"
		mono-font="'Consolas', monospace"
		text-color="{$bodyTextColor}"
		primary-color="{$linkColor}"
		nav-bg-color="{$secondaryBackgroundColor}"
		{if $isDarkColorScheme}theme="dark" {else}theme="light"{/if}>
		<img slot="logo" src="" alt="" />
	</rapi-doc>

	<script type="module" src="/interface/themes/responsive/js/lib/rapidoc.min.js?v={$aspenVersion|urlencode}.{$cssJsCacheCounter}"></script>
</div>
