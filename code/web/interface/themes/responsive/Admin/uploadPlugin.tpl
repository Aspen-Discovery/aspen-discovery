{strip}
	<div id="main-content" class="col-md-12">
		<h1>{translate text="Upload Plugin" isAdminFacing=true}</h1>
		
		{if !empty($instructions)}
			<div class="alert alert-info">
				{$instructions}
			</div>
		{/if}

		<form method="post" enctype="multipart/form-data" class="form">
			<fieldset class="adminForm">
				<legend>{translate text="Plugin File Upload" isAdminFacing=true}</legend>
				
				<div class="form-group">
					<label for="pluginFile" class="control-label">
						{translate text="Plugin File (.plugzip)" isAdminFacing=true}
					</label>
					<input type="file" name="pluginFile" id="pluginFile" accept=".plugzip" required class="form-control">
					<div class="help-block">
						{translate text="Select a .plugzip file to upload and install" isAdminFacing=true}
					</div>
				</div>
				
				<div class="form-group">
					<button type="submit" name="submit" class="btn btn-primary">
						{translate text="Upload and Install Plugin" isAdminFacing=true}
					</button>
					<a href="/Admin/Plugins" class="btn btn-default">
						{translate text="Cancel" isAdminFacing=true}
					</a>
				</div>
			</fieldset>
		</form>

		<div class="alert alert-warning">
			<h4>{translate text="Plugin Requirements" isAdminFacing=true}</h4>
			<ul>
				<li>{translate text="Plugin must be packaged as a .plugzip file" isAdminFacing=true}</li>
				<li>{translate text="Plugin must contain a PHP class extending AspenPlugin" isAdminFacing=true}</li>
				<li>{translate text="Plugin slug must be unique (not already installed)" isAdminFacing=true}</li>
				<li>{translate text="Only upload plugins from trusted sources" isAdminFacing=true}</li>
			</ul>
		</div>

		<div class="alert alert-info">
			<h4>{translate text="Creating Plugin Packages" isAdminFacing=true}</h4>
			<p>{translate text="To create a .plugzip file from a plugin directory:" isAdminFacing=true}</p>
			<ol>
				<li>{translate text="Ensure your plugin directory contains a PHP file with AspenPlugin class" isAdminFacing=true}</li>
				<li>{translate text="Create a ZIP archive of the entire plugin directory" isAdminFacing=true}</li>
				<li>{translate text="Rename the .zip extension to .plugzip" isAdminFacing=true}</li>
			</ol>
		</div>
	</div>
{/strip} 