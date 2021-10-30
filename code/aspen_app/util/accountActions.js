import React from "react";
import { Toast } from "native-base";
import AsyncStorage from "@react-native-async-storage/async-storage";
import * as SecureStore from 'expo-secure-store';
import Constants from "expo-constants";
import * as Random from 'expo-random';
import moment from "moment";
import { create, CancelToken } from 'apisauce';
import * as WebBrowser from 'expo-web-browser';

// custom components and helper files
import { translate } from "../util/translations";
import { popToast, popAlert } from "../components/loadError";

export async function isLoggedIn() {
    const api = create({ baseURL: global.libraryUrl + '/API', timeout: 5000 });
    const response = await api.get('/UserAPI?method=isLoggedIn');

    if(response.ok) {
        const result = response.data;
        return result;
    } else {
        popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
        const result = response.problem;
        return result;
    }
}

/* ACTIONS ON CHECKOUTS */
export async function renewCheckout(barcode, recordId, source) {

    const api = create({ baseURL: global.libraryUrl + '/API', timeout: 5000 });
    const response = await api.get('/UserAPI?method=renewItem', { username: global.userKey, password: global.secretKey, itemBarcode: barcode, recordId: recordId, itemSource: source });

        console.log(response);
    if(response.ok) {
        const fetchedData = response.data;
        const result = fetchedData.result;

        if(source == "ils") {
            if (result.renewalMessage.success == true) {
                popAlert("Title renewed", result.renewalMessage.message, "success");
            } else {
                popAlert("Unable to renew title", result.renewalMessage.message, "error");
            }
        } else {
            if (result.success == true) {
                popAlert("Title renewed", result.message, "success");
            } else {
                popAlert("Unable to renew title", result.message, "error");
            }
        }

    } else {
        popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
    }

}

export async function renewAllCheckouts() {

    const api = create({ baseURL: global.libraryUrl + '/API', timeout: 5000 });
    const response = await api.get('/UserAPI?method=renewAll', { username: global.userKey, password: global.secretKey });

    if(response.ok) {
        const fetchedData = response.data;
        const result = fetchedData.result;

        if (result.renewalMessage.success == true) {
            popAlert("Renewed All Titles", result.renewalMessage[0], "success");
        } else {
            popAlert("Unable to Renew All Titles", result.renewalMessage[0], "error");
        }

    } else {
        popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
    }
}

export async function returnCheckout(userId, id, source, overDriveId) {

    var itemId = id;
    if(overDriveId != null) {
        var itemId = overDriveId;
    }

    const api = create({ baseURL: global.libraryUrl + '/API', timeout: 3000 });
    const response = await api.get('/UserAPI?method=returnCheckout', { username: global.userKey, password: global.secretKey, id: itemId, patronId: userId, itemSource: source });

    if(response.ok) {
        const fetchedData = response.data;
        const result = fetchedData.result;

        if (result.success == true) {
            popAlert(result.title, result.message, "success");
        } else {
            popAlert(result.title, result.message, "error");
        }
    } else {
        popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
    }

}

export async function viewOnlineItem(userId, id, source, accessOnlineUrl) {

    if (source == "hoopla") {
        const api = create({ baseURL: global.libraryUrl + '/API', timeout: 3000 });
        const response = await api.get('/UserAPI?method=viewOnlineItem', { username: global.userKey, password: global.secretKey, patronId: userId, itemId: id, itemSource: source });

        if(response.ok) {
            const results = response.data;
            const result = results.result.url;

            await WebBrowser.openBrowserAsync(result)
              .then(res => {
                console.log(res);
              })
              .catch(async err => {
                if (err.message === "Another WebBrowser is already being presented.") {

                 try {
                      WebBrowser.dismissBrowser();
                      await WebBrowser.openBrowserAsync(result)
                        .then(response => {
                          console.log(response);
                        })
                        .catch(async error => {
                            popToast(translate('error.no_open_resource'), translate('error.device_block_browser'), "warning");
                        });
                    } catch(error) {
                          console.log ("Really borked.");
                    }

                } else {
                    popToast(translate('error.no_open_resource'), translate('error.device_block_browser'), "warning");
                }
              });
        } else {
            popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
        }
    } else {
        await WebBrowser.openBrowserAsync(accessOnlineUrl)
          .then(res => {
            console.log(res);
          })
          .catch(async err => {
            if (err.message === "Another WebBrowser is already being presented.") {

             try {
                  WebBrowser.dismissBrowser();
                  await WebBrowser.openBrowserAsync(accessOnlineUrl)
                    .then(response => {
                      console.log(response);
                    })
                    .catch(async error => {
                      popToast(translate('error.no_open_resource'), translate('error.device_block_browser'), "warning");
                    });
                } catch(error) {
                    console.log("Unable to open.")
                }

            } else {
              popToast(translate('error.no_open_resource'), translate('error.device_block_browser'), "warning");
            }
          });
    }

}

