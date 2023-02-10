import moment from 'moment';
import _ from 'lodash';
import { Badge, Box, Button, Center, Icon, Pressable, Text, HStack, VStack, IconButton, Image } from 'native-base';
import React from 'react';

import { translate } from '../translations/translations';
import { UserContext } from '../context/initialContext';

export const isOverdue = (overdue) => {
     if (overdue) {
          return (
               <Text>
                    <Badge colorScheme="danger" rounded="4px" mt={-0.5}>
                         {translate('checkouts.overdue')}
                    </Badge>
               </Text>
          );
     } else {
          return null;
     }
};

export const getTitle = (title) => {
     if (title) {
          let displayTitle = title;
          const countSlash = displayTitle.split('/').length - 1;
          if (countSlash > 0) {
               displayTitle = displayTitle.substring(0, displayTitle.lastIndexOf('/'));
          }
          return (
               <Text
                    bold
                    mb={1}
                    fontSize={{
                         base: 'sm',
                         lg: 'lg',
                    }}>
                    {displayTitle}
               </Text>
          );
     } else {
          return null;
     }
};

export function getCleanTitle(title) {
     if (title) {
          let displayTitle = title;
          const countSlash = displayTitle.split('/').length - 1;
          if (countSlash > 0) {
               displayTitle = displayTitle.substring(0, displayTitle.lastIndexOf('/'));
          }
          return displayTitle;
     }
     return 'Unknown';
}

export const getAuthor = (author) => {
     if (author) {
          let displayAuthor = author;
          const countComma = displayAuthor.split(',').length - 1;
          if (countComma > 1) {
               displayAuthor = displayAuthor.substring(0, displayAuthor.lastIndexOf(','));
          }

          return (
               <Text
                    fontSize={{
                         base: 'xs',
                         lg: 'sm',
                    }}>
                    <Text bold>{translate('grouped_work.author')}:</Text> {displayAuthor}
               </Text>
          );
     }
     return null;
};

export const getFormat = (format, source = null) => {
     if (format !== 'Unknown') {
         if(source) {
             if(source !== 'ils') {
                 if(source === 'interlibrary_loan') {
                     source = 'Interlibrary Loan';
                 } else if (source === 'axis360') {
                     source = 'Axis 360';
                 } else if (source === 'cloudlibrary') {
                     source = 'CloudLibrary';
                 } else if (source === 'hoopla') {
                     source = 'Hoopla';
                 } else if (source === 'overdrive') {
                     source = 'OverDrive'
                 }
                 return (
                     <Text
                         fontSize={{
                             base: 'xs',
                             lg: 'sm',
                         }}>
                         <Text bold>{translate('grouped_work.format')}:</Text> {format} - {source}
                     </Text>
                 );
             }
         }
          return (
               <Text
                    fontSize={{
                         base: 'xs',
                         lg: 'sm',
                    }}>
                    <Text bold>{translate('grouped_work.format')}:</Text> {format}
               </Text>
          );
     } else {
          return null;
     }
};

export const getBadge = (status, frozen, available, source) => {
     if (frozen) {
          return (
               <Text>
                    <Badge colorScheme="yellow" rounded="4px" mt={-0.5}>
                         {status}
                    </Badge>
               </Text>
          );
     } else if (available) {
          let message = translate('overdrive.hold_ready');
          if (source === 'ils') {
               message = status;
          }
          return (
               <Text>
                    <Badge colorScheme="green" rounded="4px" mt={-0.5}>
                         {message}
                    </Badge>
               </Text>
          );
     } else {
          if(status) {
              return (
                  <Text>
                      <Badge colorScheme="orange" rounded="4px" mt={-0.5}>
                          {status}
                      </Badge>
                  </Text>
              );
          }
     } return null;
};

export const getStatus = (status, source) => {
     if (status) {
          if (source === 'vdx') {
               return (
                    <Text
                         fontSize={{
                              base: 'xs',
                              lg: 'sm',
                         }}>
                         <Text bold>{translate('holds.status')}:</Text> {status}
                    </Text>
               );
          }
     } else {
          return null;
     }
};

