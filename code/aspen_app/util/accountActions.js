import React from "react";
import { Toast } from "native-base";
import AsyncStorage from "@react-native-async-storage/async-storage";
import * as SecureStore from 'expo-secure-store';
import Constants from "expo-constants";
import * as Random from 'expo-random';
import moment from "moment";
import { create, CancelToken } from 'apisauce';
import * as WebBrowser from 'expo-web-browser';

import { badServerConnectionToast, popToast } from "../components/loadError";

export async function isLoggedIn() {
    const api = create({ baseURL: global.libraryUrl + '/API', timeout: 5000 });
    const response = await api.get('/UserAPI?method=isLoggedIn');

    if(response.ok) {
        const result = response.data;
        console.log(result);
        return result;

    } else {
        badServerConnectionToast();
    }
}

/* ACTIONS ON CHECKOUTS */
export async function renewCheckout(barcode) {

    const api = create({ baseURL: global.libraryUrl + '/API', timeout: 5000 });
    const response = await api.get('/UserAPI?method=renewItem', { username: global.userKey, password: global.secretKey, itemBarcode: barcode });

    if(response.ok) {
        const result = response.data;
        const fetchedData = result.result;

        if (fetchedData.success == true) {
            if (fetchedData.renewalMessage.success == true) {
                Toast.show({
                    title: "Title renewed",
                    description: fetchedData.renewalMessage.message,
                    status: "success",
                    isClosable: true,
                    duration: 8000,
                    accessibilityAnnouncement: fetchedData.renewalMessage.message,
                    zIndex: 9999,
                    placement: "top"
                });
            } else {
                Toast.show({
                    title: "Unable to renew title",
                    description: fetchedData.renewalMessage.message,
                    status: "error",
                    isClosable: true,
                    duration: 8000,
                    accessibilityAnnouncement: fetchedData.renewalMessage.message,
                    zIndex: 9999,
                    placement: "top"
                });
            }
        } else {
            console.log("Connection made, but title not renewed because: " + fetchedData.renewalMessage.message)
        }

    } else {
        badServerConnectionToast();
    }

}

export async function renewAllCheckouts() {

    const api = create({ baseURL: global.libraryUrl + '/API', timeout: 5000 });
    const response = await api.get('/UserAPI?method=renewAll', { username: global.userKey, password: global.secretKey });

    if(response.ok) {
        const result = response.data;
        console.log(result);
        const fetchedData = result.result;

        if (fetchedData.renewalMessage.success == true) {
            Toast.show({
                title: "Renew All",
                description: fetchedData.renewalMessage[0],
                status: "success",
                isClosable: true,
                duration: 8000,
                accessibilityAnnouncement: fetchedData.renewalMessage[0],
                zIndex: 9999,
                placement: "top"
            });
        } else {
            Toast.show({
                title: "Issue with Renew All",
                description: fetchedData.renewalMessage[0],
                status: "warning",
                isClosable: true,
                duration: 8000,
                accessibilityAnnouncement: fetchedData.renewalMessage[0],
                zIndex: 9999,
                placement: "top"
            });
        }

    } else {
        badServerConnectionToast();
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
        const results = response.data;

        if (results.result.success == true) {
            Toast.show({
                title: "Title returned",
                description: results.result.message,
                status: "success",
                isClosable: true,
                duration: 8000,
                accessibilityAnnouncement: results.result.message,
                zIndex: 9999,
                placement: "top"
            });
        } else {
            Toast.show({
                title: "Unable to return title",
                description: results.result.message,
                status: "error",
                isClosable: true,
                duration: 8000,
                accessibilityAnnouncement: results.result.message,
                zIndex: 9999,
                placement: "top"
            });
        }

    } else {
        badServerConnectionToast();
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
                            popToast("Unable to open", "We are having problems opening this item, please try accessing using a browser", "info");
                        });
                    } catch(error) {
                          popToast("Unable to open", "We are having problems opening this item, please try accessing using a browser", "info");
                    }

                } else {
                    popToast("Unable to open", "We are having problems opening this item, please try accessing using a browser", "info");
                }
              });
        } else {
            badServerConnectionToast();
        }
    } else {
        console.log(accessOnlineUrl);
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
                      popToast("Unable to open", "We are having problems opening this item, please try accessing using a browser", "info");
                    });
                } catch(error) {
                    popToast("Unable to open", "We are having problems opening this item, please try accessing using a browser", "info");
                }

            } else {
              console.log("Unable to open browser window.");
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
              console.log("Unable to open browser window.");
            }
          });


    } else {
        badServerConnectionToast();
    }
}

