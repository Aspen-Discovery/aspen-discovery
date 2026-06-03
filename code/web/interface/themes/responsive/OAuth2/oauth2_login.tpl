{strip}
<div class="container" style="max-width: 450px;">
	<div class="row">
	<div class="col-xs-12">
		<div class="text-center">
			<h1>{translate text="Login Required" isPublicFacing=true}</h1>
		</div>

		<div class="well well-lg" style="padding: 24px; margin-bottom: 24px;">
			<p>{translate text="To authorize access for %1%, you must first log in to your account." 1=$clientName isPublicFacing=true}</p>


			{if !empty($loginError)}
			<div class="alert alert-danger">
				<strong>{translate text="Error:" isPublicFacing=true}</strong> {$loginError}
			</div>
			{/if}

			<form method="POST" action="{$smarty.server.REQUEST_URI}">
				<div class="form-group">
					<label for="username">{translate text="$usernameLabel" isPublicFacing=true}</label>
					<input type="text"
						   id="username"
						   name="username"
						   class="form-control"
						   required
						   autofocus>
				</div>

				<div class="form-group">
					<label for="password">{translate text="$passwordLabel" isPublicFacing=true}</label>
					<input type="password"
						   id="password"
						   name="password"
						   class="form-control"
						   required>
				</div>

				<div class="alert alert-info" style="padding-top: 16px;">
					<p>{translate text="Only proceed with login if you trust the application requesting access: %1%" 1=$clientName isPublicFacing=true}</p>
					<p>{translate text="This login is only for authorizing the application to access your library account. Your credentials are not shared with the application." isPublicFacing=true}</p>
				</div>

				<div class="form-group">
					<button type="submit" class="btn btn-primary btn-block">
                        {translate text="Login" isPublicFacing=true}
					</button>
				</div>
			</form>
		</div>

	</div>
	</div>
</div>
{/strip}
