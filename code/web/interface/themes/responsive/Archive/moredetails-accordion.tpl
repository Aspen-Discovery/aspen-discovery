{strip}
	<div id="more-details-accordion" class="panel-group">
		{foreach from=$moreDetailsOptions key="moreDetailsKey" item="moreDetailsOption"}
			<div class="panel{if $moreDetailsOption.openByDefault} active{/if}" id="{$moreDetailsKey}Panel"{if $moreDetailsOption.hideByDefault} style="display:none"{/if}>
				<a data-toggle="collapse" href="#{$moreDetailsKey}PanelBody">
					<div class="panel-heading">
						<div class="panel-title">
							{$moreDetailsOption.label}
						</div>
					</div>
				</a>
				<div id="{$moreDetailsKey}PanelBody" class="panel-collapse collapse{if $moreDetailsOption.openByDefault} in{/if}">
					<div class="panel-body">
						{if is_array($moreDetailsOption.body)}
							{foreach from=$moreDetailsOption.body item=bodyPart}
								<p>{$bodyPart}</p>
							{/foreach}
						{else}
							{$moreDetailsOption.body}
						{/if}
					</div>
					{if $moreDetailsOption.onShow}
						<script type="text/javascript">
							{literal}
							$('#{/literal}{$moreDetailsKey}Panel'){literal}.on('shown.bs.collapse', function () {
								{/literal}{$moreDetailsOption.onShow}{literal}
							});
							{/literal}
						</script>
					{/if}
				</div>
			</div>
		{/foreach}
	</div> {* End of tabs*}
{/strip}
{*
{literal}
	<script type="text/javascript">

	</script>
{/literal}
*}


