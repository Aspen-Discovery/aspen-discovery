<div class="col-xs-12">
	<h1>{translate text="Staff Directory"}</h1>

	{foreach from=$staffMembers item=staffMember}
		<div class="staff-member">
			<div class="row">
				{if $staffMember->photo}
					{* TODO: Show photos *}
				{/if}
				<div class="col-xs-12 staff-name"><strong>{$staffMember->name}</strong></div>
			</div>
			{if $staffMember->role}
				<div class="row">
					<div class="col-xs-3 result-label">{translate text="Job Title"}</div>
					<div class="col-xs-9 result-value">{$staffMember->role}</div>
				</div>
			{/if}
			{if $staffMember->description}
				<div class="row">
					<div class="col-xs-3 result-label"></div>
					<div class="col-xs-9 result-value">
						{$staffMember->description}
					</div>
				</div>
			{/if}
			{if $staffMember->email}
				<div class="row">
					<div class="col-xs-3 result-label">{translate text="Email"}</div>
					<div class="col-xs-9 result-value">
						{mailto address=$staffMember->email encode="javascript"}
					</div>
				</div>
			{/if}
			{if $staffMember->phone}
				<div class="row">
					<div class="col-xs-3 result-label">{translate text="Phone"}</div>
					<div class="col-xs-9 result-value">
						{$staffMember->phone}
					</div>
				</div>
			{/if}

		</div>
	{/foreach}
</div>