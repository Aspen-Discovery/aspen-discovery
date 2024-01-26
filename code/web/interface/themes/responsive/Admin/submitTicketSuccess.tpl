{strip}
	<div id="main-content" class="col-xs-12">
		<h1>{translate text="Submit Support Ticket" isAdminFacing=true}</h1>
		<hr>
		{if $error}
			<div class="alert alert-danger">
                {translate text="There was an error submitting your ticket." isAdminFacing=true}
			</div>
			{else}
			<div class="alert alert-info">
                {translate text="Your ticket was submitted successfully." isAdminFacing=true}
			</div>
		{/if}
	</div>
{/strip}