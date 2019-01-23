<div class="col-sm-12">
	<h1>Inventory</h1>
	{if $error}<p class="error">{$error}</p>{/if} 
	<form action="" method="post" class="form-inline" role="form">
		<fieldset>
			<div class="row">
				<div class="col-xs-12">
					<div class="form-group">
						<label for="login" class="sr-only">Login</label>
						<input type="text" name="login" id="login" value="{$lastLogin}" placeholder="Login"/>
					</div>
					<div class="form-group">
						<label for="password1" class="sr-only">Password</label>
						<input type="password" name="password1" id="password1" value="{$lastPassword1}" placeholder="Password"/>
					</div>
					{*
					<div class="sidebarLabel"><label for="initials">Initials</label></div>
					<div class="sidebarValue"><input type="text" name="initials" id="initials" value="{$lastInitials}"/> </div>
					<div class="sidebarLabel"><label for="password2">Password</label></div>
					<div class="sidebarValue"><input type="password" name="password2" id="password2" value="{$lastPassword2}"/></div>
					*}
					<div class="form-group">
						<label for="updateIncorrectStatuses"><input type="checkbox" id="updateIncorrectStatuses" name="updateIncorrectStatuses" {if $lastUpdateIncorrectStatuses}checked="checked"{/if}/> Auto correct status</label>
					</div>
					<div class="form-group">
						<input type="submit" name="submit" value="Submit Inventory" class="btn btn-primary"/>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-4">
					<h2>Barcode Entry</h2>
					<div>
						<textarea rows="20" cols="20" name="barcodes" id="barcodes" class="input-xxlarge"></textarea>
					</div>
				</div>
				<div class="col-md-8">
					<h2>Inventory Results</h2>
					{if $results}
						<div id="circaResults" {if $results.success == false}class="error"{/if}>
							{if $results.success == false}
								Error processing inventory.  {$results.message}
							{else}
								Successfully processed inventory.
								<table class="table table-striped">
									<thead>
									<tr>
										<th>Barcode</th><th>Title</th><th>Call Number</th><th>Result</th>
									</tr>
									</thead>
									<tbody>
									{foreach from=$results.barcodes item=barcodeInfo key=barcode}
										<tr>
											<td>{$barcode}</td>
											<td>{$barcodeInfo.title}</td>
											<td>{$barcodeInfo.callNumber}</td>
											<td style="color:{if $barcodeInfo.needsAdditionalProcessing}red{else}green{/if}">
												{$barcodeInfo.inventoryResult}
											</td>
										</tr>
									{/foreach}
									</tbody>
								</table>
							{/if}
						</div>
					{/if}
				</div>
			</div>
		</fieldset>
	</form>
</div>