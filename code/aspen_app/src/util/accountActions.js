import { create } from 'apisauce';
import * as WebBrowser from 'expo-web-browser';
import i18n from 'i18n-js';
import moment from 'moment';
import React from 'react';

// custom components and helper files
import { popAlert, popToast } from '../components/loadError';
import { translate } from '../translations/translations';
import { createAuthTokens, getHeaders, postData, problemCodeMap } from './apiAuth';
import { GLOBALS } from './globals';
import { getBrowseCategories, LIBRARY } from './loadLibrary';
import { getLinkedAccounts, getPatronBrowseCategories, getProfile, PATRON, reloadCheckedOutItems, reloadHolds } from './loadPatron';

export async function isLoggedIn(pathUrl) {
     const postBody = await postData();
     const api = create({
          baseURL: pathUrl + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
     });
     const response = await api.post('/UserAPI?method=isLoggedIn', postBody);

     if (response.ok) {
          console.log(response.data);
          return response.data.result;
     } else {
          console.log(response);
          return response.problem;
     }
}

/* ACTIONS ON CHECKOUTS */
export async function renewCheckout(barcode, recordId, source, itemId, libraryUrl, userId) {
     let validId;
     if (itemId == null) {
          validId = barcode;
     } else {
          validId = itemId;
     }

     const postBody = await postData();
     const api = create({
          baseURL: LIBRARY.url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          params: {
               itemBarcode: validId,
               recordId,
               itemSource: source,
               userId,
          },
          auth: createAuthTokens(),
     });
     const response = await api.post('/UserAPI?method=renewItem', postBody);

     console.log(response);
     if (response.ok) {
          const fetchedData = response.data;
          const result = fetchedData.result;

          if (source === 'ils') {
               if (result.success === true) {
                    popAlert(result.title, result.message, 'success');
                    //await reloadCheckedOutItems();
               } else {
                    popAlert(result.title, result.message, 'error');
               }
          } else {
               if (result.success === true) {
                    popAlert(result.title, result.message, 'success');
                    //await reloadCheckedOutItems();
               } else {
                    popAlert(result.title, result.message, 'error');
               }
          }
     } else {
          console.log(response);
     }
}

export async function renewAllCheckouts() {
     const postBody = await postData();
     const api = create({
          baseURL: LIBRARY.url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
     });
     const response = await api.post('/UserAPI?method=renewAll', postBody);
     //console.log(response);
     if (response.ok) {
          const fetchedData = response.data;
          const result = fetchedData.result;

          if (result.success === true) {
               popAlert(result.title, result.renewalMessage[0], 'success');
               //await reloadCheckedOutItems();
          } else {
               popAlert(result.title, result.renewalMessage[0], 'error');
          }
     } else {
          popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), 'warning');
          console.log(response);
     }
}

export async function returnCheckout(userId, id, source, overDriveId, url) {
     const postBody = await postData();

     let itemId = id;
     if (overDriveId != null) {
          itemId = overDriveId;
     }
     if (LIBRARY.version >= '22.05.00') {
          const api = create({
               baseURL: url + '/API',
               timeout: GLOBALS.timeoutFast,
               headers: getHeaders(true),
               auth: createAuthTokens(),
               params: {
                    itemId,
                    userId,
                    itemSource: source,
               },
          });
          const response = await api.post('/UserAPI?method=returnCheckout', postBody);
          console.log(response);

          if (response.ok) {
               const fetchedData = response.data;
               const result = fetchedData.result;

               if (result.success === true) {
                    popAlert(result.title, result.message, 'success');
                    //await reloadCheckedOutItems();
               } else {
                    popAlert(result.title, result.message, 'error');
               }
          } else {
               popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), 'warning');
               console.log(response);
          }
     } else {
          const api = create({
               baseURL: url + '/API',
               timeout: GLOBALS.timeoutFast,
               headers: getHeaders(true),
               auth: createAuthTokens(),
               params: {
                    id: itemId,
                    userId,
                    itemSource: source,
               },
          });
          const response = await api.post('/UserAPI?method=returnCheckout', postBody);
          console.log(response);

          if (response.ok) {
               const fetchedData = response.data;
               const result = fetchedData.result;

               if (result.success === true) {
                    popAlert(result.title, result.message, 'success');
                    //await reloadCheckedOutItems();
               } else {
                    popAlert(result.title, result.message, 'error');
               }
          } else {
               popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), 'warning');
               console.log(response);
          }
     }
}

