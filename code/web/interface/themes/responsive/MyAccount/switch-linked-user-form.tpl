{strip}
	{* Supply $label & $actionPath for this template *}

	{if !empty($linkedUsers) && count($linkedUsers) > 1} {* Linked Users contains the active user as well *}
		<div class="row">
			<div class="col-tn-12">
				<form action="{$actionPath}" method="get" class="form form-inline">
					<div id="linkedUserOptions" class="form-group">
						<label class="control-label" for="patronId">{translate text=$label isPublicFacing=true}&nbsp;</label>

						<select name="patronId" id="patronId" class="form-control" onchange="AspenDiscovery.Account.changeLinkedAccount()">
							{foreach from=$linkedUsers item=tmpUser}
								<option value="{$tmpUser->id}" {if $selectedUser == $tmpUser->id}selected="selected"{/if}>{$tmpUser->displayName} - {$tmpUser->getHomeLibrarySystemName()}</option>
							{/foreach}
						</select>
					</div>
				</form>
			</div>
		</div>
		<br/>
	{/if}

{/strip}