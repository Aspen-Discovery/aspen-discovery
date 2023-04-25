import React from 'react';
import DateTimePickerModal from "react-native-modal-datetime-picker";
import { MaterialIcons } from '@expo/vector-icons';
import { Actionsheet, Icon, useToken, useColorModeValue } from 'native-base';
import { freezeHold, freezeHolds } from '../../../util/accountActions';
import {getTermFromDictionary} from '../../../translations/TranslationService';

export const SelectThawDate = (props) => {
    const { label, language, libraryContext, onClose, freezeId, recordId, source, userId, resetGroup, isOpen } = props;
    let data = props.data;
    const [loading, setLoading] = React.useState(false);

    const textColor = useToken('colors', useColorModeValue('text.500', 'text.50'));
    const colorMode = useColorModeValue(false, true);

    let actionLabel = getTermFromDictionary(language, 'freeze_hold');
    if(label) {
       actionLabel = label;
    }

    const today = new Date();
    const [date, setDate] = React.useState(today);

    const [isDatePickerVisible, setDatePickerVisibility] = React.useState(false);

    const showDatePicker = () => {
        setDatePickerVisibility(true);
    };

    const hideDatePicker = () => {
        setDatePickerVisibility(false);
    };

    const onSelectDate = (date) => {
        hideDatePicker();
        setLoading(true);
        console.warn("A date has been picked: ", date);
        setDate(date);
        if(data) {
            freezeHolds(data, libraryContext.baseUrl, date).then((result) => {
                resetGroup();
                setLoading(false);
                onClose();
                hideDatePicker();
            });
        } else {
            freezeHold(freezeId, recordId, source, libraryContext.baseUrl, userId, date).then((result) => {
                resetGroup();
                setLoading(false);
                onClose();
                hideDatePicker();
            });
        }
    }

    return (
        <>
            <Actionsheet.Item
                startIcon={data ? null : <Icon as={MaterialIcons} name="pause" color="trueGray.400" mr="1" size="6" />}
                onPress={showDatePicker}>
                {actionLabel}
            </Actionsheet.Item>
            <DateTimePickerModal
                isVisible={isDatePickerVisible}
                date={date}
                mode="date"
                onConfirm={onSelectDate}
                onCancel={hideDatePicker}
                isDarkModeEnabled={colorMode}
                minimumDate={today}
                textColor={textColor}
                confirmTextIOS={loading ? getTermFromDictionary('en', 'freezing_hold') : getTermFromDictionary('en', 'freeze_hold')}
            />
      </>
    );
};