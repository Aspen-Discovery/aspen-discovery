VuFind.Rbdigital = (function(){
    return {
        cancelHold: function(patronId, id){
            let url = Globals.path + "/Rbdigital/AJAX?method=cancelHold&patronId=" + patronId + "&recordId=" + id;
            $.ajax({
                url: url,
                cache: false,
                success: function(data){
                    if (data.success) {
                        VuFind.showMessage("Hold Cancelled", data.message, true);
                        $("#rbdigitalHold_" + id).hide();
                        VuFind.Account.loadMenuData();
                    }else{
                        VuFind.showMessage("Error Cancelling Hold", data.message, true);
                    }

                },
                dataType: 'json',
                async: false,
                error: function(){
                    VuFind.showMessage("Error Cancelling Hold", "An error occurred processing your request in Rbdigital.  Please try again in a few minutes.", false);
                }
            });
        },

        checkOutTitle: function (id) {
            if (Globals.loggedIn){
                //Get any prompts needed for checking out a title
                let promptInfo = VuFind.Rbdigital.getCheckOutPrompts(id);
                // noinspection JSUnresolvedVariable
                if (!promptInfo.promptNeeded){
                    VuFind.Rbdigital.doCheckOut(promptInfo.patronId, id);
                }
            }else{
                VuFind.Account.ajaxLogin(null, function(){
                    VuFind.Rbdigital.checkOutTitle(id);
                });
            }
            return false;
        },

        createAccount: function(action, patronId, id){
            if (Globals.loggedIn){
                //Check form validation
                var $accountForm = $('#createRbdigitalAccount');

                if(! $accountForm[0].checkValidity()) {
                    // If the form is invalid, submit it. The form won't actually submit;
                    // this will just cause the browser to display the native HTML5 error messages.
                    $accountForm.find(':submit').click();
                    return false;
                }

                let formValues = 'username=' + encodeURIComponent($("#username").val());
                let password1 = encodeURIComponent($('#password1').val());
                let password2 = encodeURIComponent($('#password2').val());
                if (password1 !== password2){
                    $("#password_validation").show().focus();
                    return false;
                }else{
                    $("#password_validation").hide();
                }
                formValues += '&password=' +  encodeURIComponent($('#password1').val());
                formValues += '&libraryCard=' +  encodeURIComponent($('#libraryCard').val());
                formValues += '&firstName=' + encodeURIComponent($('#firstName').val());
                formValues += '&lastName=' + encodeURIComponent($('#lastName').val());
                formValues += '&email=' + encodeURIComponent($('#email').val());
                formValues += '&postalCode=' + encodeURIComponent($('#postalCode').val());
                formValues += '&followupAction=' + encodeURIComponent(action);
                formValues += '&patronId=' + encodeURIComponent(patronId);
                formValues += '&id=' + encodeURIComponent(id);
                formValues += '&method=createAccount';

                let ajaxUrl = Globals.path + "/Rbdigital/AJAX?" + formValues;

                $.ajax({
                    url: ajaxUrl,
                    cache: false,
                    success: function(data){
                        if (data.success === true){
                            VuFind.showMessageWithButtons("Success", data.message, data.buttons);
                        }else{
                            VuFind.showMessage("Error", data.message, false);
                        }
                    },
                    dataType: 'json',
                    async: false,
                    error: function(){
                        VuFind.showMessage("Error", "An error occurred processing your request in Rbdigital.  Please try again in a few minutes.");
                    }
                });
            }else{
                VuFind.showMessage("Error", "You must be logged in before creating an Rbdigital account.", false);
            }
            return false;
        },

        doCheckOut: function(patronId, id){
            if (Globals.loggedIn){
                let ajaxUrl = Globals.path + "/Rbdigital/AJAX?method=checkOutTitle&patronId=" + patronId + "&id=" + id;
                $.ajax({
                    url: ajaxUrl,
                    cache: false,
                    success: function(data){
                        if (data.success === true){
                            VuFind.showMessageWithButtons("Title Checked Out Successfully", data.message, data.buttons);
                        }else{
                            // noinspection JSUnresolvedVariable
                            if (data.noCopies === true){
                                VuFind.closeLightbox();
                                let ret = confirm(data.message);
                                if (ret === true){
                                    VuFind.Rbdigital.doHold(patronId, id);
                                }
                            }else{
                                VuFind.showMessage("Error Checking Out Title", data.message, false);
                            }
                        }
                    },
                    dataType: 'json',
                    async: false,
                    error: function(){
                        alert("An error occurred processing your request in Rbdigital.  Please try again in a few minutes.");
                        //alert("ajaxUrl = " + ajaxUrl);
                        VuFind.closeLightbox();
                    }
                });
            }else{
                VuFind.Account.ajaxLogin(null, function(){
                    VuFind.Rbdigital.checkOutTitle(id);
                }, false);
            }
            return false;
        },

        doHold: function(patronId, id){
            let url = Globals.path + "/Rbdigital/AJAX?method=placeHold&patronId=" + patronId + "&id=" + id;
            $.ajax({
                url: url,
                cache: false,
                success: function(data){
                    // noinspection JSUnresolvedVariable
                    if (data.availableForCheckout){
                        VuFind.Rbdigital.doCheckOut(patronId, id);
                    }else{
                        VuFind.showMessage("Placed Hold", data.message, true);
                    }
                },
                dataType: 'json',
                async: false,
                error: function(){
                    VuFind.showMessage("Error Placing Hold", "An error occurred processing your request in Rbdigital.  Please try again in a few minutes.", false);
                }
            });
        },

        getCheckOutPrompts(id) {
            let url = Globals.path + "/Rbdigital/" + id + "/AJAX?method=getCheckOutPrompts";
            let result = true;
            $.ajax({
                url: url,
                cache: false,
                success: function(data){
                    result = data;
                    // noinspection JSUnresolvedVariable
                    if (data.promptNeeded){
                        // noinspection JSUnresolvedVariable
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

        getHoldPrompts: function(id){
            let url = Globals.path + "/Rbdigital/" + id + "/AJAX?method=getHoldPrompts";
            let result = true;
            $.ajax({
                url: url,
                cache: false,
                success: function(data){
                    result = data;
                    // noinspection JSUnresolvedVariable
                    if (data.promptNeeded){
                        // noinspection JSUnresolvedVariable
                        VuFind.showMessageWithButtons(data.promptTitle, data.prompts, data.buttons);
                    }
                },
                dataType: 'json',
                async: false,
                error: function(){
                    alert("An error occurred processing your request in Rbdigital.  Please try again in a few minutes.");
                    VuFind.closeLightbox();
                }
            });
            return result;
        },

        placeHold: function (id) {
            if (Globals.loggedIn){
                //Get any prompts needed for placing holds (e-mail and format depending on the interface.
                let promptInfo = VuFind.Rbdigital.getHoldPrompts(id, 'hold');
                // noinspection JSUnresolvedVariable
                if (!promptInfo.promptNeeded){
                    VuFind.Rbdigital.doHold(promptInfo.patronId, id);
                }
            }else{
                VuFind.Account.ajaxLogin(null, function(){
                    VuFind.Rbdigital.placeHold(id);
                });
            }
            return false;
        },

        renewCheckout: function(patronId, recordId){
            let url = Globals.path + "/Rbdigital/AJAX?method=renewCheckout&patronId=" + patronId + "&recordId=" + recordId;
            $.ajax({
                url: url,
                cache: false,
                success: function(data){
                    if (data.success) {
                        VuFind.showMessage("Title Renewed", data.message, true);
                    }else{
                        VuFind.showMessage("Unable to Renew Title", data.message, true);
                    }

                },
                dataType: 'json',
                async: false,
                error: function(){
                    VuFind.showMessage("Error Renewing Checkout", "An error occurred processing your request in Rbdigital.  Please try again in a few minutes.", false);
                }
            });
        },

        returnCheckout: function(patronId, recordId){
            let url = Globals.path + "/Rbdigital/AJAX?method=returnCheckout&patronId=" + patronId + "&recordId=" + recordId;
            $.ajax({
                url: url,
                cache: false,
                success: function(data){
                    if (data.success) {
                        VuFind.showMessage("Title Returned", data.message, true);
                        $("#rbdigitalCheckout_" + recordId).hide();
                        VuFind.Account.loadMenuData();
                    }else{
                        VuFind.showMessage("Error Returning Title", data.message, true);
                    }

                },
                dataType: 'json',
                async: false,
                error: function(){
                    VuFind.showMessage("Error Returning Checkout", "An error occurred processing your request in Rbdigital.  Please try again in a few minutes.", false);
                }
            });
        }
    }
}(VuFind.Rbdigital || {}));