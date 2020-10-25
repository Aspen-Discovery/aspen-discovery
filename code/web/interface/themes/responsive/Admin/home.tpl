{strip}
<h1>{translate text="Aspen Discovery Administration"}</h1>
<div class="adminHome">
	{if !empty($error)}
		<div class="alert alert-danger">
			{$error|translate}
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
							<div class="adminSectionLabel row"><div class="col-tn-12">{$adminSection->label|translate}</div></div>
							<div class="adminSectionActions row">
								<div class="col-tn-12">
									{foreach from=$adminSection->actions item=action}
										<div class="adminAction row">
											<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
												<a href="{$action->link}" title="{translate text=$action->label inAttribute="true"}"><i class="fas fa-chevron-circle-right fa"></i></a>
											</div>
											<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
												<div class="adminActionLabel"><a href="{$action->link}">{$action->label|translate}</a></div>
												<div class="adminActionDescription">{$action->description|translate}</div>
											</div>
										</div>
										{foreach from=$action->subActions item=subAction}
											<div class="adminAction row">
												<div class="col-tn-2 col-xs-1 col-sm-2 col-md-1 adminActionLabel">
													<a href="{$subAction->link}" title="{translate text=$subAction->label  inAttribute="true"}"><i class="fas fa-chevron-circle-right fa"></i></a>
												</div>
												<div class="col-tn-10 col-xs-11 col-sm-10 col-md-11">
													<div class="adminActionLabel"><a href="{$subAction->link}">{$subAction->label|translate}</a></div>
													<div class="adminActionDescription">{$subAction->description|translate}</div>
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