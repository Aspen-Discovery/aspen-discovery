{strip}
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
	{if $poll->status == 3}
		{* Poll is closed *}
		<div class="alert alert-info">
            {translate text='This poll is no longer accepting submissions.' isPublicFacing=true}
		</div>
	{elseif $poll->status == 1 && !$poll->userCanAccess()}
        {* Poll is being created *}
		<div class="alert alert-info">
            {translate text='This poll is not yet accepting submissions.' isPublicFacing=true}
		</div>
	{else}
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
		<form id="quickPoll{$id}" class="form-horizontal" role="form" action="/WebBuilder/SubmitQuickPoll"  onsubmit="setFormSubmitting();" method="post">
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
				<label class="control-label" for="name">{translate text="Your Name" isAdminFacing=true} {if $poll->requireName == 1}<span class="required-input">*</span>{/if}</label>
				<input type="text" class="form-control {if $poll->requireName == 1}required{/if}" name="name" id="name" value="">
			</div>

			<div class="form-group">
				<label class="control-label" for="email">{translate text="Email" isAdminFacing=true} {if $poll->requireEmail == 1}<span class="required-input">*</span>{/if}</label>
				<input type="email" class="form-control {if $poll->requireEmail == 1}required{/if}" name="email" id="email" value="">
			</div>

            {if !empty($captcha)}
                {* Show Recaptcha spam control if set. *}
				<div class="form-group">
                    {$captcha}
				</div>
            {/if}

			<div class="form-group">
				<div class="col-xs-12">
					<input type="submit" name="submit" value="{translate text="Submit" inAttribute=true isAdminFacing=true}" class="btn btn-primary">
				</div>
			</div>

		</form>
    {/if}
</div>
{/strip}

{if !empty($captcha)}
{literal}
	<script type="text/javascript">
		var onloadCallback = function() {
			var captchas = document.getElementsByClassName("g-recaptcha");
			for(var i = 0; i < captchas.length; i++) {
				grecaptcha.render(captchas[i], {'sitekey' : '{/literal}{$captchaKey}{literal}'});
			}
		};
	</script>
{/literal}
{/if}