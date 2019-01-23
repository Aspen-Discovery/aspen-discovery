{strip}
	<a class="btn btn-small btn-default" href="{$repositoryLink}" target="_blank">
		View in Islandora
	</a>
	<a class="btn btn-small btn-default" href="{$repositoryLink}/datastream/MODS/view" target="_blank">
		View MODS Record
	</a>
	<a class="btn btn-small btn-default" href="{$repositoryLink}/datastream/MODS/edit" target="_blank">
		Edit MODS Record
	</a>
	<a class="btn btn-small btn-default" href="#" onclick="return VuFind.Archive.clearCache('{$pid}');" target="_blank">
		Clear Cache
	</a>
{/strip}