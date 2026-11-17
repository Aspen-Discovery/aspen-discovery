{strip}
	<div id="main-content" class="col-md-12">
		<h1>{translate text="Hoopla Aspen Information" isAdminFacing=true}</h1>
		{if empty($hooplaVersion2)}
			<div class="alert alert-info">{translate text="Records to Reindex requires Hoopla version 2." isAdminFacing=true}</div>
		{elseif !empty($canForceReindex)}
			<form method="post" action="/Hoopla/AspenData" class="form">
				<div class="form-group">
					<label for="recordsToReindex" class="control-label">{translate text="Records to Reindex" isAdminFacing=true}</label>
					<textarea id="recordsToReindex" name="recordsToReindex" class="form-control" rows="3"></textarea>
					<small class="form-text text-muted">{translate text="Enter Hoopla IDs (numbers only), separated by new lines or commas." isAdminFacing=true}</small>
				</div>
				<button class="btn btn-primary" type="submit">{translate text="Reindex" isAdminFacing=true}</button>
			</form>
		{/if}
		{if !empty($invalidIds)}
			<div class="alert alert-warning">{translate text="These Hoopla IDs are not valid, Hoopla IDs can only contain numbers" isAdminFacing=true}: {', '|implode:$invalidIds}</div>
		{/if}
		{if !empty($alreadyInQueueIds)}
			<div class="alert alert-info">{translate text="Already waiting to be reindexed" isAdminFacing=true}: {', '|implode:$alreadyInQueueIds}</div>
		{/if}
		{if !empty($numQueued)}
			<div class="alert alert-success">{translate text="%1% record(s) added to the queue and will be reindexed within a few minutes." 1=$numQueued isAdminFacing=true}</div>
		{/if}
	</div>
{/strip}