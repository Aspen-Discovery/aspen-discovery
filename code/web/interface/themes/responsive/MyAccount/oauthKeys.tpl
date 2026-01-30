{strip}
	<div id="main-content" class="col-md-12">
		<h1>{translate text="API Keys" isPublicFacing=true}</h1>

		{if !empty($error)}
			<div class="alert alert-danger">{$error}</div>
		{else}
			<p>{translate text="API keys allow you to access Aspen Discovery's API with your user permissions. Keys are tied to your account and should be kept secure." isPublicFacing=true}</p>
			<div class="alert alert-info">
				<strong>{translate text="Usage:" isPublicFacing=true}</strong> {translate text="Include your credentials in the Authorization header as:" isPublicFacing=true} <code>Authorization: Basic &lt;base64-encoded clientId:clientSecret&gt;</code>
			</div>

			<div class="btn-toolbar" style="margin-bottom: 15px;">
				<button class="btn btn-primary" onclick="AspenDiscovery.Account.showGenerateOAuthKeyForm()">
					<i class="fas fa-plus"></i> {translate text="Generate New API Key" isPublicFacing=true}
				</button>
			</div>

			{if $oauthKeys && count($oauthKeys) > 0}
				<table class="table table-striped table-bordered">
					<thead>
						<tr>
							<th>{translate text="Key Name" isPublicFacing=true}</th>
							<th>{translate text="Client ID" isPublicFacing=true}</th>
							<th>{translate text="Created" isPublicFacing=true}</th>
							<th>{translate text="Last Used" isPublicFacing=true}</th>
							<th>{translate text="Status" isPublicFacing=true}</th>
							<th>{translate text="Actions" isPublicFacing=true}</th>
						</tr>
					</thead>
					<tbody>
						{foreach from=$oauthKeys item=key}
							<tr id="oauth-key-{$key->id}">
								<td>{$key->keyName}</td>
								<td><code>{$key->clientId}</code></td>
								<td>{$key->created|date_format:"%Y-%m-%d %H:%M"}</td>
								<td>
									{if $key->lastUsed}
										{$key->lastUsed|date_format:"%Y-%m-%d %H:%M"}
									{else}
										{translate text="Never" isPublicFacing=true}
									{/if}
								</td>
								<td>
									<span id="status-{$key->id}" class="badge {if $key->isActive}badge-success{else}badge-secondary{/if}">
										{if $key->isActive}{translate text="Active" isPublicFacing=true}{else}{translate text="Inactive" isPublicFacing=true}{/if}
									</span>
								</td>
								<td>
									<div class="btn-group btn-group-sm">
										<button class="btn btn-sm btn-default" onclick="AspenDiscovery.Account.toggleOAuthKey({$key->id})" title="{translate text='Toggle Active/Inactive' inAttribute=true isPublicFacing=true}">
											<i class="fas fa-toggle-on"></i>
										</button>
										<button class="btn btn-sm btn-danger" onclick="AspenDiscovery.Account.revokeOAuthKey({$key->id})" title="{translate text='Delete Key' inAttribute=true isPublicFacing=true}">
											<i class="fas fa-trash"></i>
										</button>
									</div>
								</td>
							</tr>
						{/foreach}
					</tbody>
				</table>
			{else}
				<div class="alert alert-info">
					{translate text="You don't have any API keys yet. Generate one to get started." isPublicFacing=true}
				</div>
			{/if}
		{/if}
	</div>
{/strip}

{literal}
<script type="text/javascript">
	if (typeof AspenDiscovery.Account === 'undefined') {
		AspenDiscovery.Account = {};
	}

	AspenDiscovery.Account.showGenerateOAuthKeyForm = function() {
		var url = Globals.path + "/MyAccount/AJAX";
		var params = {method: "getGenerateOAuthKeyForm"};
		$.getJSON(url, params, function(data) {
			AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
		}).fail(AspenDiscovery.ajaxFail);
		return false;
	};

	AspenDiscovery.Account.generateOAuthKey = function() {
		var keyName = $('#keyName').val();
		if (!keyName) {
			AspenDiscovery.showMessage('Error', 'Please enter a key name');
			return false;
		}

		var url = Globals.path + "/MyAccount/AJAX";
		var params = {
			method: 'generateOAuthKey',
			keyName: keyName
		};

		$.getJSON(url, params, function(data) {
			if (data.success) {
				var credentials = data.clientId + ':' + data.clientSecret;
				var encodedCredentials = btoa(credentials);
				var messageBody = '<div class="alert alert-warning"><strong>Save these credentials now. You will not be able to see the secret again!</strong></div>' +
					'<div class="form-group">' +
					'<label>Client ID:</label>' +
					'<input type="text" class="form-control" value="' + data.clientId + '" readonly onclick="this.select()">' +
					'</div>' +
					'<div class="form-group">' +
					'<label>Client Secret:</label>' +
					'<input type="text" class="form-control" value="' + data.clientSecret + '" readonly onclick="this.select()">' +
					'</div>' +
					'<hr>' +
					'<div class="form-group">' +
					'<label>Authorization Header:</label>' +
					'<input type="text" class="form-control" value="Basic ' + encodedCredentials + '" readonly onclick="this.select()">' +
					'<p class="help-block">Use this value in the <code>Authorization</code> header when making API requests.</p>' +
					'</div>' +
					'<p class="help-block">Click the fields above to select and copy the values.</p>';
				AspenDiscovery.showMessage('API Key Generated', messageBody, false, true);
			} else {
				AspenDiscovery.showMessage('Error', data.message);
			}
		}).fail(AspenDiscovery.ajaxFail);

		return false;
	};

	AspenDiscovery.Account.toggleOAuthKey = function(keyId) {
		var url = Globals.path + "/MyAccount/AJAX";
		var params = {
			method: 'toggleOAuthKey',
			keyId: keyId
		};

		$.getJSON(url, params, function(data) {
			if (data.success) {
				var statusBadge = $('#status-' + keyId);
				if (data.isActive) {
					statusBadge.removeClass('badge-secondary').addClass('badge-success').text('Active');
				} else {
					statusBadge.removeClass('badge-success').addClass('badge-secondary').text('Inactive');
				}
				AspenDiscovery.showMessage('Success', data.message);
			} else {
				AspenDiscovery.showMessage('Error', data.message);
			}
		}).fail(AspenDiscovery.ajaxFail);

		return false;
	};

	AspenDiscovery.Account.revokeOAuthKey = function(keyId) {
		if (!confirm('Are you sure you want to delete this API key? This action cannot be undone.')) {
			return false;
		}

		var url = Globals.path + "/MyAccount/AJAX";
		var params = {
			method: 'revokeOAuthKey',
			keyId: keyId
		};

		$.getJSON(url, params, function(data) {
			if (data.success) {
				$('#oauth-key-' + keyId).fadeOut(function() {
					$(this).remove();
				});
				AspenDiscovery.showMessage('Success', data.message);
			} else {
				AspenDiscovery.showMessage('Error', data.message);
			}
		}).fail(AspenDiscovery.ajaxFail);

		return false;
	};
</script>
{/literal}
