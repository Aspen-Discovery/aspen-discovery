{* Javascript to embed on results pages to enable display modes. *}
{if !$onInternalIP}
	if (!Globals.opac &&VuFind.hasLocalStorage()){ldelim}
	var temp = window.localStorage.getItem('searchResultsDisplayMode');
	if (VuFind.Searches.displayModeClasses.hasOwnProperty(temp)) VuFind.Searches.displayMode = temp; {* if stored value is empty or a bad value, fall back on default setting ("null" returned when not set) *}
	else VuFind.Searches.displayMode = '{$displayMode}';
{rdelim}
	else VuFind.Searches.displayMode = '{$displayMode}';
{else}
	VuFind.Searches.displayMode = '{$displayMode}';
	Globals.opac = 1; {* set to true to keep opac browsers from storing browse mode *}
{/if}
$('#'+VuFind.Searches.displayMode).parent('label').addClass('active'); {* show user which one is selected *}