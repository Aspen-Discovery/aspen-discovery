<div>
	<form method="post" name="modifyPinNumber" action="{$path}/MyAccount/Home" onsubmit="return resetPinReset();">
		<div>
			<input type="hidden" name="resetPin" value="true"/>
			<div>
				<label for="card_number" class='loginLabel'>Library card number:</label><input type="text" name="card_number" id="card_number" size="20" maxlength="40" />
			</div>
			<div>
				<input type="submit" value="Request PIN" />
			</div>
		</div>
	</form>
</div>