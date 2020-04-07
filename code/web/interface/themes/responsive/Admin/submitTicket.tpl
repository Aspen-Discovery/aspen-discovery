{strip}
	<div id="main-content" class="col-xs-12">
		<h1>{translate text="Submit Support Ticket"}</h1>
		<hr>
		{if !empty($error)}
			<div class="alert alert-warning">
				{$error}
			</div>
		{/if}
		<form class="form-horizontal">
			<div class="form-group">
				<label class="control-label" for="name">{translate text="Your Name"}</label>
				<input type="text" class="form-control" name="name" id="name" value="{$name}">
			</div>
			<div class="form-group">
				<label class="control-label" for="email">{translate text="Email"}</label>
				<input type="email" class="form-control" name="email" id="email" value="{$email}">
			</div>
			<div class="form-group">
				<label class="control-label" for="subject">{translate text="Subject"}</label>
				<input type="text" class="form-control" name="subject" id="subject">
			</div>
			<div class="form-group">
				<label class="control-label" for="description">{translate text="Description"}</label>
				<textarea class="form-control" name="description" id="description"></textarea>
			</div>
			<div class="form-group">
				<div class="col-xs-12">
					<button type="submit" name="submitTicket" class="btn btn-primary">{translate text="Submit Ticket"}</button>
				</div>
			</div>
		</form>
	</div>
{/strip}