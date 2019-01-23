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
				<strong><u>Volume</u></strong>
			</div>
		{/if}
		<div class="col-tn-{if $showLastCheckIn && showVolume}3{elseif $showLastCheckIn || $showVolume}4{else}5{/if} ">
			<strong><u>Location</u></strong>
		</div>
		<div class="holdingsCallNumber col-tn-{if $showLastCheckIn || $showVolume}3{else}4{/if}">
			<strong><u>Call Number</u></strong>
		</div>
		<div class="col-tn-{if $showLastCheckIn && showVolume}2{elseif $showLastCheckIn || $showVolume}3{else}3{/if}">
			<strong><u>Status</u></strong>
		</div>
		{if $showLastCheckIn}
			<div class="col-tn-2">
				<strong><u>Last Check-In</u></strong>
			</div>
		{/if}
	</div>
{/strip}