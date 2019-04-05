VuFind.Rbdigital = (function(){
    return {
        checkOutTitle: function (id) {
            if (Globals.loggedIn){
                //Get any prompts needed for checking out a title
                var promptInfo = VuFind.Rbdigital.getCheckoutPrompts(overDriveId, 'hold');
                if (!promptInfo.promptNeeded){
                    VuFind.Rbdigital.doCheckout(promptInfo.patronId, overDriveId);
                }
            }else{
                VuFind.Account.ajaxLogin(null, function(){
                    VuFind.Rbdigital.checkOutTitle(id);
                });
            }
            return false;
        },

        getCheckoutPrompts(id) {
            var url = Globals.path + "/Rbdigital/" + overDriveId + "/AJAX?method=GetCheckoutPrompts";
            var result = true;
            $.ajax({
                url: url,
                cache: false,
                success: function(data){
                    result = data;
                    if (data.promptNeeded){
                        VuFind.showMessageWithButtons(data.promptTitle, data.prompts, data.buttons);
                    }
                },
                dataType: 'json',
                async: false,
                error: function(){
                    alert("An error occurred processing your request.  Please try again in a few minutes.");
                    VuFind.closeLightbox();
                }
            });
            return result;
        },

        placeHold: function (id) {

        }
    }
}(VuFind.Rbdigital || {}));