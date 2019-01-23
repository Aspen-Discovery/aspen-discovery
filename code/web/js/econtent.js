$(document).ready(function(){
	$("#typeSelect").bind('change', function(){
		if (this.value == 'mp3'){
			$('#propertyRowfilename').hide();
			$('#propertyRowfolder').show();
			if (('#id').val() == ''){
				$('#filename').removeClass('required');
				$('#folder').addClass('required');
			}
		}else{
			$('#propertyRowfilename').show();
			$('#propertyRowfolder').hide();
			if (('#id').val() == ''){
				$('#filename').addClass('required');
				$('#folder').removeClass('required');
			}
		}
	});
	
	$("#typeSelect").change();
});

