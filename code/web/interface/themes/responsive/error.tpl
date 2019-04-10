<div>
	<h1>Oops, an error occurred</h1>
	<h2>This error has been logged and we are working on a fix.</h2>
	<h4>{$error->getMessage()}</h4>
	<h4>{translate text="Please contact the Library Reference Department for assistance"}<br /></h4>
	{if $supportEmail}
	<h4><a href="mailto:{$supportEmail}">{$supportEmail}</a></h4>
	{/if}
</div>
<div id ="debug">
	{if $debug}
		<h4>{translate text="Debug Information"}</h4>
		<p>{translate text="Backtrace"}:</p>
		{foreach from=$error->backtrace item=trace}
			[{$trace.line}] {$trace.file}<br />
		{/foreach}
	{/if}
</div>
