{strip}
	<div class="striped">
		{foreach from=$links item="link"}
			<div class="row">
				<div class="col-xs-12">
					{if !empty($link.requiresLogin)}
						<span class="loginRequiredLink"><i class="fas fa-lock" role="presentation"></i>  {$link.title} &ndash; {translate text="Please log in to view this resource" isPublicFacing=true}</span>
					{else}
						<a href="{$link.url}" target="_blank"><i class="fas fa-external-link-alt" role="presentation"></i>  {$link.title}</a>
					{/if}
				</div>
			</div>
		{/foreach}
	</div>
{/strip}