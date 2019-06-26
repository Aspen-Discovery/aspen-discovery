<div>
	<h1>{translate text="Oops, an error occurred"}</h1>
	<h2>{translate text='error_logged_message' defaultText="This error has been logged and we are working on a fix."}</h2>
	<h4>{$error->getMessage()}</h4>
	<h4>{translate text="contact_library_message" defaultText="Please contact the Library Reference Department for assistance"}<br /></h4>
	{if $supportEmail}
	<h4><a href="mailto:{$supportEmail}">{$supportEmail}</a></h4>
	{/if}
</div>
<div id ="debug">
	{if $debug}
		<h4>{translate text="Debug Information"}</h4>
		<p>{translate text="Backtrace"}:</p>
		{foreach from=$error->getRawBacktrace() item=trace}
			[{$trace.line}] {$trace.file}<br />
		{/foreach}
	{/if}
</div>
