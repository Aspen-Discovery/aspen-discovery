{strip}
	{* resize the columns when  including the lastcheckin box
 xs-5 : 41.6667%
 xs-4 : 33.3333%  (1/3)
 xs-3 : 25%       (1/4)
 xs-2 : 16.6667% (1/6)
 *}
	<div class="row">
		{if $showVolume}
			<div class="col-tn-2">
				{if $holding.volume}
					<span title="Volume">{$holding.volume}</span>
				{/if}
			</div>
		{/if}
		<div class="col-tn-{if $showLastCheckIn && showVolume}3{elseif $showLastCheckIn || $showVolume}4{else}5{/if}">
			<strong>
				{$holding.shelfLocation|escape}
				{if $holding.locationLink} (<a href='{$holding.locationLink}' target="_blank">Map</a>){/if}
			</strong>
		</div>
		<div class="holdingsCallNumber col-tn-{if $showLastCheckIn || $showVolume}3{else}4{/if}">
			{$holding.callNumber|escape}
			{if $holding.link}
				{foreach from=$holding.link item=link}
					<a href='{$link.link}' target="_blank">{$link.linkText}</a><br>
				{/foreach}
			{/if}
		</div>
		<div class="col-tn-{if $showLastCheckIn && showVolume}2{elseif $showLastCheckIn || $showVolume}3{else}3{/if}">
			{if $holding.reserve == "Y"}
				{translate text="On Reserve - Ask at Circulation Desk"}
			{else}
				<span class="{if $holding.availability}available{else}checkedout{/if}">
					{if $holding.onOrderCopies > 1}{$holding.onOrderCopies}&nbsp;{/if}
					{$holding.statusFull|translate}{if $holding.holdable == 0 && $showHoldButton} <label class='notHoldable' title='{$holding.nonHoldableReason}'>(Not Holdable)</label>{/if}
				</span>
			{/if}
		</div>
		{if $showLastCheckIn}
			<div class="col-tn-2">
				{if $holding.lastCheckinDate && $holding.available}
					{* for debugging: *}
					{*{$holding.lastCheckinDate}<br>*}
					{*{$holding.lastCheckinDate|date_format}<br>*}

					<span title="Last Check-in Date">{$holding.lastCheckinDate|date_format}</span>
				{/if}
			</div>
		{/if}
	</div>
{/strip}