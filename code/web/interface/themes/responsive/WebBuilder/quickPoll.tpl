<div class="col-xs-12">
	<h1>{$poll->title}</h1>
    {if !empty($loggedIn) && (array_key_exists('Administer All Quick Polls', $userPermissions) || array_key_exists('Administer Library Quick Polls', $userPermissions))}
		<div class="row">
			<div class="col-xs-12">
				<a href="/WebBuilder/QuickPolls?id={$id}&objectAction=edit" class="btn btn-default btn-sm">{translate text=Edit isAdminFacing=true}</a>
				<a href="/WebBuilder/QuickPollSubmissions?pollId={$id}" class="btn btn-default btn-sm">{translate text="View Submissions" isAdminFacing=true}</a>
			</div>
		</div>
	{/if}
	{if !empty($introText)}
		<div class="alert alert-info">
			{$introText}
		</div>
	{/if}
	{if !empty($submissionError)}
		<div class="alert alert-danger">
			{$submissionError}
		</div>
    {/if}
	<form id="quickPoll{$id}" class="form-horizontal" role="form" action="/WebBuilder/SubmitQuickPoll"  onsubmit="setFormSubmitting();">
		<input type="hidden" name="id" id="id" value="{$id}">
		{foreach from=$pollOptions item=$pollOption}
			<div class="form-group">
				<div class="col-xs-12">
					<div class="checkbox">
						<label for='pollOption_{$pollOption->id}'>{translate text="{$pollOption->label}" isAdminFacing=true}
							<input type="checkbox" name='pollOption[]' id='pollOption_{$pollOption->id}' value="{$pollOption->id}"/>
						</label>
					</div>
				</div>
			</div>
		{/foreach}
		<div class="form-group">
			<label class="control-label" for="name">{translate text="Your Name" isAdminFacing=true} <span class="required-input">*</span></label>
			<input type="text" class="form-control {if $poll->requireName == 1}required{/if}" name="name" id="name" value="">
		</div>
		<div class="form-group">
			<label class="control-label" for="email">{translate text="Email" isAdminFacing=true} <span class="required-input">*</span></label>
			<input type="email" class="form-control {if $poll->requireEmail == 1}required{/if}" name="email" id="email" value="">
		</div>
		<div class="form-group">
			<div class="col-xs-12">
				<input type="submit" name="submit" value="{translate text="Submit" inAttribute=true isAdminFacing=true}" class="btn btn-primary">
			</div>
		</div>
	</form>
</div>