/**
 * Created by mark on 2/11/14.
 */
VuFind.EContent = (function(){
	return {
		submitHelpForm: function(){
			$.post(Globals.path + '/Help/eContentSupport', $("#eContentSupport").serialize(),
					function(data){
						VuFind.showMessage(data.title, data.message);
					},
					'json').fail(function(){VuFind.ajaxFail()});
			return false;
		}
	}
}(VuFind.EContent));
