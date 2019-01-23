{strip}
	<h3>Claim Authorship of Archive Materials</h3>
	<div class="page">
		{if $requestSubmitted}
			{if $error}
				<p>There was an error submitting your request.</p>
				<p>{$error}</p>
			{else}
				<p>Your request was submitted successfully.  The library will contact you with more information soon.</p>
			{/if}
		{else}
			{if $claimAuthorshipHeader}
				{$claimAuthorshipHeader}
			{else}
				<p>
					Please fill out this form if you are the author of this object.
					The owning library will contact you to confirm the details of your request so you can be properly credited.
				</p>
				<p>
					For the best and most immediate service, please include your email address.
				</p>
			{/if}

			{if $captchaMessage}
				<div id="selfRegFail" class="alert alert-warning">
					{$captchaMessage}
				</div>
			{/if}
			<div id="archiveClaimAuthorshipFormContainer">
				{$requestForm}
			</div>
		{/if}

	</div>

{/strip}