export async function viewOnlineItem(userId, id, source, accessOnlineUrl, url) {
     const postBody = await postData();

     if (source === 'hoopla') {
          const api = create({
               baseURL: url + '/API',
               timeout: GLOBALS.timeoutFast,
               headers: getHeaders(),
               auth: createAuthTokens(),
               params: {
                    userId,
                    itemId: id,
                    itemSource: source,
               },
          });
          const response = await api.post('/UserAPI?method=viewOnlineItem', postBody);

          if (response.ok) {
               const results = response.data;
               const result = results.result.url;

               await WebBrowser.openBrowserAsync(result)
                    .then((res) => {
                         console.log(res);
                    })
                    .catch(async (err) => {
                         if (err.message === 'Another WebBrowser is already being presented.') {
                              try {
                                   WebBrowser.dismissBrowser();
                                   await WebBrowser.openBrowserAsync(result)
                                        .then((response) => {
                                             console.log(response);
                                        })
                                        .catch(async (error) => {
                                             popToast(translate('error.no_open_resource'), translate('error.device_block_browser'), 'warning');
                                        });
                              } catch (error) {
                                   console.log('Really borked.');
                              }
                         } else {
                              popToast(translate('error.no_open_resource'), translate('error.device_block_browser'), 'warning');
                         }
                    });
          } else {
               popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), 'warning');
          }
     } else {
          await WebBrowser.openBrowserAsync(accessOnlineUrl)
               .then((res) => {
                    console.log(res);
               })
               .catch(async (err) => {
                    if (err.message === 'Another WebBrowser is already being presented.') {
                         try {
                              WebBrowser.dismissBrowser();
                              await WebBrowser.openBrowserAsync(accessOnlineUrl)
                                   .then((response) => {
                                        console.log(response);
                                   })
                                   .catch((error) => {
                                        console.log(error);
                                        popToast(translate('error.no_open_resource'), translate('error.device_block_browser'), 'warning');
                                   });
                         } catch (error) {
                              console.log(error);
                              console.log('Unable to open.');
                         }
                    } else {
                         popToast(translate('error.no_open_resource'), translate('error.device_block_browser'), 'warning');
                    }
               });
     }
}

export async function viewOverDriveItem(userId, formatId, overDriveId, url) {
     const postBody = await postData();

     const api = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(),
          auth: createAuthTokens(),
          params: {
               userId,
               overDriveId,
               formatId,
               itemSource: 'overdrive',
          },
     });
     const response = await api.post('/UserAPI?method=viewOnlineItem', postBody);

     if (response.ok) {
          const result = response.data;
          const accessUrl = result.result.url;

          await WebBrowser.openBrowserAsync(accessUrl)
               .then((res) => {
                    console.log(res);
               })
               .catch(async (err) => {
                    if (err.message === 'Another WebBrowser is already being presented.') {
                         try {
                              WebBrowser.dismissBrowser();
                              await WebBrowser.openBrowserAsync(accessUrl)
                                   .then((response) => {
                                        console.log(response);
                                   })
                                   .catch(async (error) => {
                                        console.log('Unable to close previous browser session.');
                                   });
                         } catch (error) {
                              console.log('Really borked.');
                         }
                    } else {
                         popToast(translate('error.no_open_resource'), translate('error.device_block_browser'), 'warning');
                    }
               });
     } else {
          popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), 'warning');
          console.log(response);
     }
}

/* ACTIONS ON HOLDS */
export async function freezeHold(cancelId, recordId, source, url, patronId, selectedReactivationDate = null) {
     const postBody = await postData();

     const today = moment().format('YYYY-MM-DD');
     let reactivationDate = null;
     if (selectedReactivationDate) {
          reactivationDate = moment(selectedReactivationDate).format('YYYY-MM-DD');
          if (reactivationDate === 'Invalid date') {
               reactivationDate = null;
          } else if (reactivationDate === today) {
               reactivationDate = moment().add(30, 'days').format('YYYY-MM-DD');
          }
     } else {
          reactivationDate = moment().add(30, 'days').format('YYYY-MM-DD');
     }

     const api = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               sessionId: GLOBALS.appSessionId,
               holdId: cancelId,
               recordId,
               itemSource: source,
               reactivationDate,
               userId: patronId,
          },
     });
     const response = await api.post('/UserAPI?method=freezeHold', postBody);

     if (response.ok) {
          //console.log(response);
          const fetchedData = response.data;
          const result = fetchedData.result;

          if (result.success === true) {
               popAlert('Hold frozen', result.message, 'success');
               // reload patron data in the background
               //await reloadHolds(libraryUrl);
          } else {
               popAlert('Unable to freeze hold', result.message, 'error');
          }
     } else {
          popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), 'warning');
          console.log(response);
     }
}

