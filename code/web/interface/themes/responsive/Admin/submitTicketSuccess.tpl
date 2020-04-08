{strip}
	<div id="main-content" class="col-xs-12">
		<h1>{translate text="Submit Support Ticket"}</h1>
		<hr>
        {if !empty($error)}
			<div class="alert alert-info">
                Your ticket was submitted successfully.
			</div>
        {/if}
	</div>
{/strip}