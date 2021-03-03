{ldelim}
label: {$seriesLabel},
data: [
{foreach from=$seriesData item=curValue}
	{$curValue},
{/foreach}
],
borderWidth: 1
{rdelim},