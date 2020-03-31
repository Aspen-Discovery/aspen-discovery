AspenDiscovery.RBdigital = (function(){
    return {
        cancelHold: function(patronId, id){
            let url = Globals.path + "/RBdigital/AJAX?method=cancelHold&patronId=" + patronId + "&recordId=" + id;
            $.ajax({
                url: url,
                cache: false,
                success: function(data){
                    if (data.success) {
                        AspenDiscovery.showMessage("Hold Cancelled", data.message, true);
                        $("#rbdigitalHold_" + id).hide();
                        AspenDiscovery.Account.loadMenuData();
                    }else{
                        AspenDiscovery.showMessage("Error Cancelling Hold", data.message, true);
                    }

                },
                dataType: 'json',
                async: false,
                error: function(){
                    AspenDiscovery.showMessage("Error Cancelling Hold", "An error occurred processing your request in RBdigital.  Please try again in a few minutes.", false);
                }
            });
        },

        checkOutTitle: function (id) {
            if (Globals.loggedIn){
                //Get any prompts needed for checking out a title
                let promptInfo = AspenDiscovery.RBdigital.getCheckOutPrompts(id);
                // noinspection JSUnresolvedVariable
                if (!promptInfo.promptNeeded){
                    AspenDiscovery.RBdigital.doCheckOut(promptInfo.patronId, id);
                }
            }else{
                AspenDiscovery.Account.ajaxLogin(null, function(){
                    AspenDiscovery.RBdigital.checkOutTitle(id);
                });
            }
            return false;
        },

        checkOutMagazine: function (id) {
            if (Globals.loggedIn){
                //Get any prompts needed for checking out a title
                let promptInfo = AspenDiscovery.RBdigital.getMagazineCheckOutPrompts(id);
                // noinspection JSUnresolvedVariable
                if (!promptInfo.promptNeeded){
                    AspenDiscovery.RBdigital.doMagazineCheckOut(promptInfo.patronId, id);
                }
            }else{
                AspenDiscovery.Account.ajaxLogin(null, function(){
                    AspenDiscovery.RBdigital.checkOutMagazine(id);
                });
            }
            return false;
        },

        createAccount: function(action, patronId, id){
            if (Globals.loggedIn){
                //Check form validation
                var $accountForm = $('#createRBdigitalAccount');

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
                formValues += '&password=' +  password1;
                formValues += '&libraryCard=' +  encodeURIComponent($('#libraryCard').val());
                formValues += '&firstName=' + encodeURIComponent($('#firstName').val());
                formValues += '&lastName=' + encodeURIComponent($('#lastName').val());
                formValues += '&email=' + encodeURIComponent($('#email').val());
                formValues += '&postalCode=' + encodeURIComponent($('#postalCode').val());
                formValues += '&followupAction=' + encodeURIComponent(action);
                formValues += '&patronId=' + encodeURIComponent(patronId);
                formValues += '&id=' + encodeURIComponent(id);
                formValues += '&method=createAccount';

                let ajaxUrl = Globals.path + "/RBdigital/AJAX?" + formValues;

                $.ajax({
                    url: ajaxUrl,
                    cache: false,
                    success: function(data){
                        if (data.success === true){
                            AspenDiscovery.showMessageWithButtons("Success", data.message, data.buttons);
                        }else{
                            AspenDiscovery.showMessage("Error", data.message, false);
                        }
                    },
                    dataType: 'json',
                    async: false,
                    error: function(){
                        AspenDiscovery.showMessage("Error", "An error occurred processing your request in RBdigital.  Please try again in a few minutes.");
                    }
                });
            }else{
                AspenDiscovery.showMessage("Error", "You must be logged in before creating an RBdigital account.", false);
            }
            return false;
        },

        doCheckOut: function(patronId, id){
            if (Globals.loggedIn){
                let ajaxUrl = Globals.path + "/RBdigital/AJAX?method=checkOutTitle&patronId=" + patronId + "&id=" + id;
                $.ajax({
                    url: ajaxUrl,
                    cache: false,
                    success: function(data){
                        if (data.success === true){
                            AspenDiscovery.showMessageWithButtons(data.title, data.message, data.buttons);
                            AspenDiscovery.Account.loadMenuData();
                        }else{
                            // noinspection JSUnresolvedVariable
                            if (data.noCopies === true){
                                AspenDiscovery.closeLightbox();
                                let ret = confirm(data.message);
                                if (ret === true){
                                    AspenDiscovery.RBdigital.doHold(patronId, id);
                                }
                            }else{
                                AspenDiscovery.showMessage(data.title, data.message, false);
                            }
                        }
                    },
                    dataType: 'json',
                    async: false,
                    error: function(){
                        alert("An error occurred processing your request in RBdigital.  Please try again in a few minutes.");
                        //alert("ajaxUrl = " + ajaxUrl);
                        AspenDiscovery.closeLightbox();
                    }
                });
            }else{
                AspenDiscovery.Account.ajaxLogin(null, function(){
                    AspenDiscovery.RBdigital.checkOutTitle(id);
                }, false);
            }
            return false;
        },

        doMagazineCheckOut: function(patronId, id){
            if (Globals.loggedIn){
                let ajaxUrl = Globals.path + "/RBdigital/AJAX?method=checkOutMagazine&patronId=" + patronId + "&id=" + id;
                $.ajax({
                    url: ajaxUrl,
                    cache: false,
                    success: function(data){
                        if (data.success === true){
                            AspenDiscovery.showMessageWithButtons("Magazine Checked Out Successfully", data.message, data.buttons);
                            AspenDiscovery.Account.loadMenuData();
                        }else{
                            // noinspection JSUnresolvedVariable
                            AspenDiscovery.showMessage("Error Checking Out Magazine", data.message, false);
                        }
                    },
                    dataType: 'json',
                    async: false,
                    error: function(){
                        alert("An error occurred processing your request in RBdigital.  Please try again in a few minutes.");
                        //alert("ajaxUrl = " + ajaxUrl);
                        AspenDiscovery.closeLightbox();
                    }
                });
            }else{
                AspenDiscovery.Account.ajaxLogin(null, function(){
                    AspenDiscovery.RBdigital.checkOutMagazine(id);
                }, false);
            }
            return false;
        },

        doHold: function(patronId, id){
            let url = Globals.path + "/RBdigital/AJAX?method=placeHold&patronId=" + patronId + "&id=" + id;
            $.ajax({
                url: url,
                cache: false,
                success: function(data){
                    // noinspection JSUnresolvedVariable
                    if (data.availableForCheckout){
                        AspenDiscovery.RBdigital.doCheckOut(patronId, id);
                    }else{
                        AspenDiscovery.showMessage("Placed Hold", data.message, true);
                        AspenDiscovery.Account.loadMenuData();
                    }
                },
                dataType: 'json',
                async: false,
                error: function(){
                    AspenDiscovery.showMessage("Error Placing Hold", "An error occurred processing your request in RBdigital.  Please try again in a few minutes.", false);
                }
            });
        },

        getCheckOutPrompts: function(id) {
            let url = Globals.path + "/RBdigital/" + id + "/AJAX?method=getCheckOutPrompts";
            let result = true;
            $.ajax({
                url: url,
                cache: false,
                success: function(data){
                    result = data;
                    // noinspection JSUnresolvedVariable
                    if (data.promptNeeded){
                        // noinspection JSUnresolvedVariable
                        AspenDiscovery.showMessageWithButtons(data.promptTitle, data.prompts, data.buttons);
                    }
                },
                dataType: 'json',
                async: false,
                error: function(){
                    alert("An error occurred processing your request.  Please try again in a few minutes.");
                    AspenDiscovery.closeLightbox();
                }
            });
            return result;
        },

        getMagazineCheckOutPrompts: function(id) {
            let url = Globals.path + "/RBdigital/" + id + "/AJAX?method=getMagazineCheckOutPrompts";
            let result = true;
            $.ajax({
                url: url,
                cache: false,
                success: function(data){
                    result = data;
                    // noinspection JSUnresolvedVariable
                    if (data.promptNeeded){
                        // noinspection JSUnresolvedVariable
                        AspenDiscovery.showMessageWithButtons(data.promptTitle, data.prompts, data.buttons);
                    }
                },
                dataType: 'json',
                async: false,
                error: function(){
                    alert("An error occurred processing your request.  Please try again in a few minutes.");
                    AspenDiscovery.closeLightbox();
                }
            });
            return result;
        },

        getHoldPrompts: function(id){
            let url = Globals.path + "/RBdigital/" + id + "/AJAX?method=getHoldPrompts";
            let result = true;
            $.ajax({
                url: url,
                cache: false,
                success: function(data){
                    result = data;
                    // noinspection JSUnresolvedVariable
                    if (data.promptNeeded){
                        // noinspection JSUnresolvedVariable
                        AspenDiscovery.showMessageWithButtons(data.promptTitle, data.prompts, data.buttons);
                    }
                },
                dataType: 'json',
                async: false,
                error: function(){
                    alert("An error occurred processing your request in RBdigital.  Please try again in a few minutes.");
                    AspenDiscovery.closeLightbox();
                }
            });
            return result;
        },

        placeHold: function (id) {
            if (Globals.loggedIn){
                //Get any prompts needed for placing holds (email and format depending on the interface.
                let promptInfo = AspenDiscovery.RBdigital.getHoldPrompts(id, 'hold');
                // noinspection JSUnresolvedVariable
                if (!promptInfo.promptNeeded){
                    AspenDiscovery.RBdigital.doHold(promptInfo.patronId, id);
                }
            }else{
                AspenDiscovery.Account.ajaxLogin(null, function(){
                    AspenDiscovery.RBdigital.placeHold(id);
                });
            }
            return false;
        },

        processCheckoutPrompts: function(){
            let id = $("#id").val();
            let checkoutType = $("#checkoutType").val();
            let patronId = $("#patronId option:selected").val();
            AspenDiscovery.closeLightbox();
            if (checkoutType === 'book'){
                return AspenDiscovery.RBdigital.doCheckOut(patronId, id);
            }else{
                return AspenDiscovery.RBdigital.doMagazineCheckOut(patronId, id);
            }
        },

        processHoldPrompts: function(){
            let id = $("#id").val();
            let patronId = $("#patronId option:selected").val();
            AspenDiscovery.closeLightbox();
            return AspenDiscovery.RBdigital.doHold(patronId, id);
        },

        renewCheckout: function(patronId, recordId){
            let url = Globals.path + "/RBdigital/AJAX?method=renewCheckout&patronId=" + patronId + "&recordId=" + recordId;
            $.ajax({
                url: url,
                cache: false,
                success: function(data){
                    if (data.success) {
                        AspenDiscovery.showMessage("Title Renewed", data.message, true);
                    }else{
                        AspenDiscovery.showMessage("Unable to Renew Title", data.message, true);
                    }

                },
                dataType: 'json',
                async: false,
                error: function(){
                    AspenDiscovery.showMessage("Error Renewing Checkout", "An error occurred processing your request in RBdigital.  Please try again in a few minutes.", false);
                }
            });
        },

        returnCheckout: function(patronId, recordId){
            let url = Globals.path + "/RBdigital/AJAX?method=returnCheckout&patronId=" + patronId + "&recordId=" + recordId;
            $.ajax({
                url: url,
                cache: false,
                success: function(data){
                    if (data.success) {
                        AspenDiscovery.showMessage("Title Returned", data.message, true);
                        $("#rbdigitalCheckout_" + recordId).hide();
                        AspenDiscovery.Account.loadMenuData();
                    }else{
                        AspenDiscovery.showMessage("Error Returning Title", data.message, true);
                    }

                },
                dataType: 'json',
                async: false,
                error: function(){
                    AspenDiscovery.showMessage("Error Returning Checkout", "An error occurred processing your request in RBdigital.  Please try again in a few minutes.", false);
                }
            });
        },

        returnMagazine: function(patronId, recordId){
            let url = Globals.path + "/RBdigital/AJAX?method=returnMagazine&patronId=" + patronId + "&recordId=" + recordId;
            $.ajax({
                url: url,
                cache: false,
                success: function(data){
                    if (data.success) {
                        AspenDiscovery.showMessage("Magazine Returned", data.message, true);
                        $(".rbdigitalMagazineCheckout_" + recordId).hide();
                        AspenDiscovery.Account.loadMenuData();
                    }else{
                        AspenDiscovery.showMessage("Error Returning Magazine", data.message, true);
                    }

                },
                dataType: 'json',
                async: false,
                error: function(){
                    AspenDiscovery.showMessage("Error Returning Magazine", "An error occurred processing your request in RBdigital.  Please try again in a few minutes.", false);
                }
            });
        }
    }
}(AspenDiscovery.RBdigital || {}));