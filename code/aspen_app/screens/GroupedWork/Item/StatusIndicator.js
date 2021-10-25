import React, { Component, useState, useReducer } from "react";
import { Dimensions, Animated } from "react-native";
import { Center, Stack, HStack, VStack, Spinner, Toast, Button, Divider, Flex, Box, Text, Icon, Image, IconButton, FlatList, Badge, Avatar, Actionsheet, useDisclose, Pressable } from "native-base";
import AsyncStorage from "@react-native-async-storage/async-storage";
import * as SecureStore from 'expo-secure-store';
import { ListItem } from "react-native-elements";
import NavigationService from '../../../components/NavigationService';
import { MaterialIcons, Entypo, Ionicons, MaterialCommunityIcons } from "@expo/vector-icons";
import { create, CancelToken } from 'apisauce';
import _ from "lodash";

import CheckOut from "./CheckOut";
import PlaceHold from "./PlaceHold";

const StatusIndicator = (props) => {

    const { data, format, language } = props;

    var dataArray = Object.values(data);

    const arrayToFilter = dataArray.map(function (variation, index, array) {
        let records = Object.values(variation.records);
        return records[0];
    });

    const match = arrayToFilter.filter(function(item){
         return (item.format == format && item.language == language);
    })

    console.log(match);

    const status = match[0].status;
    const available = match[0].available;
    const action = match[0].action[0].title;
    const actionType = match[0].action[0].type;
    const id = match[0].id;
    const source = match[0].source;
    const copiesMessage = match[0].copiesMessage;

    const location = match[0].shelfLocation;
    const callNumber = match[0].callNumber;
    const eContent = match[0].isEContent;

    if(available == true) {
        var badgeColor = "success";
    } else {
        var badgeColor = "danger";
    }


    return (
        <Center>
            <VStack mt={5} mb={0} bgColor="white" p={3} rounded="8px" shadow={1} alignItems="center" width={{ base: "100%", lg: "75%" }}>
                <HStack space={1.5} mb={1}>
                    <Badge colorScheme={badgeColor} rounded="4px" _text={{ fontSize: 12, }} >{status}</Badge>
                    {source == "ils" && <><Text fontSize={{ base: "sm", lg: "lg" }} color="muted.500" mt={-.5}><Text bold>{location} -</Text> {callNumber}</Text></>}
                </HStack>
                {copiesMessage != "" && <><Text fontSize={{ base: "xxs", lg: "xs" }} color="muted.400">{copiesMessage}</Text></>}
                <Button mt={3} size={{ base: "md", lg: "lg" }} colorScheme="primary" variant="solid" _text={{ padding: 0 }} style={{flex: 1, flexWrap: 'wrap'}} onPress={ () => doAction(id, actionType)}>{action}</Button>
            </VStack>
        </Center>
    )


}

async function doAction(id, actionType) {

    const recordId = id.split(":");
    const source = recordId[0];
    const itemId = recordId[1];


    if(actionType.includes("checkout")) {
        await checkoutItem(itemId, source).then(response => {
            if(response == "TIMEOUT_ERROR") {
                this.setState({
                    hasError: true,
                    error: "Connection to the library timed out.",
                    isLoading: false,
                });
            } else {
                this.setState({
                    hasError: false,
                    error: null,
                    isLoading: false,
                });

            }
        })
    } else if(actionType.includes("hold")) {
        await placeHold(itemId, source).then(response => {
            if(response == "TIMEOUT_ERROR") {
                this.setState({
                    hasError: true,
                    error: "Connection to the library timed out.",
                    isLoading: false,
                });
            } else {
                this.setState({
                    hasError: false,
                    error: null,
                    isLoading: false,
                });
            }
        })
    }
}

async function placeHold(itemId, source) {
    const api = create({ baseURL: 'http://demo.localhost:8888/API', timeout: 10000 });
    const response = await api.get('/UserAPI?method=placeHold', { username: global.userKey, password: global.secretKey, itemId: itemId, source: source });


    if(response.ok) {
        const result = response.data;
        const fetchedData = result.result;
        console.log(fetchedData);

        if (fetchedData.success == true) {
            Toast.show({
                title: "Hold placed successfully",
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
                title: "Unable to place hold",
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
        console.log("Unable to connect.");
        fetchedData = response.problem;
        return fetchedData
    }
}

async function checkoutItem(itemId, source) {
    const patronId = await SecureStore.getItemAsync("patronId");
    const api = create({ baseURL: 'http://demo.localhost:8888/API', timeout: 10000 });
    const response = await api.get('/UserAPI?method=checkoutItem', { username: global.userKey, password: global.secretKey, itemId: itemId, source: source, patronId: patronId });

    if(response.ok) {
        const result = response.data;
                console.log(result);
        const fetchedData = result.result;

        if (fetchedData.success == true) {
            Toast.show({
                title: "Title checked out",
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
                title: "Unable to checkout title",
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
        console.log("Unable to connect.");
        fetchedData = response.problem;
        return fetchedData
    }
}

const ItemStatus = (props) => {
    const { available, status } = props;


    if (available == true) {
        var color = "success";
        var message = "Available";
    } else if (available == false) {
        var color = "danger"
        var message = "Checked out";
    }
    else {
        var color = "info"
        var message = "Status unknown";
    }

    return (
        <Badge colorScheme={color} variant="solid">{message}</Badge>
    );
}

const ItemLocation = (props) => {
    const { shelfLocation, callNumber } = props;

    return (
    <Stack>
        <Text bold>{shelfLocation}</Text>
        <Text fontSize="sm">{callNumber}</Text>
    </Stack>
    );
}

export const ItemAction = (props) => {

    const { showPlaceHold, showCheckout, available } = props;

    if(showPlaceHold == true) {
        var action = "Place Hold";
    } else {
        var action = "Checkout";
    }

    if(showPlaceHold == 1 && available == false) {
        var action = "Place Hold";
    } else if (showPlaceHold == 0 && available == true) {
        var action = "In-library Only";
    } else {
        var action = "Checkout";
    }

    return (
    <Stack>
        <Button size="sm" mb={1}>{action}</Button>
    </Stack>
    )
}

export default StatusIndicator;