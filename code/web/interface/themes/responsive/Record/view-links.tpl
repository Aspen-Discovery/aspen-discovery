{strip}
	<div class="striped">
		{foreach from=$links item="link"}
			<div class="row">
				<div class="col-xs-12">
					<a href="{$link.url}" target="_blank"><i class="fas fa-external-link-alt"></i>  {$link.title}</a>
				</div>
			</div>
		{/foreach}
	</div>
{/strip}