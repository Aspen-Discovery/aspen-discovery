{strip}
	<div class="nopadding col-sm-12">
		<div class="exploreMoreBar row">
			{*<div class="label-left">*}
			<div class="label-top">
				<div class="exploreMoreBarLabel"><div class="archiveComponentHeader">Browse All</div></div>
			</div>

			<div class="col-xs-12 exploreMoreContainer">
				<div id="related-objects-for-exhibit" class="exploreMoreItemsContainer">
					<div class="row">
						<div id="exhibit-results-loading" class="col-xs-12" style="display: none">
							<br/>
							<div class="alert alert-info">
								Updating results, please wait.
							</div>
						</div>
					</div>
				</div>

				<script type="text/javascript">
					$(document).ready(function(){ldelim}
						return VuFind.Archive.handleCollectionScrollerClick('{$pid}');
						{rdelim});
				</script>
			</div>
		</div>
	</div>
{/strip}