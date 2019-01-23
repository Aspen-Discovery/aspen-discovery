<div onmouseup="this.style.cursor='default';" id="popupboxHeader" class="header">
	<a onclick="hideLightbox(); return false;" href="">close</a>
	{$title}
</div>
<div id="popupboxContent" class="content">
	{* Generate links for each go deeper option *}
	<div id='goDeeperContent'>
	<div id='goDeeperLinks'>
	{foreach from=$options item=option key=dataAction}
	<div class='goDeeperLink'><a href='#' onclick="getGoDeeperData('{$dataAction}', '{$recordType}', '{$id}', '{$isbn}', '{$upc}');return false;">{$option}</a></div>
	{/foreach}
	</div>
	<div id='goDeeperOutput'>{$defaultGoDeeperData}</div>
	</div>
	<div id='goDeeperEnd'>&nbsp;</div>
</div>