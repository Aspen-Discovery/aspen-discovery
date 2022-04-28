$(document).ready(function(){
	$('#lookfor').on( 'keydown', function (event ){
		if (event.which === 13 || event.which === 10){
			event.preventDefault();
			event.stopPropagation();
			$("#searchForm").submit();
			return false;
		}
	}).on( 'keypress', function (event ){
		if (event.which === 13 || event.which === 10){
			event.preventDefault();
			event.stopPropagation();
			return false;
		}
	});

	try{
		var mediaQueryList = window.matchMedia('print');
		mediaQueryList.addEventListener("change",setIsPrint);

		function setIsPrint() {
			Globals.isPrint = this.checkNative();
		}
	}catch(err){
		//For now, just ignore this error.
	}

	window.onbeforeprint = function() {
		Globals.isPrint = true;
	}

});