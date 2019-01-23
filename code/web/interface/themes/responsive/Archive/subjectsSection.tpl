{strip}
	{foreach from=$subjects item=subject}
		<a href='{$subject.link}'>
			{$subject.label}
		</a><br>
	{/foreach}
{/strip}