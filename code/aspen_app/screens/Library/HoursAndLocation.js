import React, { Component, useState, useCallback, useEffect, useRef } from 'react'
import { Box, Text, FlatList, Spinner, ScrollView, View, TouchableWithoutFeedback } from 'native-base';

const HoursAndLocation = (props) => {

    const { hoursMessage, hours } = props

    return (
    <>
        <Box mb={4}>
            <HStack space={3}>
                <Icon as={MaterialIcons} name="schedule" size="sm" mt={0.3} mr={-1} />
                <Text fontSize="lg" bold>Today's Hours </Text>
            </HStack>
            <Text>{hoursMessage}</Text>
        </Box>
            <FlatList
                data={hours}
                renderItem={({ item }) => renderHours(item)}
                keyExtractor={(item) => item.day}
                mb={3}
            />
        <Divider mb={10} />
        </>
    )
}

function renderHours(item) {

    const openTime = moment(item.open, "HH:mm").format("h:mm A");
    const closingTime = moment(item.close, "HH:mm").format("h:mm A");

    if(item.isClosed) {
        var hours = "Closed";
    } else {
        var hours = openTime + " - " + closingTime;
    }

    return (
        <Box>
            <HStack space={3} alignItems="flex-start">
                <Text bold fontSize="sm">{item.dayName}</Text>
                <Text fontSize="sm">{hours}</Text>
            </HStack>
        {item.notes &&
            <Text bold>Note: <Text>{item.notes}</Text></Text>
        }
        </Box>
    );
};

export default HoursAndLocation;