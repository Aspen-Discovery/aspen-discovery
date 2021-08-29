{strip}
	<div>
		{if count($hooplaUsers) > 1} {* Linked Users contains the active user as well*}
			<div id='pickupLocationOptions' class="form-group">
				<label class="control-label" for="patronId">{translate text="Please choose the account to check out from" isPublicFacing=true}</label>
				<div class="controls">
					<select name="patronId" id="patronId" class="form-control">
						{foreach from=$hooplaUsers item=tmpUser}
						{assign var="userId" value=$tmpUser->id}
							<option value="{$tmpUser->id}">
								{$tmpUser->getNameAndLibraryLabel()}
								{if !empty($hooplaUserStatuses[$userId])}
									{assign var="hooplaPatronStatus" value=$hooplaUserStatuses[$userId]}
									&nbsp;{translate text="(%1% check outs remaining this month)" 1=$hooplaPatronStatus->numCheckoutsRemaining isPublicFacing=true}
								{else}
									&nbsp;{translate text="(no Hoopla account)" isPublicFacing=true}
								{/if}
							</option>
						{/foreach}
					</select>
				</div>
			</div>
		{/if}
	</div>
{/strip}