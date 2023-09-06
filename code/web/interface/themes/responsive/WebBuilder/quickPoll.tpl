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
		<div id="pollOptions">
			{foreach from=$pollOptions item=$pollOption}
				{include file="WebBuilder/quickPollOption.tpl"}
			{/foreach}
			{if $poll->allowSuggestingNewOptions}
				<span id="newOptionPlaceholder"></span>
			{/if}
		</div>
		{if $poll->allowSuggestingNewOptions}
			<div class="form-group" id="initialCustomPollOptionRow">
				<div class="col-xs-12">
					<button class="btn btn-default" onclick="return AspenDiscovery.WebBuilder.getAddQuickPollOptionForm('{$id}');">{translate text="Add Option" isPublicFacing=true}</button>
				</div>
			</div>
		{/if}
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