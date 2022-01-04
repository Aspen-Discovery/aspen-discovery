{strip}
<div id="main-content">
	<div class="row" style="margin-top: 1em">
		<div class="col-xs-12">
			<div class="btn-group btn-group-sm">
				<a class="btn btn-default" href="/Admin/TwoFactorAuth?objectAction=edit&amp;id={$id}"><i class="fas fa-cog"></i> {translate text="Edit Settings" isAdminFacing=true}</a>
				<a class="btn btn-default" href='/Admin/TwoFactorAuth?objectAction=list'><i class="fas fa-list"></i> {translate text="Return to List" isAdminFacing=true}</a>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-xs-12">
			<h1>{translate text="Account Recovery" isAdminFacing=true}</h1>
			<p>{translate text="Generate a one-time use code to allow a user to regain access to their account." isAdminFacing=true}</p>
			<div class="row">
				<div class="col-xs-12">
					<label for="userId">{translate text="$usernameLabel" isPublicFacing=true isAdminFacing=true}</label>
					<div class="input-group input-group-lg">
						<input type="text" class="form-control" id="username">
						<span class="input-group-btn">
				        <button class="btn btn-default" type="button" onclick="return AspenDiscovery.Admin.createRecovery2FACode()">{translate text="Generate Code" isAdminFacing=true}</button>
				      </span>
					</div>
				</div>
			</div>
			<div id="generatedCode" class="alert alert-success" style="margin-top: 1em; display: none"></div>
			<div id="error" class="alert alert-danger" style="margin-top: 1em; display: none"></div>
		</div>
	</div>
</div>
{/strip}