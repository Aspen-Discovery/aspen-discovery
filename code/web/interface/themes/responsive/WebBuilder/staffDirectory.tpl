<div class="col-xs-12">
	<h1>{translate text="Staff Directory" isPublicFacing=true}</h1>

	{foreach from=$staffMembers item=staffMember}
		<div class="staff-member">
			{if $hasPhotos}
				<div class="row">
					<div class="coversColumn col-xs-4 col-sm-3 col-md-2 text-center">
						{if !empty($staffMember->photo)}
							<img src="/files/thumbnail/{$staffMember->photo}" class="listResultImage img-thumbnail" alt="{translate text='Staff Picture' inAttribute=true isPublicFacing=true}">
						{/if}
					</div>
					<div class="col-xs-8 col-sm-9 col-md-10">
			{/if}
			<div class="row">
				<div class="col-xs-12 staff-name"><strong>{$staffMember->name}</strong></div>
			</div>
			{if $staffMember->role}
				<div class="row">
					<div class="col-xs-3 result-label">{translate text="Job Title" isPublicFacing=true}</div>
					<div class="col-xs-9 result-value">{$staffMember->role}</div>
				</div>
			{/if}
			{if $staffMember->description}
				<div class="row">
					<div class="col-xs-3 result-label"></div>
					<div class="col-xs-9 result-value">
						{$staffMember->getFormattedDescription()}
					</div>
				</div>
			{/if}
			{if $staffMember->email}
				<div class="row">
					<div class="col-xs-3 result-label">{translate text="Email" isPublicFacing=true}</div>
					<div class="col-xs-9 result-value">
						{mailto address=$staffMember->email encode="javascript"}
					</div>
				</div>
			{/if}
			{if $staffMember->phone}
				<div class="row">
					<div class="col-xs-3 result-label">{translate text="Phone" isPublicFacing=true}</div>
					<div class="col-xs-9 result-value">
						{$staffMember->phone}
					</div>
				</div>
			{/if}
			{if $hasPhotos}
					</div>
				</div>
			{/if}
		</div>
	{/foreach}
</div>