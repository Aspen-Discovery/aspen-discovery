{strip}
	{* Supply $label & $actionPath for this template *}

	{if !empty($linkedUsers) && count($linkedUsers) > 1} {* Linked Users contains the active user as well *}
		<form action="{$path}{$actionPath}" method="get" class="form form-inline">
			<div id="linkedUserOptions" class="form-group">
				<label class="control-label" for="account">{translate text="$label"}: </label>
				<div class="controls">
					<select name="patronId" id="patronId" class="form-control">
						{foreach from=$linkedUsers item=tmpUser}
							<option value="{$tmpUser->id}" {if $selectedUser == $tmpUser->id}selected="selected"{/if}>{$tmpUser->displayName} - {$tmpUser->getHomeLibrarySystemName()}</option>
						{/foreach}
					</select>
					<button type="submit" class="btn btn-default">Change Account</button>
				</div>
			</div>
		</form>
	{/if}

{/strip}