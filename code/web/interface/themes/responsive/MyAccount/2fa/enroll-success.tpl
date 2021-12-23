<div id="main-content">
    {if $loggedIn}
	    <!-- Stepper -->
	    <div class="steps-form">
		    <div class="steps-row setup-panel">
			    <div class="steps-step">
				    <a type="button" class="btn btn-success btn-circle"><i class="fa fa-check"></i></a>
				    <p>{translate text="Register" isPublicFacing=true}</p>
			    </div>
			    <div class="steps-step">
				    <a type="button" class="btn btn-success btn-circle"><i class="fa fa-check"></i></a>
				    <p>{translate text="Verify" isPublicFacing=true}</p>
			    </div>
			    <div class="steps-step">
				    <a type="button" class="btn btn-success btn-circle"><i class="fa fa-check"></i></a>
				    <p>{translate text="Backup" isPublicFacing=true}</p>
			    </div>
		    </div>
	    </div>

	<div class="row">
		<div class="col-md-10 col-md-offset-1 text-center">
		<h2 class="text-success">{translate text="You're Verified!" isPublicFacing=true}</h2>
		<p>{translate text="From now on you'll use your email to sign into the catalog." isPublicFacing=true}</p>
		</div>
	</div>
    {/if}
</div>