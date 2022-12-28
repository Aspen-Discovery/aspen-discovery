{strip}
{* Add availability as needed *}
{if !empty($showAvailability) && $availability}
	<div>
		<table class="holdingsTable">
			<thead>
				<tr><th>{translate text="Library" isPublicFacing=true}</th><th>{translate text="Owned" isPublicFacing=true}</th><th>{translate text="Available" isPublicFacing=true}</th></tr>
			</thead>
			<tbody>
				<tr><td>{$availability->getLibraryName()}</td><td>{$availability->copiesOwned}</td><td>{$availability->copiesAvailable}</td></tr>
			</tbody>
		</table>
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