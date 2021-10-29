<div>
	<h1>{translate text="Oops, an error occurred" isPublicFacing=true}</h1>
	<h2>{translate text="This error has been logged and we are working on a fix." isPublicFacing=true}</h2>
	<h4>{$error->getMessage()}</h4>
	<h4>{translate text="Please contact the Library Reference Department for assistance" isPublicFacing=true}<br /></h4>
	{if $supportEmail}
	<h4><a href="mailto:{$supportEmail}">{$supportEmail}</a></h4>
	{/if}
</div>
<div id ="debug">
	{if $debug}
		<h4>{translate text="Debug Information" isPublicFacing=true}</h4>
		<p>{translate text="Backtrace" isPublicFacing=true}</p>
		{$error->backtrace}
	{/if}
</div>
