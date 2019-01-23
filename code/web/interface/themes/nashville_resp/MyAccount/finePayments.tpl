{strip}
	{php}
	// determine if user has any pending payments
	require_once ROOT_DIR . '/services/MyAccount/PayOnlineNashville.php';
  $user = UserAccount::getLoggedInUser();

	// users have to log out and log back in in order for the $user->finesVal to reflect patrons actual balance.
	// to prevent them from seeing the payment form again, I check that the patron API agrees with $user->finesVal

	$payonline = new PayOnlineNashville();
	$payonline->librarycard = 'b' . $user->cat_username;
	$search = $payonline->search(); // returns patron information, including bills.

	// first, determine if user has unapplied payments.  This can happen if the patrons record is busy, or if
	// the API becomes unavailable before they complete the payment process.  Cron will resolve these unapplied payments.

	if(isset($search->error)) {
		{/php}
		<div class="alert alert-danger">
		{php}echo $search->error;{/php}
		</div>
		{php}
	} elseif(is_array($payonline->pending('p' . $user->username . Millennium::getCheckDigit($user->username)))) {
		// This copy should probably be defined elsewhere (language file?)
		{/php}
		<div class="alert alert-danger">You have pending payments that will be applied to your account within the next 24 hours. Additional online payments are not currently an option for this account</div>
		{php}
	} elseif(floor($search->bill) == 0 && $user->finesVal > 0) {
		// This copy should probably be defined elsewhere (language file?)
		// User has less than $1 in fines; do not allow online fine payment
		{/php}
		<div class="alert alert-danger">You have less than $1 in fines. Online payment is currently not an option for this account.</div>
		{php}
	} elseif($search->bill > 0 && $user->finesVal > 0) {
	{/php}

<div>
<p>Online payments must be for the total amount of Nashville Public Library fines and fees associated with a patron account, plus the convenience fee.</p>
<p>A 2.30% convenience fee will be charged on all debit and credit card transactions. This fee is collected by a third party processor. Neither Nashville Public Library nor the Metropolitan Government of Nashville and Davidson County receives any part of it.</p>
<p>By clicking on Pay Online below, you acknowledge that the convenience fee will be charged as calculated above and you agree to pay this convenience fee.</p>
</div>

<div>
<p>Total Nashville Public Library Fines and Fees: ${php}echo number_format($search->bill,2,'.',''){/php}</p>
<p>Convenience Fee: ${php}echo number_format((ceil(($search->bill)*2.3))/100,2,'.','');{/php}</p>
<p>Total Charge: ${php}echo number_format(($search->bill + (ceil(($search->bill)*2.3))/100),2,'.','');{/php}</p>
<p><a href="#" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#modalOnlinePayment">Pay ${php}echo number_format(($search->bill + (ceil(($search->bill)*2.3))/100),2,'.','');{/php} Online</a></p>
</div>

<!-- Online Payment Modal -->
<div id="modalOnlinePayment" class="modal fade" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			<form id="online_payment_form" accept-charset="UTF-8" action="/MyAccount/PayOnlineNashville" method="post">

				<fieldset>
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
						<h4 class="modal-title">Pay ${php}echo number_format(($search->bill + (ceil(($search->bill)*2.3))/100),2,'.','');{/php} Online</h4>
					</div>
					<div class="modal-body">
						<p>Enter your payment information and click Process Online Payment. Your payment will then be submitted for processing.</p>
						<p>If you do not wish to process this payment as calculated above, click on Cancel below to leave this page.</p>
					<div>
						<label for="cc-num">Card Number</label><br />
<!--						<input id="cc-num" name="payment[cc]" type="text" placeholder="•••• •••• •••• ••••" onblur="this.value=this.value.replace(/\D/g,''); this.checkValidity()" {literal}pattern="(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|3[47][0-9]{13}|6(?:011|5[0-9]{2})[0-9]{12})"{/literal} required="required">
-->						<input id="cc-num" name="payment[cc]" type="text" placeholder="•••• •••• •••• ••••" onblur="this.value=this.value.replace(/\D/g,''); this.checkValidity()" {literal}pattern="(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|3[47][0-9]{13}|6(?:011|5[0-9]{2})[0-9]{12})"{/literal} required="required" value="371449635398431">
					</div>
					<div>
						<label for="cc-exp">Expires Month / Year</label><br />
<!--						<input id="cc-exp" name="payment[cc_month]" type="text" placeholder="MM" {literal}pattern="[0-9]{2}"{/literal} size="2" required="required">
						<input id="cc-exp" name="payment[cc_year]" type="text" placeholder="YY" {literal}pattern="[0-9]{2}"{/literal} size="2" required="required">
-->						<input id="cc-exp" name="payment[cc_month]" type="text" placeholder="MM" {literal}pattern="[0-9]{2}"{/literal} size="2" required="required" value="12"> /
						<input id="cc-exp" name="payment[cc_year]" type="text" placeholder="YY" {literal}pattern="[0-9]{2}"{/literal} size="2" required="required" value="16">
					</div>
					<div>
						<label for="cc-cvv">Security Code</label><br />
<!--						<input id="cc-cvv" name="payment[cc_cvv]" type="text" placeholder="••••" {literal}pattern="[0-9]{3,4}"{/literal} required="required">
-->						<input id="cc-cvv" name="payment[cc_cvv]" type="text" placeholder="••••" {literal}pattern="[0-9]{3,4}"{/literal} required="required" value="7357">
					</div>
					<div>
						<label for="cc-fullname">Name on Credit Card</label><br />
<!--						<input id="cc-fullname" name="payment[fullname]" type="text" value="{php}echo strtoupper($user->firstname)." ". strtoupper($user->lastname);{/php}" required="required">
-->						<input id="cc-fullname" name="payment[fullname]" type="text" value="{php}echo strtoupper($user->firstname)." ". strtoupper($user->lastname);{/php}" required="required" value="AL CHASE">
					</div>
					<div>
						<label for="cc-zipcode">Billing ZIP Code</label><br />
<!--						<input id="cc-zipcode" name="payment[zipcode]" type="text" value="{php}echo substr(preg_replace('/\D/','//',$user->zip),0,5);{/php}" {literal}pattern="[0-9]{5}"{/literal} required="required">
-->						<input id="cc-zipcode" name="payment[zipcode]" type="text" value="{php}echo substr(preg_replace('/\D/','//',$user->zip),0,5);{/php}" {literal}pattern="[0-9]{5}"{/literal} required="required" value="10463">
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
						<span class="modal-buttons"><input id="ProcessOnlinePaymentButton" value="Process Online Payment" class="btn btn-primary" type="submit"></span>
					</div>
				</fieldset>
			</form>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modalOnlinePayment -->
{php}
}
{/php}

{/strip}
