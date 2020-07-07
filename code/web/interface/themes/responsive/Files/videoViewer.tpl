{strip}
<div class="col-xs-12">
	<h1>{$title}</h1>
	<div id="videoContainer">
		<video width="100%" id="player" controls>
			<source src="{$videoPath}" type="video/mp4">
			Your browser does not support the display of Videos or the file ({$fileSize}) is too large to display.  Click <a href="{$videoPath}">here</a> to open the file.
		</video>
	</div>
{/strip}