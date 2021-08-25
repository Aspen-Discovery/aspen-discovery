{strip}
<h1>{translate text="Aspen Discovery Administration" isAdminFacing=true}</h1>
<div class="adminHome">
	{if !empty($error)}
		<div class="alert alert-danger">
			{translate text=$error isAdminFacing=true}
		</div>
	{else}
		<div id="adminSections" class="grid" data-colcade="columns: .grid-col, items: .grid-item">
			<!-- columns -->
			<div class="grid-col grid-col--1"></div>
			<div class="grid-col grid-col--2"></div>
			<!-- items -->
			{foreach from=$adminSections key="sectionId" item="adminSection"}
				{if $adminSection->hasActions()}
					<div class="adminSection grid-item" id="{$sectionId}">
						<div class="adminPanel">
							<div class="adminSectionLabel row"><div class="col-tn-12">{translate text=$adminSection->label isAdminFacing=true}</div></div>
							<div class="adminSectionActions row">
								<div class="col-tn-12">
									{foreach from=$adminSection->actions item=action}
										<div class="adminAction row">
											<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
												<a href="{$action->link}" title="{translate text=$action->label inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
											</div>
											<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
												<div class="adminActionLabel"><a href="{$action->link}">{translate text=$action->label  isAdminFacing=true}</a></div>
												<div class="adminActionDescription">{translate text=$action->description isAdminFacing=true}</div>
											</div>
										</div>
										{foreach from=$action->subActions item=subAction}
											<div class="adminAction row">
												<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
													<a href="{$subAction->link}" title="{translate text=$subAction->label inAttribute="true" isAdminFacing=true}"><i class="fas fa-chevron-circle-right fa"></i></a>
												</div>
												<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
													<div class="adminActionLabel"><a href="{$subAction->link}">{translate text=$subAction->label isAdminFacing=true}</a></div>
													<div class="adminActionDescription">{translate text=$subAction->description isAdminFacing=true}</div>
												</div>
											</div>
										{/foreach}
									{/foreach}
								</div>
							</div>
						</div>
					</div>
				{/if}
			{/foreach}
		</div>
	{/if}
</div>

{/strip}