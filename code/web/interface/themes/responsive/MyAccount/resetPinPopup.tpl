<div>
	<form method="post" name="modifyPinNumber" action="/MyAccount/Home" onsubmit="return resetPinReset();">
		<div>
			<input type="hidden" name="resetPin" value="true"/>
			<div>
				<label for="card_number" class='loginLabel'>{translate text="Library card number" isPublicFacing=true}</label><input type="text" name="card_number" id="card_number" size="20" maxlength="40" />
			</div>
			<div>
				<input type="submit" value="{translate text="Request PIN" isPublicFacing=true inAttribute=true}" />
			</div>
		</div>
	</form>
</div>