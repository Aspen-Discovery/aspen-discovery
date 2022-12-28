{strip}
	<div id='checkInGrid'>
		{foreach from=$checkInGrid item=checkInCell}
			<div class='checkInCell'>
				<div class='issueInfo'>{$checkInCell.issueDate}{if !empty($checkInCell.issueNumber)} ({$checkInCell.issueNumber}){/if}</div>
				<div class='status'><span class="{$checkInCell.class}">{$checkInCell.status}</span> on {$checkInCell.statusDate}</div>
				{if !empty($checkInCell.copies)}
					<div class='copies'>{$checkInCell.copies} {if $checkInCell.copies > 1}{translate text="Copies" isPublicFacing=true}{else}{translate text="Copy" isPublicFacing=true}{/if}</div>
				{/if}
			</div>
		{/foreach}
	</div>
	<div class="clearfix"></div>
{/strip}