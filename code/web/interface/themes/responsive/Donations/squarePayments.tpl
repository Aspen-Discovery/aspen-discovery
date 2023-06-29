{strip}
	<input type="hidden" name="patronId" value="{$userId}"/>
	<div class="row">
		<div class="col-tn-12 col-sm-8 col-md-6 col-lg -3">
		<script type="text/javascript" src="{$squareCdnUrl}"></script>
        <script>
	        const appId = '{$squareApplicationId}';
	        const locationId = '{$squareLocationId}';

	         async function initializeCard(payments) {ldelim}
	           const card = await payments.card();
	           await card.attach('#card-container');
	           return card;
	         {rdelim}

             async function createPayment(token) {ldelim}
                AspenDiscovery.Account.completeSquareOrder('{$userId}', 'donation', token);
              {rdelim}

              async function tokenize(paymentMethod) {ldelim}
                const tokenResult = await paymentMethod.tokenize();
                if (tokenResult.status === 'OK') {ldelim}
                  AspenDiscovery.Account.createSquareOrder('#fines{$userId}', 'donation', tokenResult.token);
                  return tokenResult.token;
                {rdelim} else {ldelim}
                  let errorMessage = `Tokenization failed.`;
                  if (tokenResult.errors) {ldelim}
                    errorMessage += ` and errors: ${ldelim}JSON.stringify(
                      tokenResult.errors
                    ){rdelim}`;
                  {rdelim}
                  throw new Error(errorMessage);
                {rdelim}
              {rdelim}

              function displayPaymentResults(status) {ldelim}
                const statusContainer = document.getElementById(
                  'payment-status-container'
                );
                if (status === 'SUCCESS') {ldelim}
                  statusContainer.classList.remove('is-failure');
                  statusContainer.classList.add('is-success');
                {rdelim} else {ldelim}
                  statusContainer.classList.remove('is-success');
                  statusContainer.classList.add('is-failure');
                {rdelim}

                statusContainer.style.visibility = 'visible';
              {rdelim}

	        document.addEventListener('DOMContentLoaded', async function () {ldelim}
	          const cardButton = document.getElementById('card-button');
	          if (!window.Square) {ldelim}
	            throw new Error('Square.js failed to load properly');
	          {rdelim}
	          const payments = window.Square.payments(appId, locationId);
	          let card;
	          try {ldelim}
	            card = await initializeCard(payments);
	            cardButton.style.display = "block";
	          {rdelim} catch (e) {ldelim}
	            console.error('Initializing Card failed', e);
	            return;
	          {rdelim}

	           async function handlePaymentMethodSubmission(event, paymentMethod) {ldelim}
                 event.preventDefault();

                 try {ldelim}
                   cardButton.disabled = true;
                   const token = await tokenize(paymentMethod);
                   const paymentResults = await createPayment(token);
                   displayPaymentResults('SUCCESS');
                 {rdelim} catch (e) {ldelim}
                   cardButton.disabled = false;
                   displayPaymentResults('FAILURE');
                   console.error(e.message);
                 {rdelim}
               {rdelim}


               cardButton.addEventListener('click', async function (event) {ldelim}
                 await handlePaymentMethodSubmission(event, card);
               {rdelim});
          {rdelim});
        </script>
        <div id="card-container"></div>
        <button id="card-button" type="button" class="btn btn-primary btn-block" style="display:none"><i class="fas fa-lock"></i> Pay Now</button>
        <div id="payment-status-container"></div>
		</div>
	</div>
{/strip}