{strip}
	<h3>Contribute Content To The Archive</h3>
	<div class="page">
		{if $requestSubmitted}
			{if !empty($error)}
				<p>There was an error submitting your content.</p>
				<p>{$error}</p>
			{else}
				<p>Your content was submitted successfully.  The library will contact you if they need additional information soon.</p>
			{/if}
		{else}
			<p>
				Please fill out this form to contribute content to our archive.
				Our librarians will review the content to determine if it is suitable for inclusion.
			</p>
			{if $captchaMessage}
				<div id="selfRegFail" class="alert alert-warning">
					{$captchaMessage}
				</div>
			{/if}
			<div id="redwoordContributeContentFormContainer">
				{$requestForm}
			</div>
		{/if}

	</div>

{/strip}