export const getType = (type) => {
     if (type && type !== 'ils') {
         if(type === 'interlibrary_loan') {
             type = 'Interlibrary Loan';
         } else if (type === 'axis360') {
             type = 'Axis 360';
         } else if (type === 'cloudlibrary') {
             type = 'CloudLibrary';
         } else if (type === 'hoopla') {
             type = 'Hoopla';
         } else if (type === 'overdrive') {
             type = 'OverDrive'
         }

          return (
               <Text
                    fontSize={{
                         base: 'xs',
                         lg: 'sm',
                    }}>
                    <Text bold>{translate('holds.source')}:</Text> {type}
               </Text>
          );
     } else {
          return null;
     }
};

export const getOnHoldFor = (user) => {
     if (user) {
          return (
               <Text
                    fontSize={{
                         base: 'xs',
                         lg: 'sm',
                    }}>
                    <Text bold>{translate('holds.on_hold_for')}:</Text> {user}
               </Text>
          );
     }
     return null;
};

export const getCheckedOutTo = (props) => {
     const { user } = React.useContext(UserContext);
     const [checkedOutTo, setCheckedOutTo] = React.useState();
     if (user.id !== checkedOutTo) {
          return (
               <Text
                    fontSize={{
                         base: 'xs',
                         lg: 'sm',
                    }}>
                    <Text bold>Checked Out To:</Text> {props}
               </Text>
          );
     } else {
          return null;
     }
};

export const getDueDate = (date) => {
     const dueDate = moment.unix(date);
     const itemDueOn = moment(dueDate).format('MMM D, YYYY');
     return (
          <Text
               fontSize={{
                    base: 'xs',
                    lg: 'sm',
               }}>
               <Text bold>{translate('checkouts.due')}:</Text> {itemDueOn}
          </Text>
     );
};

export const willAutoRenew = (props) => {
     if (props.autoRenew === 1) {
          return (
               <Box mt={1} p={0.5} bgColor="muted.100">
                    <Text
                         fontSize={{
                              base: 'xs',
                              lg: 'sm',
                         }}>
                         <Text bold>{translate('checkouts.auto_renew')}:</Text> {props.renewalDate}
                    </Text>
               </Box>
          );
     } else {
          return null;
     }
};

export const getPickupLocation = (location, source) => {
     if (location && source === 'ils') {
          return (
               <Text
                    fontSize={{
                         base: 'xs',
                         lg: 'sm',
                    }}>
                    <Text bold>{translate('holds.pickup_at')}:</Text> {location}
               </Text>
          );
     } else {
          return null;
     }
};

export const getPosition = (position, available, length) => {
     if (position && !available && position !== 0 && position !== '0') {
         if(length) {
             return (
                 <Text
                     fontSize={{
                         base: 'xs',
                         lg: 'sm',
                     }}>
                     <Text bold>{translate('holds.position')}:</Text> {translate('holds.positionWithHoldQueueLength', { position: position, queueLength: length })}
                 </Text>
             );
         }
          return (
               <Text
                    fontSize={{
                         base: 'xs',
                         lg: 'sm',
                    }}>
                    <Text bold>{translate('holds.position')}:</Text> {position}
               </Text>
          );
     } else {
          return null;
     }
};

export const getExpirationDate = (expiration, available) => {
     if (expiration && available) {
          const expirationDateUnix = moment.unix(expiration);
          let expirationDate = moment(expirationDateUnix).format('MMM D, YYYY');
          return (
               <Text
                    fontSize={{
                         base: 'xs',
                         lg: 'sm',
                    }}>
                    <Text bold>{translate('holds.pickup_by')}:</Text> {expirationDate}
               </Text>
          );
     } else {
          return null;
     }
};

export const getRenewalCount = (count, available = null) => {
    if(available) {
        return (
            <Text
                fontSize={{
                    base: 'xs',
                    lg: 'sm',
                }}>
                <Text bold>{translate('checkouts.renewed')}:</Text> {count} of {available} times
            </Text>
        );
    } else {
        return null;
    }
}