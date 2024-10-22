{strip}
	<div id="main-content" class="col-md-12">
		<h1>Symphony API Tester</h1>
		<h2>Get Endpoint documentation</h2>
		<form role="form" id="describeCallForm">
			<div class="form-group">
				<label for="pathToDescribe">Path to describe</label>
				<input  type="text" name="pathToDescribe" id="pathToDescribe" class="form-control" value="{if !empty($pathToDescribe)}{$pathToDescribe}{else}user/patron{/if}"/>
				<div class='propertyDescription'><em>Enter the path to describe, i.e. user/patron The /describe at the end of the path should be omitted and there should not be a leading /. </em></div>
			</div>
			<div class="form-group">
				<div class="input-group input-group-sm">
					<button type="submit" class="btn btn-sm btn-primary">Describe</button>
				</div>
			</div>
		</form>
		{if !empty($describeResults)}
			<pre>
				{$describeResults|print_r}
			</pre>
		{/if}

		<h2>GET request</h2>
		<form role="form" id="describeCallForm">
			<div class="form-group">
				<label for="getRequest">GET request</label>
				<input  type="text" name="getRequest" id="getRequest" class="form-control" value="{if !empty($getRequest)}{$getRequest}{/if}"/>
				<div class='propertyDescription'><em>Enter the path to retrieve. I.e., catalog/item/barcode/<em>barcode</em> there should not be a leading /. </em></div>
			</div>
			<div class="form-group">
				<div class="input-group input-group-sm">
					<button type="submit" class="btn btn-sm btn-primary">GET</button>
				</div>
			</div>
		</form>
        {if !empty($getRequestResults)}
			<pre>
				{$getRequestResults|print_r}
			</pre>
        {/if}
	</div>
{/strip}