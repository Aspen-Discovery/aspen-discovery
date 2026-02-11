{strip}
	<div id="main-content" class="col-md-12">
		<div class="row">
			<div class="col-xs-12">
				<h1>{$pageTitle}</h1>
				{if !empty($instructions)}
					<div class="alert alert-info">
						{$instructions}
					</div>
				{/if}
				
				{if !empty($updateMessage)}
					<div class="alert {if $updateMessageIsError}alert-danger{else}alert-success{/if}">
						{$updateMessage}
					</div>
				{/if}
			</div>
		</div>
		
		<div class="row">
			<div class="col-xs-12">
				<form method="post" class="form-horizontal">
					<div class="form-group">
						<label for="pluginPath" class="col-sm-3 control-label">Plugin Directory Path:</label>
						<div class="col-sm-9">
							<input type="text" class="form-control" id="pluginPath" name="pluginPath" 
								   placeholder="/path/to/plugin/directory" required>
							<p class="help-block">
								Enter the full path to the plugin directory containing the manifest.json file.
							</p>
						</div>
					</div>
					
					<div class="form-group">
						<div class="col-sm-offset-3 col-sm-9">
							<button type="submit" class="btn btn-primary">Install Plugin</button>
							<a href="/Admin/Plugins" class="btn btn-default">Cancel</a>
						</div>
					</div>
				</form>
			</div>
		</div>
		
		<div class="row">
			<div class="col-xs-12">
				<h3>Plugin Requirements</h3>
				<ul>
					<li>The plugin directory must contain a <code>manifest.json</code> file</li>
					<li>The manifest must include: name, slug, version, description, and author</li>
					<li>The plugin main class file should be named <code>[slug].php</code></li>
					<li>The plugin class should extend <code>AspenPlugin</code></li>
				</ul>
				
				<h4>Example manifest.json:</h4>
				<pre><code>{literal}{
  "name": "My Custom Plugin",
  "slug": "my_custom_plugin",
  "version": "1.0.0",
  "description": "A sample plugin for Aspen Discovery",
  "author": "Your Name",
  "hookPoints": ["injectJavaScript", "afterPageLoad"],
  "jsFiles": ["js/custom.js"],
  "cssFiles": ["css/custom.css"],
  "config": {
    "setting1": "default_value"
  }
}{/literal}</code></pre>
			</div>
		</div>
	</div>
{/strip} 