export async function viewOverDriveItem(userId, formatId, overDriveId) {

    const api = create({ baseURL: global.libraryUrl + '/API', timeout: 3000 });
    const response = await api.get('/UserAPI?method=viewOnlineItem', { username: global.userKey, password: global.secretKey, patronId: userId, overDriveId: overDriveId, formatId: formatId, itemSource: "overdrive" });

    if(response.ok) {
        const result = response.data;
        const accessUrl = result.result.url;

        await WebBrowser.openBrowserAsync(accessUrl)
          .then(res => {
            console.log(res);
          })
          .catch(async err => {
            if (err.message === "Another WebBrowser is already being presented.") {

             try {
                  WebBrowser.dismissBrowser();
                  await WebBrowser.openBrowserAsync(accessUrl)
                    .then(response => {
                      console.log(response);
                    })
                    .catch(async error => {
                      console.log("Unable to close previous browser session.");
                    });
                } catch(error) {
                    console.log ("Really borked.");
                }
            } else {
              popToast(translate('error.no_open_resource'), translate('error.device_block_browser'), "warning");
            }
          });


    } else {
        popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
    }
}

/* ACTIONS ON HOLDS */
export async function freezeHold(cancelId, recordId, source) {

    const today = moment();
    const reactivationDate = moment().add(30, 'days').format('YYYY-MM-DD');
    const api = create({ baseURL: global.libraryUrl + '/API', timeout: 3000 });
    const response = await api.get('/UserAPI?method=freezeHold', { username: global.userKey, password: global.secretKey, sessionId: global.sessionId, holdId: cancelId, recordId: recordId, itemSource: source, reactivationDate: reactivationDate, patronId: global.patronId });

    if(response.ok) {
        const fetchedData = response.data;
        const result = fetchedData.result;

        if(result.success == true) {
            popAlert("Hold frozen", result.message, "success");
        } else {
            popAlert("Unable to freeze hold", result.message, "error");
        }
    } else {
        popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
    }
}

export async function thawHold(cancelId, recordId, source) {
    const api = create({ baseURL: global.libraryUrl + '/API', timeout: 5000 });
    const response = await api.get('/UserAPI?method=activateHold', { username: global.userKey, password: global.secretKey, sessionId: global.sessionId, holdId: cancelId, recordId: recordId, itemSource: source, patronId: global.patronId });

    if(response.ok) {
        const fetchedData = response.data;
        const result = fetchedData.result;

        if(result.success == true) {
            popAlert("Hold thawed", result.message, "success");
        } else {
            popAlert("Unable to thaw hold", result.message, "error");
        }
    } else {
        popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
    }
}

export async function cancelHold(cancelId, recordId, source) {
    const api = create({ baseURL: global.libraryUrl + '/API', timeout: 3000 });
    const response = await api.get('/UserAPI?method=cancelHold', { username: global.userKey, password: global.secretKey, sessionId: global.sessionId, cancelId: cancelId, recordId: recordId, itemSource: source, patronId: global.patronId });

    if(response.ok) {
        const fetchedData = response.data;
        const result = fetchedData.result;

        if(fetchedData.success == true) {
            popAlert("Hold cancelled", result.message, "success");
        } else {
            popAlert("Unable to cancel hold", result.message, "error");
        }

    } else {
        popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
    }
}

export async function changeHoldPickUpLocation(holdId, newLocation) {
    const api = create({ baseURL: global.libraryUrl + '/API', timeout: 3000 });
    const response = await api.get('/UserAPI?method=changeHoldPickUpLocation', { username: global.userKey, password: global.secretKey, sessionId: global.sessionId, holdId: holdId, location: newLocation });

    if(response.ok) {
        const fetchedData = response.data;
        console.log(result);
        const result = fetchedData.result;

        if(fetchedData.success == true) {
            popAlert("Pickup location updated", result.message, "success");
        } else {
            popAlert("Unable to update pickup location", result.message, "error");
        }

    } else {
        popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
    }
}

export async function updateOverDriveEmail(itemId, source, patronId, overdriveEmail, promptForOverdriveEmail) {
    const api = create({ baseURL: global.libraryUrl + '/API', timeout: 5000 });
    const response = await api.get('/UserAPI?method=updateOverDriveEmail', { username: global.userKey, password: global.secretKey, itemId: itemId, itemSource: source, patronId: patronId, overdriveEmail: overdriveEmail, promptForOverdriveEmail: promptForOverdriveEmail });

    if(response.ok) {
        const responseData = response.data;
        const result = responseData.result;
        return result;
    } else {
        popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
    }
}