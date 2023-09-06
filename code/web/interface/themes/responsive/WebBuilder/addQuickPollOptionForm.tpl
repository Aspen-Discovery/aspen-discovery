{strip}
	<form enctype="multipart/form-data" name="addPollOptionForm" id="addPollOptionForm" method="post" action="/WebBuilder/AJAX" class="form">
		<input type="hidden" name="pollId" id="pollId" value="{$pollId}"/>
		<div class="form-group">
			<label class="control-label">{translate text="New Option" isPublicFacing=true}</label>
			<input type="text" class="form-control" id="newOption" required>
		</div>
	</form>
{/strip}