/* ACTIONS ON HOLDS */
export async function freezeHold(cancelId, recordId, source) {

    const today = moment();
    const reactivationDate = moment().add(30, 'days').format('YYYY-MM-DD');
    const api = create({ baseURL: global.libraryUrl + '/API', timeout: 3000 });
    const response = await api.get('/UserAPI?method=freezeHold', { username: global.userKey, password: global.secretKey, sessionId: global.sessionId, holdId: cancelId, recordId: recordId, itemSource: source, reactivationDate: reactivationDate, patronId: global.patronId });

    if(response.ok) {
        const result = response.data;
        const fetchedData = result.result;

        if(fetchedData.success == true) {
            Toast.show({
                title: "Hold frozen",
                description: fetchedData.message,
                status: "success",
                isClosable: true,
                duration: 8000,
                accessibilityAnnouncement: fetchedData.message,
                zIndex: 9999,
                placement: "top"
            });

        } else {
            Toast.show({
                title: "Unable to freeze hold",
                description: fetchedData.message,
                status: "error",
                isClosable: true,
                duration: 8000,
                accessibilityAnnouncement: fetchedData.message,
                zIndex: 9999,
                placement: "top"
            });
        }

    } else {
        badServerConnectionToast();
    }
}

export async function thawHold(cancelId, recordId, source) {
    const api = create({ baseURL: global.libraryUrl + '/API', timeout: 5000 });
    const response = await api.get('/UserAPI?method=activateHold', { username: global.userKey, password: global.secretKey, sessionId: global.sessionId, holdId: cancelId, recordId: recordId, itemSource: source, patronId: global.patronId });

    if(response.ok) {
        const result = response.data;
        const fetchedData = result.result;

        if(fetchedData.success == true) {
            Toast.show({
                title: "Hold thawed",
                description: fetchedData.message,
                status: "success",
                isClosable: true,
                duration: 8000,
                accessibilityAnnouncement: fetchedData.message,
                zIndex: 9999,
                placement: "top",
            });

        } else {
            Toast.show({
                title: "Unable to thaw hold",
                description: fetchedData.message,
                status: "error",
                isClosable: true,
                duration: 8000,
                accessibilityAnnouncement: fetchedData.message,
                zIndex: 9999,
                placement: "top"
            });
        }

    } else {
        badServerConnectionToast();
    }
}

export async function cancelHold(cancelId, recordId, source) {
    const api = create({ baseURL: global.libraryUrl + '/API', timeout: 3000 });
    const response = await api.get('/UserAPI?method=cancelHold', { username: global.userKey, password: global.secretKey, sessionId: global.sessionId, cancelId: cancelId, recordId: recordId, itemSource: source, patronId: global.patronId });

    if(response.ok) {
        const result = response.data;
                console.log(result);
        const fetchedData = result.result;

        if(fetchedData.success == true) {
            Toast.show({
                title: "Hold canceled",
                description: fetchedData.message,
                status: "success",
                isClosable: true,
                duration: 8000,
                accessibilityAnnouncement: fetchedData.message,
                zIndex: 9999,
                placement: "top",
            });

        } else {
            Toast.show({
                title: "Unable to cancel hold",
                description: fetchedData.message,
                status: "error",
                isClosable: true,
                duration: 8000,
                accessibilityAnnouncement: fetchedData.message,
                zIndex: 9999,
                placement: "top"
            });
        }

    } else {
        badServerConnectionToast();
    }
}

export async function changeHoldPickUpLocation(holdId, newLocation) {
    const api = create({ baseURL: global.libraryUrl + '/API', timeout: 3000 });
    const response = await api.get('/UserAPI?method=changeHoldPickUpLocation', { username: global.userKey, password: global.secretKey, sessionId: global.sessionId, holdId: holdId, location: newLocation });

    if(response.ok) {
        const result = response.data;
        console.log(result);
        const fetchedData = result.result;

        if(fetchedData.success == true) {
            Toast.show({
                title: "Pickup location updated",
                description: fetchedData.message,
                status: "success",
                isClosable: true,
                duration: 8000,
                accessibilityAnnouncement: fetchedData.message,
                zIndex: 9999,
                placement: "top",
            });

        } else {
            Toast.show({
                title: "Unable to update pickup location",
                description: fetchedData.message,
                status: "error",
                isClosable: true,
                duration: 8000,
                accessibilityAnnouncement: fetchedData.message,
                zIndex: 9999,
                placement: "top"
            });
        }

    } else {
        badServerConnectionToast();
    }
}

export async function updateOverDriveEmail(itemId, source, patronId, overdriveEmail, promptForOverdriveEmail) {
    const api = create({ baseURL: global.libraryUrl + '/API', timeout: 5000 });
    const response = await api.get('/UserAPI?method=updateOverDriveEmail', { username: global.userKey, password: global.secretKey, itemId: itemId, itemSource: source, patronId: patronId, overdriveEmail: overdriveEmail, promptForOverdriveEmail: promptForOverdriveEmail });

    if(response.ok) {
        const responseData = response.data;
        const results = responseData.result;
        return results;
    } else {
        badServerConnectionToast();
    }
}