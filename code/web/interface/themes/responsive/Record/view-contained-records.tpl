{strip}
	<div class="striped">
		{foreach from=$childRecords item="childRecord"}
			<div class="row">
				<div class="col-xs-12">
					<a href="{$childRecord.link}">{$childRecord.label}</a>
				</div>
			</div>
		{/foreach}
	</div>
{/strip}