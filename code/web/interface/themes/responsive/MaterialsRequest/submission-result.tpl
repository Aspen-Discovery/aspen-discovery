<div id="page-content" class="content">
	<div id="main-content">
		<h1>{translate text='Materials Request Result' isPublicFacing=true}</h1>
		{if $success == 0}
			<div class="alert alert-danger">
			{$error}
			</div>
		{else}
			<div class="result">
				<div class="alert alert-success">
					{translate text="Your request for <b>%1%</b> by <b>%2%</b> was submitted successfully." 1=$materialsRequest->title 2=$materialsRequest->author isPublicFacing=true}
				</div>
				<div id="materialsRequestSummary" class="alert alert-info">
					{translate text="You have used <strong>%1%</strong> of your %2% yearly materials requests.  We also limit patrons to %3% active materials requests at a time.  You currently have <strong>%4%</strong> active materials requests." 1=$requestsThisYear 2=$maxRequestsPerYear 3=$maxActiveRequests 4=$openRequests isPublicFacing=true}
				</div>

				<p>
					<a role="button" class="btn btn-primary" href="{$accountPageLink}">{translate text='See My Materials Requests' isPublicFacing=true}</a>
				</p>
			</div>
		{/if}
	</div>
</div>
