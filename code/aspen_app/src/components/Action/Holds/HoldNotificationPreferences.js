import React from 'react';
import { FormControl, FormControlLabel, FormControlLabelText, FormControlHelper, Select, SelectTrigger, SelectInput, SelectIcon, SelectPortal, SelectBackdrop, SelectContent, SelectDragIndicatorWrapper, SelectDragIndicator, SelectItem, Icon, ChevronDownIcon, Input, InputField, Checkbox, CheckboxLabel, Text, CheckIcon, CheckboxIndicator, CheckboxIcon, FormControlHelperText, SelectScrollView } from '@gluestack-ui/themed';
import _ from 'lodash';
import { getTermFromDictionary, getTranslationsWithValues } from '../../../translations/TranslationService';

export const HoldNotificationPreferences = (props) => {
     const { textColor, theme, user, url, language, emailNotification, setEmailNotification, phoneNotification, setPhoneNotification, smsNotification, setSMSNotification, smsCarrier, setSMSCarrier, smsNumber, setSMSNumber, phoneNumber, setPhoneNumber } = props;

     const holdNotificationInfo = user.holdNotificationInfo;
     const smsCarriers = holdNotificationInfo.smsCarriers;

     const [emailNotificationLabel, setEmailNotificationLabel] = React.useState('Yes, by email');
     React.useEffect(() => {
          async function fetchTranslations() {
               await getTranslationsWithValues('hold_email_notification', user.email ?? null, language, url).then((result) => {
                    const tmp = _.toString(result);
                    if (!tmp.includes('%')) {
                         setEmailNotificationLabel(tmp);
                    }
               });
          }

          fetchTranslations();
     }, [language]);

     return (
          <>
               <Text color={textColor} mb="$2" size="sm">
                    {getTermFromDictionary(language, 'hold_notify_for_pickup')}
               </Text>
               {user.email ? (
                    <FormControl mb="$2">
                         <Checkbox
                              size="sm"
                              value={emailNotification}
                              name="emailNotification"
                              defaultIsChecked={emailNotification}
                              onChange={(value) => {
                                   setEmailNotification(value);
                              }}>
                              <CheckboxIndicator mr="$2">
                                   <CheckboxIcon as={CheckIcon} />
                              </CheckboxIndicator>
                              <CheckboxLabel color={textColor}>{emailNotificationLabel}</CheckboxLabel>
                         </Checkbox>
                    </FormControl>
               ) : null}
               <FormControl mb="$2">
                    <Checkbox
                         size="sm"
                         name="phoneNotification"
                         defaultIsChecked={phoneNotification}
                         onChange={(value) => {
                              setPhoneNotification(value);
                         }}>
                         <CheckboxIndicator mr="$2">
                              <CheckboxIcon as={CheckIcon} />
                         </CheckboxIndicator>
                         <CheckboxLabel color={textColor}>{getTermFromDictionary(language, 'hold_phone_notification')}</CheckboxLabel>
                    </Checkbox>
               </FormControl>
               {phoneNotification ? (
                    <>
                         <FormControl mb="$2">
                              <FormControlLabel>
                                   <FormControlLabelText color={textColor} size="sm">
                                        {getTermFromDictionary(language, 'hold_phone_number')}
                                   </FormControlLabelText>
                              </FormControlLabel>
                              <Input>
                                   <InputField color={textColor} name="phoneNumber" defaultValue={phoneNumber} accessibilityLabel={getTermFromDictionary(language, 'hold_phone_number')} onChangeText={(value) => setPhoneNumber(value)} />
                              </Input>
                         </FormControl>
                    </>
               ) : null}
               {!_.isEmpty(smsCarriers) ? (
                    <>
                         <FormControl mb="$1">
                              <Checkbox
                                   size="sm"
                                   name="smsNotification"
                                   defaultIsChecked={smsNotification}
                                   onChange={(value) => {
                                        setSMSNotification(value);
                                   }}>
                                   <CheckboxIndicator mr="$2">
                                        <CheckboxIcon as={CheckIcon} />
                                   </CheckboxIndicator>
                                   <CheckboxLabel color={textColor}>{getTermFromDictionary(language, 'hold_sms_notification')}</CheckboxLabel>
                              </Checkbox>
                         </FormControl>
                         {smsNotification ? (
                              <>
                                   <FormControl mb="$1">
                                        <FormControlLabel>
                                             <FormControlLabelText size="sm" color={textColor}>
                                                  {getTermFromDictionary(language, 'hold_sms_carrier')}
                                             </FormControlLabelText>
                                        </FormControlLabel>

                                        <Select name="smsCarrier" selectedValue={smsCarrier} accessibilityLabel={getTermFromDictionary(language, 'hold_sms_select_carrier')} onValueChange={(itemValue) => setSMSCarrier(itemValue)}>
                                             <SelectTrigger variant="outline" size="md">
                                                  {smsCarrier && smsCarrier !== -1 ? (
                                                       _.map(smsCarriers, function (carrier, selectedIndex, array) {
                                                            if (selectedIndex === smsCarrier) {
                                                                 return <SelectInput placeholder="Select a Carrier" value={carrier} color={textColor} />;
                                                            }
                                                       })
                                                  ) : (
                                                       <SelectInput placeholder="Select a Carrier" />
                                                  )}
                                                  <SelectIcon mr="$3" as={ChevronDownIcon} color={textColor} />
                                             </SelectTrigger>
                                             <SelectPortal>
                                                  <SelectBackdrop />
                                                  <SelectContent p="$5">
                                                       <SelectDragIndicatorWrapper>
                                                            <SelectDragIndicator />
                                                       </SelectDragIndicatorWrapper>
                                                       <SelectScrollView>
                                                            {_.map(smsCarriers, function (carrier, index, array) {
                                                                 if (index === smsCarrier) {
                                                                      return <SelectItem key={index} label={carrier} value={index} bgColor={theme['colors']['tertiary']['300']} />;
                                                                 }
                                                                 return <SelectItem key={index} label={carrier} value={index} />;
                                                            })}
                                                       </SelectScrollView>
                                                  </SelectContent>
                                             </SelectPortal>
                                        </Select>
                                        <FormControlHelper mb="$2">
                                             <FormControlHelperText size="xs" color={textColor}>
                                                  {getTermFromDictionary(language, 'hold_sms_charges')}
                                             </FormControlHelperText>
                                        </FormControlHelper>
                                   </FormControl>
                                   <FormControl>
                                        <FormControlLabel>
                                             <FormControlLabelText size="sm" color={textColor}>
                                                  {getTermFromDictionary(language, 'hold_sms_number')}
                                             </FormControlLabelText>
                                        </FormControlLabel>
                                        <Input>
                                             <InputField color={textColor} name="smsNumber" defaultValue={smsNumber} accessibilityLabel={getTermFromDictionary(language, 'hold_sms_number')} onChangeText={(value) => setSMSNumber(value)} />
                                        </Input>
                                        <FormControlHelper mb="$2">
                                             <FormControlHelperText size="xs" color={textColor}>
                                                  {getTermFromDictionary(language, 'hold_sms_format')}
                                             </FormControlHelperText>
                                        </FormControlHelper>
                                   </FormControl>
                              </>
                         ) : null}
                    </>
               ) : null}
          </>
     );
};