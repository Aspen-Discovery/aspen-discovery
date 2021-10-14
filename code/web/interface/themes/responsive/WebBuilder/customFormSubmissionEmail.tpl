<html>
	<h1>{$formTitle}</h1>
	<div><b>From</b></div>
	<div>{if !empty($patronName)}{$patronName}{else}Anonymous{/if}{if !empty($replyTo)} {$replyTo}{/if}</div><br/>
	{$htmlData}
</html>