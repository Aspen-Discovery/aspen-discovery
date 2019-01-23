{strip}
	<nav id="masquerade-header" class="navbar-fixed-top">
		<div id="masquerade-header-content" class="container-fluid">
			<div class="row">
				<div id="masquerade-header-title" class="col-tn-7 col-xs-8 col-sm-4 col-lg-3">
					<h4>
						<span class="glyphicon glyphicon-sunglasses"></span>
						&nbsp;
						Masquerade Mode
					</h4>
				</div>
				<div id="masquerade-header-name-section" class="hidden-tn hidden-xs col-sm-5 col-lg-6">
					<h5>Masquerading As {$userDisplayName|capitalize}</h5>
				</div>

				<div id="masquerade-header-end" class="col-tn-5 col-xs-4 col-sm-3 col-lg-2 pull-right">
					<button class="btn btn-masquerade btn-block pull-right" onclick="VuFind.Account.endMasquerade()">End Masquerade</button>
				</div>
			</div>

		</div>
	</nav>
{/strip}