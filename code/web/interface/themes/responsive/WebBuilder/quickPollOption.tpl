<div class="form-group">
	<div class="col-xs-12">
		<div class="checkbox">
			<label for='pollOption_{$pollOption->id}'>{translate text="{$pollOption->label}" isPublicFacing=true}
				<input type="checkbox" name='pollOption[]' id='pollOption_{$pollOption->id}' value="{$pollOption->id}"/>
			</label>
		</div>
	</div>
</div>