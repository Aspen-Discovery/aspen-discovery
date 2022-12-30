export function getStatusIndicator(status) {
     let label = null;
     let message = '';
     let indicator = 'success';
     if (status) {
          if (status['isEContent']) {
               if (status['isShowStatus']) {
                    if (status['isAvailableOnline']) {
                         label = 'Available Online';
                    } else {
                         if (status['groupedStatus'] === 'On Order') {
                              label = 'On Order';
                              indicator = 'danger';
                         } else {
                              label = 'Checked Out';
                              indicator = 'danger';
                         }
                    }
               }
          } else {
               if (status['isAvailableHere']) {
                    if (status['isAllLibraryUseOnly']) {
                         label = "It's Here (library use only)";
                    } else {
                         if (status['showItsHere']) {
                              label = "It's Here";
                         } else {
                              label = 'On Shelf';
                         }
                    }
               } else if (status['isAvailableLocally']) {
                    if (status['isAllLibraryUseOnly']) {
                         label = 'Library use only';
                    } else {
                         label = 'On Shelf';
                    }
               } else if (status['isAllLibraryUseOnly']) {
                    if (status['isAvailable'] === false && status['hasLocalItem']) {
                         label = 'Checked Out / Available Elsewhere';
                         indicator = 'warning';
                    } else if (status['isAvailable']) {
                         if (status['hasLocalItem']) {
                              label = 'Library use only';
                         } else {
                              label = 'Available from another library';
                              indicator = 'warning';
                         }
                    } else {
                         label = 'Checked Out (library use only)';
                         indicator = 'danger';
                    }
               } else if (status['isAvailable']) {
                    if (status['hasLocalItem']) {
                         label = 'On Shelf';
                    } else {
                         label = 'Available from another library';
                         indicator = 'warning';
                    }
               } else {
                    indicator = 'danger';
                    if (status['groupedStatus']) {
                         label = 'Checked Out';
                    } else {
                         label = 'Withdrawn / Unavailable';
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