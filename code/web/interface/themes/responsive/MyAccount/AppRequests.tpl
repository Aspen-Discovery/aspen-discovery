{strip}
    {if !empty($loggedIn)}
		<h1>{translate text='LiDA Requests' isAdminFacing=true}</h1>
	    <div class="well">
		    <form action="" method="post" role="form">
			    <div class="form-group propertyRow">
				    <label for="allowAppRequestLogging" class="control-label">{translate text='Allow logging Aspen LiDA requests for this patron' isPublicFacing=true}</label>&nbsp;&nbsp;
				    <input type="checkbox" class="form-control" name="allowAppRequestLogging" id="allowAppRequestLogging" {if $user->allowAppRequestLogging==1}checked='checked'{/if} data-switch="">
				    <script type="text/javascript">
                        {literal}
					    $(function(){ $('input[type="checkbox"][data-switch]').bootstrapSwitch()});
                        $('#allowAppRequestLogging').on('switchChange.bootstrapSwitch', function(event, state) {
	                        $.post(window.location.href, {
		                        allowAppRequestLogging: state ? 1 : 0
	                        });
                        });
                        {/literal}
				    </script>
			    </div>
		    </form>
	    </div>
		<table class="adminTable table table-responsive table-condensed">
			<thead>
			<tr>
				<th>{translate text="API" isAdminFacing=true}</th>
				<th>{translate text="Method" isAdminFacing=true}</th>
				<th>{translate text="Parameters" isAdminFacing=true}</th>
				<th>{translate text="LiDA Version" isAdminFacing=true}</th>
				<th>{translate text="Time" isAdminFacing=true}</th>
			</tr>
			</thead>
			<tbody>
            {foreach from=$requestLogs item=$logEntry}
				<tr>
					<td>{$logEntry->action}</td>
					<td>{$logEntry->method}</td>
					<td>{$logEntry->queryString}</td>
					<td>{$logEntry->version}</td>
					<td>{$logEntry->time|date_format:"%D %T"}</td>
				</tr>
            {/foreach}
			</tbody>
		</table>
    {else}
        {translate text="You must sign in to view this information." isPublicFacing=true}<a href='/MyAccount/Login' class="btn btn-primary">{translate text="Sign In" isPublicFacing=true}</a>
    {/if}
{/strip}
