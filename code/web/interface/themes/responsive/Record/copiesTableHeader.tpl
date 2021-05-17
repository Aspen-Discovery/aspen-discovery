{strip}
	{* resize the columns when  including the lastcheckin box
 xs-5 : 41.6667%
 xs-4 : 33.3333%  (1/3)
 xs-3 : 25%       (1/4)
 xs-2 : 16.6667% (1/6)
 *}
<thead>
	<tr>
		{if $showVolume}
			<th>
				<strong><u>{translate text="Volume"}</u></strong>
			</th>
		{/if}
		<th>
			<strong><u>{translate text="Location"}</u></strong>
		</th>
		<th>
			<strong><u>{translate text="Call Number"}</u></strong>
		</th>
		{if $hasNote}
			<th>
				<strong><u>{translate text="Note"}</u></strong>
			</th>
		{/if}
		<th>
			<strong><u>{translate text="Status"}</u></strong>
		</th>
		{if $hasDueDate}
			<th>
				<strong><u>{translate text="Due Date"}</u></strong>
			</th>
		{/if}
		{if $showLastCheckIn}
			<th>
				<strong><u>{translate text="Last Check-In"}</u></strong>
			</th>
		{/if}
	</tr>
</thead>
{/strip}