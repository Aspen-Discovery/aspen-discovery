{strip}
<div class="form-group">
	<div class="col-xs-12">
		{if $poll->allowMultipleSelections}
			<div class="checkbox">
				<label for='pollOption_{$pollOption->id}'>{translate text=$pollOption->label isPublicFacing=true}
					<input type="checkbox" name='pollOption[]' id='pollOption_{$pollOption->id}' value="{$pollOption->id}"/>
				</label>
			</div>
		{else}
			<div class="radio">
				<label>
					<input type="radio" name="pollOption" id="pollOption_{$pollOption->id}" value="{$pollOption->id}">
					{translate text=$pollOption->label isPublicFacing=true}
				</label>
			</div>
		{/if}
	</div>
</div>
{/strip}