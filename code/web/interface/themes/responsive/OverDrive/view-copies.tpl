{strip}
{* Add availability as needed *}
{if $showAvailability && $availability && count($availability) > 0}
	<div>
		<table class="holdingsTable">
			<thead>
				<tr><th>{translate text="Library" isPublicFacing=true}</th><th>{translate text="Owned" isPublicFacing=true}</th><th>{translate text="Available" isPublicFacing=true}</th><th>{translate text="Shared?" isPublicFacing=true}</th></tr>
			</thead>
			<tbody>
				{foreach from=$availability item=availabilityItem}
					<tr><td>{$availabilityItem->getLibraryName()}</td><td>{$availabilityItem->copiesOwned}</td><td>{$availabilityItem->copiesAvailable}</td><td>{if $availabilityItem->shared}{translate text="Yes" isPublicFacing=true}{else}{translate text="No" isPublicFacing=true}{/if}</td></tr>
				{/foreach}
			</tbody>
		</table>
	</div>
{/if}
{if $showAvailabilityOther && $availabilityOther && count($availabilityOther) > 0}
	<div>
		<h3>{translate text="Other Libraries that own this title" isPublicFacing=true}</h3>
		<table class="holdingsTable">
			<thead>
			<tr><th>{translate text="Library" isPublicFacing=true}</th><th>{translate text="Owned" isPublicFacing=true}</th><th>{translate text="Available" isPublicFacing=true}</th><th>{translate text="Shared?" isPublicFacing=true}</th></tr>
			</thead>
			<tbody>
			{foreach from=$availabilityOther item=availabilityItem}
				<tr><td>{$availabilityItem->getLibraryName()}</td><td>{$availabilityItem->copiesOwned}</td><td>{$availabilityItem->copiesAvailable}</td><td>{if $availabilityItem->shared}{translate text="Yes" isPublicFacing=true}{else}{translate text="No" isPublicFacing=true}{/if}</td></tr>
			{/foreach}
			</tbody>
		</table>
		<br/>
		<div class="note">
			{if strcasecmp($source, 'OverDrive') == 0}
				{translate text="Note: Copies owned by the Digital library are available to patrons of any Marmot Library.  Titles owned by a specific library are only available for use by patrons of that library." isPublicFacing=true}
			{/if}
		</div>
	</div>
{/if}
{if $numberOfHolds > 0}
	<p>
		{if $numberOfHolds == 1}
			{translate text="There is 1 hold on this title." isPublicFacing=true}
		{else}
			{translate text="There are %1% holds on this title." 1=$numberOfHolds isPublicFacing=true}
		{/if}
	</p>
{/if}
{/strip}