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
				<strong><u>{translate text="Volume" isPublicFacing=true}</u></strong>
			</th>
		{/if}
		<th>
			<strong><u>{translate text="Location" isPublicFacing=true}</u></strong>
		</th>
		<th>
			<strong><u>{translate text="Call Number" isPublicFacing=true}</u></strong>
		</th>
		{if $hasNote}
			<th>
				<strong><u>{translate text="Note" isPublicFacing=true}</u></strong>
			</th>
		{/if}
		<th>
			<strong><u>{translate text="Status" isPublicFacing=true}</u></strong>
		</th>
		{if $hasDueDate && $showItemDueDates}
			<th>
				<strong><u>{translate text="Due Date" isPublicFacing=true}</u></strong>
			</th>
		{/if}
		{if $showLastCheckIn}
			<th>
				<strong><u>{translate text="Last Check-In" isPublicFacing=true}</u></strong>
			</th>
		{/if}
	</tr>
</thead>
{/strip}