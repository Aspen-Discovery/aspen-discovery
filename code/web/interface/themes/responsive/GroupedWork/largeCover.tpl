{strip}
	<form enctype="multipart/form-data" name="getLargeCover" id="getLargeCover" method="post" action="/GroupedWork/{$groupedWorkId}/AJAX">
		<input type="hidden" name="groupedWorkId" value="{$groupedWorkId}"/>

		<div class="form-group">
			<div id="recordCover" class="text-center row">
				<img alt="{translate text='Book Cover' inAttribute=true}" class="img-thumbnail" src="/bookcover.php?id={$groupedWorkId}&size=large&type=grouped_work">
			</div>

		</div>
	</form>
{/strip}