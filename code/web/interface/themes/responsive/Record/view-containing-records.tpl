{strip}
	<div class="striped">
		{foreach from=$parentRecords item="parentRecord"}
			<div class="row">
				<div class="col-xs-12">
					<a href="{$parentRecord.link}">{$parentRecord.label}</a>
				</div>
			</div>
		{/foreach}
	</div>
{/strip}