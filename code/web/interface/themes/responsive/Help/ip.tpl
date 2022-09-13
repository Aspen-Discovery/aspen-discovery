<div class="col-xs-12">
	<h1>
		{translate text="Active IP Address" isPublicFacing=true}
	</h1>
	<p>
		{translate text="Your IP address is <strong>%1%</strong>." 1=$ip_address isPublicFacing=true}
	</p>
	<p>
		{translate text="Your active location is <strong>%1%</strong>." 1=$physicalLocation isPublicFacing=true}
	</p>
	{if $isOpac}
		<p>
			{translate text="You are currently at an OPAC station." isPublicFacing=true}
		</p>
	{/if}
	{if !empty($instanceName)}
		<p>{translate text="Instance Name is %1%" 1=$instanceName isPublicFacing=true}</p>
		<h2>{translate text="Valid Server Names" isPublicFacing=true}</h2>
		<ul>
		{foreach from=$validServerNames item="validServerName"}
			<li>{$validServerName}</li>
		{/foreach}
		</ul>
	{/if}
</div>