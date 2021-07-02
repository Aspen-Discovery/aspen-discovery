{strip}
	<a class="btn btn-small btn-default" href="{$repositoryLink}" target="_blank">
		<i class="fas fa-external-link-alt"></i> View in Islandora
	</a>
	<a class="btn btn-small btn-default" href="{$repositoryLink}/datastream/MODS/view" target="_blank">
		<i class="fas fa-external-link-alt"></i> View MODS Record
	</a>
	<a class="btn btn-small btn-default" href="{$repositoryLink}/datastream/MODS/edit" target="_blank">
		<i class="fas fa-external-link-alt"></i> Edit MODS Record
	</a>
	<a class="btn btn-small btn-default" href="#" onclick="return AspenDiscovery.Archive.clearCache('{$pid}');" target="_blank">
		<i class="fas fa-external-link-alt"></i> Clear Cache
	</a>
{/strip}