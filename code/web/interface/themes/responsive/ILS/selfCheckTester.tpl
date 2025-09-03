{strip}
	<div id="main-content" class="col-sm-12">
		<h1>{translate text="Self Check Tester" isAdminFacing=true}</h1>
		<h2>{translate text="Test Check Outs" isAdminFacing=true}</h2>
		<form name="checkoutTester" id="checkoutTester" method="post">
			{if !empty($checkoutResult)}
				<div class="alert {if $checkoutResult.success}alert-success{else}alert-danger{/if}">
					<div><strong>{$checkoutResult.title}</strong></div>
					<div>{$checkoutResult.message}</div>

					{if !empty($checkoutResult.completionMessage)}
						<div>{$checkoutResult.completionMessage}</div>
					{/if}

					{if !empty($checkoutResult.itemData)}
						<div>Title: {$checkoutResult.itemData.title}</div>
						<div>Due: {$checkoutResult.itemData.due}</div>
						<div>Barcode: {$checkoutResult.itemData.barcode}</div>
					{/if}
				</div>
			{/if}
			<div class="row">
				<div class="form-group col-tn-12">
					<label for="patronBarcode" class="control-label">{translate text="Patron Barcode" isAdminFacing=true}</label>&nbsp;<span class="label label-danger" style="margin-right: .5em">{translate text="Required" isAdminFacing=true}</span>
					<input type="text" id="patronBarcode" name="patronBarcode" class="form-control input-sm" {if !empty($patronBarcode)}value="{$patronBarcode}"{/if} required/>
				</div>
			</div>
			<div class="row">
				<div class="form-group col-tn-12">
					<label for="patronPassword" class="control-label">{translate text="Patron PIN/Password" isAdminFacing=true}</label>&nbsp;
					<input type="password" id="patronPassword" name="patronPassword" class="form-control input-sm"/>
				</div>
			</div>
			<div class="row">
				<div class="form-group col-tn-12">
					<label for="itemBarcode" class="control-label">{translate text="Item Barcode" isAdminFacing=true}</label>&nbsp;<span class="label label-danger" style="margin-right: .5em">{translate text="Required" isAdminFacing=true}</span>
					<input type="text" id="itemBarcode" name="itemBarcode" class="form-control input-sm" {if !empty($itemBarcode)}value="{$itemBarcode}"{/if} required/>
				</div>
			</div>
			<div class="row">
				<div class="form-group col-tn-12">
					<div class="controls">
						<input type="submit" name="submitCheckout" value="{translate text="Check Out Item" isAdminFacing="true" inAttribute="true"}" class="btn btn-primary">
					</div>
				</div>
			</div>
		</form>

		<h2>{translate text="Test Check Ins" isAdminFacing=true}</h2>
		<form name="checkInTester" id="checkInTester" method="post">
			{if !empty($checkinResult)}
				<div class="alert {if $checkinResult.success}alert-success{else}alert-danger{/if}">
					<div><strong>{$checkinResult.title}</strong></div>
					<div>{$checkinResult.message}</div>
				</div>
			{/if}
			<div class="row">
				<div class="form-group col-tn-12">
					<label for="patronBarcode" class="control-label">{translate text="Patron Barcode" isAdminFacing=true}</label>&nbsp;<span class="label label-danger" style="margin-right: .5em">{translate text="Required" isAdminFacing=true}</span>
					<input type="text" id="patronBarcode" name="patronBarcode" class="form-control input-sm" {if !empty($patronBarcode)}value="{$patronBarcode}"{/if} required/>
				</div>
			</div>
			<div class="row">
				<div class="form-group col-tn-12">
					<label for="patronPassword" class="control-label">{translate text="Patron PIN/Password" isAdminFacing=true}</label>&nbsp;
					<input type="password" id="patronPassword" name="patronPassword" class="form-control input-sm"/>
				</div>
			</div>
			<div class="row">
				<div class="form-group col-tn-12">
					<label for="itemBarcode" class="control-label">{translate text="Item Barcode" isAdminFacing=true}</label>&nbsp;<span class="label label-danger" style="margin-right: .5em">{translate text="Required" isAdminFacing=true}</span>
					<input type="text" id="itemBarcode" name="itemBarcode" class="form-control input-sm" required/>
				</div>
			</div>
			<div class="row">
				<div class="form-group col-tn-12">
					<div class="controls">
						<input type="submit" name="submitCheckin" value="{translate text="Check In Item" isAdminFacing="true" inAttribute="true"}" class="btn btn-primary">
					</div>
				</div>
			</div>
		</form>
	</div>
{/strip}
