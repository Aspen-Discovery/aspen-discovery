{strip}
	<div class="form-group row">
		<div class="col-sm-12 text-right">
			{*Allows users to leave the workflow entirely*}
			<a href="/MyAccount/Home" class="btn {if $currentStep.name == "done"}btn-default{else}btn-danger{/if}">
				{if $currentStep.name == "done"}
					{translate text="Finish" isPublicFacing=true}
				{else}
					{translate text="Cancel" isPublicFacing=true}
				{/if}
			</a>
			{*Allows users to move backwards in the flow once started*}
			{if $currentStep.name != "start" && $currentStep.name != "done"}
			<button type="submit" name="navigation" value="back" class="btn btn-default">{translate text="Back" isPublicFacing=true}</button>
			{/if}
			{*Allows users to move forwards in the flow*}
			{if $currentStep.name != "done"}
				<button type="submit" name="navigation" value="next" id="continueButton" class="btn btn-primary" {if $currentStep.isInformationStep}disabled{/if}>
					{if $currentStep.name == "submit"}
						{translate text="Submit Application" isPublicFacing=true}
					{elseif $currentStep.name == "verifyContactInformation"}
						{translate text="Submit account renewal request" isPublicFacing=true}
					{else}
						{translate text="Continue" isPublicFacing=true}
					{/if}
				</button>
			{/if}
		</div>
	</div>
	{* Hidden field to manage state. *}
	<input type="hidden" name="currentStep" value="{$currentStep.name|escape}">
{/strip}