export async function freezeHolds(data, url, selectedReactivationDate = null) {
     const postBody = await postData();

     const today = moment().format('YYYY-MM-DD');
     let reactivationDate = null;
     if (selectedReactivationDate) {
          reactivationDate = moment(selectedReactivationDate).format('YYYY-MM-DD');
          if (reactivationDate === 'Invalid date') {
               reactivationDate = null;
          } else if (reactivationDate === today) {
               reactivationDate = moment().add(30, 'days').format('YYYY-MM-DD');
          }
     } else {
          reactivationDate = moment().add(30, 'days').format('YYYY-MM-DD');
     }

     let numSuccess = 0;
     let numFailed = 0;

     const holdsToFreeze = data.map(async (hold, index) => {
          const api = create({
               baseURL: url + '/API',
               timeout: GLOBALS.timeoutFast,
               headers: getHeaders(true),
               auth: createAuthTokens(),
               params: {
                    sessionId: GLOBALS.appSessionId,
                    holdId: hold.cancelId,
                    recordId: hold.recordId,
                    itemSource: hold.source,
                    reactivationDate,
                    userId: hold.patronId,
               },
          });
          const response = await api.post('/UserAPI?method=freezeHold', postBody);
          if (response.ok) {
               const fetchedData = response.data;
               const result = fetchedData.result;

               if (result.success === true) {
                    numSuccess = numSuccess + 1;
               } else {
                    numFailed = numFailed + 1;
               }
          } else {
               popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), 'warning');
               console.log(response);
          }
     });

     //await reloadHolds();

     let message = '';
     let status = 'success';
     if (numSuccess > 0) {
          message = message.concat(numSuccess + ' holds frozen successfully.');
     }

     if (numFailed > 0) {
          status = 'warning';
          message = message.concat(' Unable to freeze ' + numFailed + ' holds.');
     }
     popAlert('Holds frozen', message, status);
}

export async function thawHold(cancelId, recordId, source, url, patronId) {
     const postBody = await postData();

     const api = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               sessionId: GLOBALS.appSessionId,
               holdId: cancelId,
               recordId,
               itemSource: source,
               userId: patronId,
          },
     });
     const response = await api.post('/UserAPI?method=activateHold', postBody);

     if (response.ok) {
          const fetchedData = response.data;
          const result = fetchedData.result;

          if (result.success === true) {
               popAlert('Hold thawed', result.message, 'success');
               // reload patron data in the background
               //await reloadHolds();
          } else {
               popAlert('Unable to thaw hold', result.message, 'error');
          }
     } else {
          popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), 'warning');
          console.log(response);
     }
}

export async function thawHolds(data, url) {
     const postBody = await postData();

     let numSuccess = 0;
     let numFailed = 0;

     const holdsToThaw = data.map(async (hold, index) => {
          const api = create({
               baseURL: url + '/API',
               timeout: GLOBALS.timeoutFast,
               headers: getHeaders(true),
               auth: createAuthTokens(),
               params: {
                    sessionId: GLOBALS.appSessionId,
                    holdId: hold.cancelId,
                    recordId: hold.recordId,
                    itemSource: hold.source,
                    userId: hold.patronId,
               },
          });
          const response = await api.post('/UserAPI?method=activateHold', postBody);
          if (response.ok) {
               const fetchedData = response.data;
               const result = fetchedData.result;

               if (result.success === true) {
                    numSuccess = numSuccess + 1;
               } else {
                    numFailed = numFailed + 1;
               }
          } else {
               popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), 'warning');
               console.log(response);
          }
     });

     //await reloadHolds();

     let message = '';
     let status = 'success';
     if (numSuccess > 0) {
          message = message.concat(numSuccess + ' holds thawed successfully.');
     }

     if (numFailed > 0) {
          status = 'warning';
          message = message.concat(' Unable to thaw ' + numFailed + ' holds.');
     }
     popAlert('Holds thawed', message, status);
}

