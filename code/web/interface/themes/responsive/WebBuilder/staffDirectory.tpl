<div class="col-xs-12">
	<h1>{translate text="Staff Directory"}</h1>

	{foreach from=$staffMembers item=staffMember}
		<div class="staff-member">
			<div class="row">
				{if $staffMember->photo}
					{* TODO: Show photos *}
				{/if}
				<div class="col-xs-12 staff-name"><strong>{$staffMember->name}</strong> {if $staffMember->role}({$staffMember->role}){/if}</div>
			</div>
			{if $staffMember->email}
				<div class="row">
					<div class="col-xs-11 col-xs-offset-1">
						<a href="mailto:{$staffMember->email}">Email</a>
					</div>
				</div>
			{/if}
			{if $staffMember->phone}
				<div class="row">
					<div class="col-xs-11 col-xs-offset-1">
						{$staffMember->phone}
					</div>
				</div>
			{/if}
		</div>
	{/foreach}
</div>