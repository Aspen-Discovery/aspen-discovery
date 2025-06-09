<div>
	<h1>{translate text="Oops, an error occurred" isPublicFacing=true}</h1>
	<h2>{translate text="This error has been logged and we are working on a fix." isPublicFacing=true}</h2>
	<h4>{$errorMessage}</h4>
	<h4>{translate text="Please contact the Library Reference Department for assistance" isPublicFacing=true}<br /></h4>
	{if !empty($supportEmail)}
		<h4><a href="mailto:{$supportEmail}">{$supportEmail}</a></h4>
	{/if}

	{if !empty($retryTalpaSearchLink)}
		<div style="margin-top: 20px;">
			<a class="btn btn-primary" href="{$retryTalpaSearchLink}">Retry this search</a>
		</div>
	{/if}
</div>
<div id ="debug">
	{if !empty($debug)}
		<h4>{translate text="Debug Information" isAdminFacing=true}</h4>
		<p>{translate text="Backtrace" isAdminFacing=true}</p>
		{$error->backtrace}
	{/if}
</div>