export async function cancelHold(cancelId, recordId, source, url, patronId) {
     const postBody = await postData();
     const api = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               sessionId: GLOBALS.appSessionId,
               cancelId,
               recordId,
               itemSource: source,
               userId: patronId,
          },
     });
     const response = await api.post('/UserAPI?method=cancelHold', postBody);

     if (response.ok) {
          const fetchedData = response.data;
          const result = fetchedData.result;

          if (result.success === true) {
               popAlert(result.title, result.message, 'success');
               // reload patron data in the background
               //await reloadHolds();
          } else {
               popAlert(result.title, result.message, 'error');
          }

          //await getProfile();
     } else {
          popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), 'warning');
          console.log(response);
     }
}

export async function cancelHolds(data, url) {
     const postBody = await postData();

     let numSuccess = 0;
     let numFailed = 0;

     const holdsToCancel = data.map(async (hold, index) => {
          const api = create({
               baseURL: url + '/API',
               timeout: GLOBALS.timeoutFast,
               headers: getHeaders(true),
               auth: createAuthTokens(),
               params: {
                    sessionId: GLOBALS.appSessionId,
                    cancelId: hold.cancelId,
                    recordId: hold.recordId,
                    itemSource: hold.source,
                    userId: hold.patronId,
               },
          });
          const response = await api.post('/UserAPI?method=cancelHold', postBody);
          if (response.ok) {
               const fetchedData = response.data;
               const result = fetchedData.result;

               if (result.success === true) {
                    numSuccess = numSuccess + 1;
               } else {
                    console.log(response);
                    numFailed = numFailed + 1;
               }
          } else {
               popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), 'warning');
               console.log(response);
          }
     });

     //await reloadHolds();

     let message = '';
     let status = 'success';
     if (numSuccess > 0) {
          message = message.concat(numSuccess + ' holds cancelled successfully.');
     }

     if (numFailed > 0) {
          status = 'warning';
          message = message.concat(' Unable to cancel ' + numFailed + ' holds.');
     }
     popAlert('Holds cancelled', message, status);
}

export async function changeHoldPickUpLocation(holdId, newLocation, url = null, userId) {
     let baseUrl = url ?? LIBRARY.url;
     const postBody = await postData();
     const api = create({
          baseURL: baseUrl + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               sessionId: GLOBALS.appSessionId,
               holdId,
               newLocation,
               userId,
          },
     });
     const response = await api.post('/UserAPI?method=changeHoldPickUpLocation', postBody);

     if (response.ok) {
          const fetchedData = response.data;
          const result = fetchedData.result;

          if (result.success === true) {
               //console.log(result);
               popAlert(result.title, result.message, 'success');
               // reload patron data in the background
               //await reloadHolds();
          } else {
               popAlert(result.title, result.message, 'error');
          }
     } else {
          popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), 'warning');
          console.log(response);
     }
}

export async function updateOverDriveEmail(itemId, source, patronId, overdriveEmail, promptForOverdriveEmail, libraryUrl) {
     const postBody = await postData();
     const api = create({
          baseURL: LIBRARY.url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               itemId,
               itemSource: source,
               userId: patronId,
               overdriveEmail,
               promptForOverdriveEmail,
          },
     });
     const response = await api.post('/UserAPI?method=updateOverDriveEmail', postBody);

     if (response.ok) {
          const responseData = response.data;
          const result = responseData.result;
          // reload patron data in the background
          return result;
     } else {
          popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), 'warning');
          console.log(response);
     }
}

/* ACTIONS ON BROWSE CATEGORIES */
export async function dismissBrowseCategory(libraryUrl, browseCategoryId, patronId) {
     const postBody = await postData();
     if (LIBRARY.version >= '22.05.00') {
          const api = create({
               baseURL: LIBRARY.url + '/API',
               timeout: GLOBALS.timeoutAverage,
               headers: getHeaders(true),
               auth: createAuthTokens(),
               params: { browseCategoryId },
          });
          const response = await api.post('/UserAPI?method=dismissBrowseCategory', postBody);
          console.log(response);
          if (response.ok) {
               return response.data;
          } else {
               const problem = problemCodeMap(response.problem);
               popToast(problem.title, problem.message, 'warning');
               console.log(response);
          }
     } else {
          const api = create({
               baseURL: LIBRARY.url + '/API',
               timeout: GLOBALS.timeoutAverage,
               headers: getHeaders(true),
               auth: createAuthTokens(),
               params: {
                    browseCategoryId,
                    patronId,
               },
          });
          const response = await api.post('/UserAPI?method=dismissBrowseCategory', postBody);
          console.log(response);
          if (response.ok) {
               return response.data;
          } else {
               const problem = problemCodeMap(response.problem);
               popToast(problem.title, problem.message, 'warning');
               console.log(response);
          }
     }
}

