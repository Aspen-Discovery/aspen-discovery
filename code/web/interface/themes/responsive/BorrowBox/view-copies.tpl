{strip}
{if !empty($showAvailability) && $availability}
	<div>
		<table class="holdingsTable table table-striped table-responsive">
			<thead>
				<tr>
					<th>{translate text="Collection" isPublicFacing=true}</th>
					<th>{translate text="Status" isPublicFacing=true}</th>
					<th>{translate text="Next Available" isPublicFacing=true}</th>
				</tr>
			</thead>
			<tbody>
				{foreach from=$availability item="availabilityRow"}
					<tr>
						<td>{$availabilityRow->getSettingName()}</td>
						<td>{$availabilityRow->availabilityStatus}</td>
						<td>{if $availabilityRow->nextAvailableDate}{$availabilityRow->nextAvailableDate|format_date_locale}{else}-{/if}</td>
					</tr>
				{/foreach}
			</tbody>
		</table>
	</div>
{/if}
{/strip}
