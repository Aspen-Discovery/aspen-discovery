{* Javascript to embed on results pages to enable display modes. *}
{if !$onInternalIP}
	if (!Globals.opac && AspenDiscovery.hasLocalStorage()){ldelim}
	var temp = window.localStorage.getItem('searchResultsDisplayMode');
	if (AspenDiscovery.Searches.displayModeClasses.hasOwnProperty(temp)) AspenDiscovery.Searches.displayMode = temp; {* if stored value is empty or a bad value, fall back on default setting ("null" returned when not set) *}
	else AspenDiscovery.Searches.displayMode = '{$displayMode}';
{rdelim}
	else AspenDiscovery.Searches.displayMode = '{$displayMode}';
{else}
	AspenDiscovery.Searches.displayMode = '{$displayMode}';
	Globals.opac = 1; {* set to true to keep opac browsers from storing browse mode *}
{/if}
$('#'+AspenDiscovery.Searches.displayMode).parent('label').addClass('active'); {* show user which one is selected *}