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
								Enter the full path to the plugin directory containing the PHP plugin file.
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
					<li>The plugin directory must contain a PHP file with a class extending <code>AspenPlugin</code></li>
					<li>The plugin class must implement <code>getMetadata()</code> method with: name, version, description, and author</li>
					<li>The plugin class must implement <code>getSlug()</code> method returning a unique identifier</li>
					<li>All metadata is now defined in PHP methods - no manifest.json needed!</li>
				</ul>
				
				<h4>Example Plugin Class:</h4>
				<pre><code>{literal}<?php
class MyCustomPlugin extends AspenPlugin {
    public function getMetadata(): array {
        return [
            'name' => 'My Custom Plugin',
            'version' => '1.0.0',
            'description' => 'Description of the plugin',
            'author' => 'Your Name',
            'dateCreated' => '2025-01-26',
            'lastModified' => '2025-01-26',
            'minAspenVersion' => '24.01.00',
            'maxAspenVersion' => '25.12.99', // or null for no limit
        ];
    }

    public function getSlug(): string {
        return 'my_custom_plugin';
    }

    public function getJavaScriptFiles(): array {
        return ['js/custom.js'];
    }

    public function getCssFiles(): array {
        return ['css/custom.css'];
    }

    // Hook methods are auto-detected
    public function injectJavaScript(array $data): ?string {
        return "console.log('My plugin loaded');";
    }
}{/literal}</code></pre>
			</div>
		</div>
	</div>
{/strip} 