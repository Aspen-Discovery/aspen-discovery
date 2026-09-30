<div class="container" style="max-width: 450px;">
	<div class="row">
		<div class="col-xs-12">
			<div class="text-center">
				<h1>
					{$client->name}
				</h1>
				<p>
                    {translate text="wants to access your library account" isPublicFacing=true}
				</p>
			</div>

			<div class="well well-lg" style="padding: 24px; margin-bottom: 24px;">
				<div style="display: flex; align-items: center">
					<div style="background-color: #fff; width: 40px; height: 40px; border-radius: 50%; margin-right: 16px; display: flex; align-items: center; justify-content: center;">
						<i class="fas fa-user" role="presentation" style="font-size: 18px;"></i>
					</div>
					<div>
						<div>
							{$user->displayName}
						</div>
						<div style="margin-top: 2px;">
							{$user->username}
						</div>
					</div>
				</div>

				{if $consentChanged}
					<hr>
					<div class="alert alert-warning" style="margin-bottom: 20px;">
						<p>
							<strong>{translate text="This application's requested permissions have changed since you last approved it." isPublicFacing=true}</strong>
						</p>
						<p style="margin-bottom: 0;">
							{translate text="Please review the updated access below before authorizing again." isPublicFacing=true}
						</p>
					</div>
				{/if}

				{if !empty($scopes)}
					<hr>
				<div style="margin-bottom: 20px;">
					<div style="margin-bottom: 12px;">
                        {translate text="This will allow %1% to:" 1=$client->name isPublicFacing=true}
					</div>
					<ul style="margin: 0; padding-left: 20px; list-style: none;">
						{foreach from=$scopes key=scopeId item=scopeLabel}
							<li style="margin-bottom: 8px; padding-left: 24px; position: relative;">
								<i class="fas fa-check-circle text-info" style="position: absolute; left: 0; margin-top: 1px;"></i>
								{$scopeLabel}
							</li>
						{/foreach}
					</ul>
				</div>
				{/if}

				{if $consentChanged && (!empty($newScopes) || !empty($removedScopes))}
					<hr>
					<div style="margin-bottom: 20px;">
						<div style="margin-bottom: 12px;">
							{translate text="What's changed since your last consent:" isPublicFacing=true}
						</div>
						{if !empty($newScopes)}
							<div style="margin-bottom: 12px;">
								<strong>{translate text="New permissions requested" isPublicFacing=true}</strong>
								<ul style="margin: 8px 0 0; padding-left: 20px; list-style: none;">
									{foreach from=$newScopes key=scopeId item=scopeLabel}
										<li style="margin-bottom: 6px; padding-left: 24px; position: relative;">
											<i class="fas fa-plus-circle text-warning" style="position: absolute; left: 0; margin-top: 1px;"></i>
											{$scopeLabel}
										</li>
									{/foreach}
								</ul>
							</div>
						{/if}
						{if !empty($removedScopes)}
							<div>
								<strong>{translate text="Previously approved, but no longer requested" isPublicFacing=true}</strong>
								<ul style="margin: 8px 0 0; padding-left: 20px; list-style: none;">
									{foreach from=$removedScopes key=scopeId item=scopeLabel}
										<li style="margin-bottom: 6px; padding-left: 24px; position: relative;">
											<i class="fas fa-minus-circle text-muted" style="position: absolute; left: 0; margin-top: 1px;"></i>
											{$scopeLabel}
										</li>
									{/foreach}
								</ul>
							</div>
						{/if}
					</div>
				{/if}
				<hr>
				<div style="padding-top: 16px;">
					<div>
                        {translate text="By clicking 'Authorize', you allow %1% to use your library account information in accordance with its terms of service and privacy policy." 1=$client->name isPublicFacing=true}
					</div>
				</div>

			</div>

			<form method="POST" action="{$authorizationUrl}">
				<div style="display: flex; gap: 12px; padding-bottom: 20px">
					<button type="submit" name="approve" value="no" class="btn btn-link" style="flex: 1;">
                        {translate text="Cancel" isPublicFacing=true}
					</button>
					<button type="submit" name="approve" value="yes" class="btn btn-primary" style="flex: 1;">
                        {translate text="Authorize" isPublicFacing=true}
					</button>
				</div>
			</form>

		</div>
	</div>
</div>
