{strip}
	<div id="main-content" class="col-md-12">
		<form name="cleanupArchiveCache" method="post">
			<h3>Archive Cache</h3>
			<div class="alert alert-info">There are currently {$numCachedObjects} objects in the cache.  Clearing the entire cache may result in performance issues until the cache is rebuilt.</div>

			<div class="form-group">
				<button type="submit" name="submit" class="btn btn-default">Clear Cache</button>
			</div>

		</form>
	</div>
{/strip}