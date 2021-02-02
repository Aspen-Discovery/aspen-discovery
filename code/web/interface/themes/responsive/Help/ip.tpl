<div class="col-xs-12">
	<h1>
		{translate text="Active IP Address"}
	</h1>
	<p>
		{translate text="Your IP address is <strong>%1%</strong>." 1=$ip_address}
	</p>
	<p>
		{translate text="Your active location is <strong>%1%</strong>." 1=$physicalLocation}
	</p>
	{if $isOpac}
		<p>
			{translate text="You are currently at an OPAC station."}
		</p>
	{/if}
</div>