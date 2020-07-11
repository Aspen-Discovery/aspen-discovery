<html>
	<h1>{$formTitle}</h1>
	<div><b>From</b></div>
	<div>{if !empty($patronName)}{$patronName}{else}Anonymous{/if}</div><br/>
	{$htmlData}
</html>