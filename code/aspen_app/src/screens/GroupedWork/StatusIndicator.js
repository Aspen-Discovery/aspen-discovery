import {getTermFromDictionary} from '../../translations/TranslationService';

export function getStatusIndicator(status, language) {
     let label = null;
     let message = '';
     let indicator = 'success';
     if (status) {
          if (status['isEContent']) {
               if (status['isShowStatus']) {
                    if (status['isAvailableOnline']) {
                         label = getTermFromDictionary(language, 'status_available_online');
                    } else {
                         if (status['groupedStatus'] === 'On Order') {
                              label = getTermFromDictionary(language, 'status_on_order');
                              indicator = 'error';
                         } else {
                              label = getTermFromDictionary(language, 'status_checked_out');
                              indicator = 'error';
                         }
                    }
               }
          } else {
               if (status['isAvailableHere']) {
                    if (status['isAllLibraryUseOnly']) {
                         label = getTermFromDictionary(language, 'status_its_here_library_use_only');
                    } else {
                         if (status['showItsHere']) {
                              label = getTermFromDictionary(language, 'status_its_here');
                         } else {
                              label = getTermFromDictionary(language, 'status_on_shelf');
                         }
                    }
               } else if (status['isAvailableLocally']) {
                    if (status['isAllLibraryUseOnly']) {
                         label = getTermFromDictionary(language, 'status_library_use_only');
                    } else {
                         label = getTermFromDictionary(language, 'status_on_shelf');
                    }
               } else if (status['isAllLibraryUseOnly']) {
                    if(status['isGlobalScope']) {
                         label = getTermFromDictionary(language, 'status_on_shelf_library_use_only')
                    } else {
                         if (status['isAvailable'] === false && status['hasLocalItem']) {
                              label = getTermFromDictionary(language, 'status_checked_out_available_elsewhere');
                              indicator = 'warning';
                         } else if (status['isAvailable']) {
                              if (status['hasLocalItem']) {
                                   label = getTermFromDictionary(language, 'status_library_use_only');
                              } else {
                                   label = getTermFromDictionary(language, 'status_available_elsewhere');
                                   indicator = 'warning';
                              }
                         } else {
                              label = getTermFromDictionary(language, 'status_checked_out_library_use_only');
                              indicator = 'error';
                         }
                    }
               } else if (status['isAvailable'] && status['isAvailableLocally'] === false && status['hasLocalItem']) {
                    label = getTermFromDictionary(language, 'status_checked_out_available_elsewhere');
                    indicator = 'warning';
               } else if (status['isAvailable']) {
                    if(status['isGlobalScope']) {
                         label = getTermFromDictionary(language, 'status_on_shelf');
                    } else {
                         if (status['hasLocalItem']) {
                              label = getTermFromDictionary(language, 'status_on_shelf');
                         } else {
                              label = getTermFromDictionary(language, 'status_available_elsewhere');
                              indicator = 'warning';
                         }
                    }
               } else {
                    indicator = 'error';
                    if (status['groupedStatus']) {
                         label = status['groupedStatus'];
                    } else {
                         label = getTermFromDictionary(language, 'status_withdrawn');
                    }
               }
          }

          if ((status['numHolds'] > 0 || status['onOrderCopies'] > 0) && status['showGroupedHoldCopiesCount']) {
               message = status['numCopiesMessage'];
          }
     }

     return {
          label: label,
          indicator: indicator,
          message: message,
     };
}

export function getBasicStatusIndicator(status) {
     let indicator = 'success';

     if (status) {
          if (status === 'Checked Out' || status === 'On Order' || status === 'Withdrawn / Unavailable' || status === 'Checked Out (library use only') {
               indicator = 'danger';
          }
          if (status === 'Available from another library' || status === 'Checked Out / Available Elsewhere' || status === 'Available from another library') {
               indicator = 'warning';
          }
     }

     return {
          label: status,
          indicator: indicator,
     };
}