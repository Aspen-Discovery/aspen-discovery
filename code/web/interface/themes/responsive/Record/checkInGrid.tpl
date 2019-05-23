{strip}
	<div id='checkInGrid'>
		{foreach from=$checkInGrid item=checkInCell}
			<div class='checkInCell'>
				<div class='issueInfo'>{$checkInCell.issueDate}{if $checkInCell.issueNumber} ({$checkInCell.issueNumber}){/if}</div>
				<div class='status'><span class="{$checkInCell.class}">{$checkInCell.status}</span> on {$checkInCell.statusDate}</div>
				{if $checkInCell.copies}
					<div class='copies'>{$checkInCell.copies} {if $checkInCell.copies > 1}Copies{else}Copy{/if}</div>
				{/if}
			</div>
		{/foreach}
	</div>
	<div class="clearfix"></div>
{/strip}