export async function showBrowseCategory(browseCategoryId, patronId) {
     const postBody = await postData();

     if (LIBRARY.version >= '22.05.00') {
          const api = create({
               baseURL: LIBRARY.url + '/API',
               timeout: GLOBALS.timeoutAverage,
               headers: getHeaders(true),
               auth: createAuthTokens(),
               params: { browseCategoryId },
          });
          const response = await api.post('/UserAPI?method=showBrowseCategory', postBody);

          if (response.ok) {
               await getPatronBrowseCategories(LIBRARY.url, patronId);
               await getBrowseCategories(LIBRARY.url, LIBRARY.version);
               return response.data;
          } else {
               const problem = problemCodeMap(response.problem);
               popToast(problem.title, problem.message, 'warning');
               console.log(response);
          }
     } else {
          const api = create({
               baseURL: LIBRARY.url + '/API',
               timeout: GLOBALS.timeoutAverage,
               headers: getHeaders(true),
               auth: createAuthTokens(),
               params: {
                    browseCategoryId,
                    patronId,
               },
          });
          const response = await api.post('/UserAPI?method=showBrowseCategory', postBody);

          if (response.ok) {
               await getPatronBrowseCategories(LIBRARY.url, patronId);
               await getBrowseCategories(LIBRARY.url, LIBRARY.version);
               return response.data;
          } else {
               const problem = problemCodeMap(response.problem);
               popToast(problem.title, problem.message, 'warning');
               console.log(response);
          }
     }
}

export async function addLinkedAccount(username, password) {
     const postBody = await postData();
     postBody.append('accountToLinkUsername', username);
     postBody.append('accountToLinkPassword', password);
     const api = create({
          baseURL: LIBRARY.url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
     });
     const response = await api.post('/UserAPI?method=addAccountLink', postBody);
     if (response.ok) {
          //await getLinkedAccounts();
          if (response.data.result.success) {
               popAlert(response.data.result.title, response.data.result.message, 'success');
          } else {
               popAlert(response.data.result.title, response.data.result.message, 'error');
          }
     } else {
          const problem = problemCodeMap(response.problem);
          popToast(problem.title, problem.message, 'warning');
          console.log(response);
     }
}

export async function removeLinkedAccount(id, libraryUrl) {
     const postBody = await postData();
     const api = create({
          baseURL: LIBRARY.url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
     });
     const response = await api.post('/UserAPI?method=removeAccountLink&idToRemove=' + id, postBody);
     if (response.ok) {
          //await getLinkedAccounts();
          if (response.data.result.success) {
               popAlert(response.data.result.title, response.data.result.message, 'success');
          } else {
               popAlert(response.data.result.title, response.data.result.message, 'error');
          }
     } else {
          const problem = problemCodeMap(response.problem);
          popToast(problem.title, problem.message, 'warning');
          console.log(response);
     }
}

export async function saveLanguage(code) {
     const postBody = await postData();
     const api = create({
          baseURL: LIBRARY.url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(true),
          auth: createAuthTokens(),
     });
     const response = await api.post('/UserAPI?method=saveLanguage&languageCode=' + code, postBody);
     if (response.ok) {
          console.log(response.data);
          i18n.locale = code;
          PATRON.language = code;
          console.log(PATRON.language);
          return code;
     } else {
          console.log(response);
     }
}

export async function cancelVdxRequest(libraryUrl, sourceId, cancelId) {
     const postBody = await postData();
     const api = create({
          baseURL: LIBRARY.url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               sourceId,
               cancelId,
          },
     });
     const response = await api.post('/UserAPI?method=cancelVdxRequest', postBody);
     if (response.ok) {
          if (response.data.result.success === 'true') {
               popAlert(response.data.result.title, response.data.result.message, 'success');
          } else {
               console.log(response);
               popAlert('Error', response.data.result.message, 'error');
          }
     } else {
          const problem = problemCodeMap(response.problem);
          popAlert(problem.title, problem.message, 'warning');
          console.log(response);
     }
}