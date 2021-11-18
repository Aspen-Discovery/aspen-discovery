import React, { Component, useState, useCallback, useEffect, useRef } from 'react'
import { Box, Text, FlatList, Spinner, ScrollView, View, TouchableWithoutFeedback, HStack, VStack, Icon, Divider, Center } from 'native-base';
import { Ionicons, MaterialIcons } from "@expo/vector-icons";
import { Platform } from "react-native";
import moment from "moment";

const HoursAndLocation = (props) => {

    const { hoursMessage, hours } = props

    return (
    <>
        <Box mb={4}>
        <Center>
            <HStack space={3} alignItems="center">
                <Icon as={MaterialIcons} name="schedule" size="sm" mt={0.3} mr={-1} />
                <Text fontSize="lg" bold>Today's Hours </Text>
            </HStack>
            <Text alignText="center" mt={2} italic>{hoursMessage}</Text>
            </Center>
        </Box>
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
        <Center>
            <VStack space={1} alignItems="flex-start">
                <Text bold fontSize="sm">{item.dayName}</Text>
                <Text fontSize="sm">{hours}</Text>
            </VStack>
            </Center>
        {item.notes ?
            <Text bold>Note: <Text>{item.notes}</Text></Text>
        : null }
        </Box>
    );
};

export default HoursAndLocation;