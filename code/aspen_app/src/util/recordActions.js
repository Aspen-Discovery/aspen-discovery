import { create } from 'apisauce';
import * as WebBrowser from 'expo-web-browser';

// custom components and helper files
import { popAlert, popToast } from '../components/loadError';
import { translate } from '../translations/translations';
import { createAuthTokens, getHeaders, postData, problemCodeMap } from './apiAuth';
import { GLOBALS } from './globals';
import { getHolds } from './loadPatron';
import { LIBRARY } from './loadLibrary';

/**
 * Fetch information for GroupedWork
 *
 * Parameters:
 * <ul>
 *     <li>itemId - the GroupedWork id for the record</li>
 * </ul>
 **/
export async function getGroupedWork(url, itemId) {
     let baseUrl = url ?? LIBRARY.url;
     const api = create({
          baseURL: baseUrl + '/API',
          timeout: GLOBALS.timeoutSlow,
          headers: getHeaders(),
          auth: createAuthTokens(),
     });
     const response = await api.get('/ItemAPI?method=getAppGroupedWork', {
          id: itemId,
     });
     if (response.ok) {
          return response.data;
     } else {
          popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), 'warning');
          console.log(response);
     }
}

/**
 * Checkout item to patron
 *
 * Parameters:
 * <ul>
 *     <li>itemId - the id for the record</li>
 *     <li>source - the source of the item, i.e. ils, hoopla, overdrive. If left empty, Aspen assumes ils.</li>
 *     <li>patronId - the id for the patron</li>
 * </ul>
 * @param {string} url
 * @param {number} itemId
 * @param {string} source
 * @param {number} patronId
 **/
export async function checkoutItem(url, itemId, source, patronId) {
     const postBody = await postData();
     const api = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               itemId,
               itemSource: source,
               userId: patronId,
          },
     });
     const response = await api.post('/UserAPI?method=checkoutItem', postBody);
     if (response.ok) {
          const responseData = response.data;
          // reload patron data in the background
          //await getCheckedOutItems(url);

          return responseData.result;
     } else {
          popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), 'warning');
          console.log(response);
     }
}

/**
 * Place hold on item for patron
 *
 * Parameters:
 * <ul>
 *     <li>itemId - the id for the record</li>
 *     <li>source - the source of the item, i.e. ils, hoopla, overdrive. If left empty, Aspen assumes ils.</li>
 *     <li>patronId - the id for the patron</li>
 *     <li>pickupBranch - the location id for where the hold will be picked up at</li>
 * </ul>
 **/
export async function placeHold(url, itemId, source, patronId, pickupBranch, volumeId = null, holdType = null, recordId = null) {
     const postBody = await postData();
     const api = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               itemId,
               itemSource: source,
               userId: patronId,
               pickupBranch,
               volumeId,
               holdType,
               recordId,
          },
     });
     const response = await api.post('/UserAPI?method=placeHold', postBody);
     if (response.ok) {
          const responseData = response.data;
          const results = responseData.result;

          // reload patron data in the background
          await getHolds(url);

          return results;
     } else {
          popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), 'warning');
          console.log(response);
     }
}

export async function overDriveSample(url, formatId, itemId, sampleNumber) {
     const postBody = await postData();
     const api = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               overDriveId: itemId,
               formatId,
               sampleNumber,
               itemSource: 'overdrive',
               isPreview: 'true',
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

export async function openSideLoad(redirectUrl) {
     if (redirectUrl) {
          await WebBrowser.openBrowserAsync(redirectUrl)
               .then((res) => {
                    console.log(res);
               })
               .catch(async (err) => {
                    if (err.message === 'Another WebBrowser is already being presented.') {
                         try {
                              WebBrowser.dismissBrowser();
                              await WebBrowser.openBrowserAsync(redirectUrl)
                                   .then((response) => {
                                        console.log(response);
                                   })
                                   .catch(async (error) => {
                                        console.log('Unable to close previous browser session.');
                                        popToast(translate('error.no_open_resource'), translate('error.device_block_browser'), 'warning');
                                   });
                         } catch (error) {
                              console.log('Tried to open again but still unable');
                              popToast(translate('error.no_open_resource'), translate('error.device_block_browser'), 'warning');
                         }
                    } else {
                         console.log('Unable to open browser window.');
                         popToast(translate('error.no_open_resource'), translate('error.device_block_browser'), 'warning');
                    }
               });
     } else {
          popToast(translate('error.no_open_resource'), translate('error.no_valid_url'), 'warning');
          console.log(response);
     }
}

export async function getItemDetails(url, id, format) {
     const postBody = await postData();
     const api = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               recordId: id,
               format,
          },
     });
     const response = await api.post('/ItemAPI?method=getItemDetails', postBody);
     //console.log(response.config);
     if (response.ok) {
          return response.data;
     } else {
          popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), 'warning');
          console.log(response);
     }
}

export async function submitVdxRequest(url, request) {
     const postBody = await postData();
     const api = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               title: request.title,
               author: request.author,
               publisher: request.publisher,
               isbn: request.isbn,
               maximumFeeAmount: request.maximumFeeAmount,
               acceptFee: request.acceptFee,
               pickupLocation: request.pickupLocation,
               catalogKey: request.catalogKey,
               note: request.note,
          },
     });
     const response = await api.post('/UserAPI?method=submitVdxRequest', postBody);
     if (response.ok) {
          if (response.data.result.success === true) {
               popAlert(response.data.result.title, response.data.result.message, 'success');
          } else {
               popAlert(response.data.result.title ?? 'Unknown Error', response.data.result.message, 'error');
          }
          return response.data;
     } else {
          const problem = problemCodeMap(response.problem);
          popAlert(problem.title, problem.message, 'warning');
          console.log(response);
     }
}