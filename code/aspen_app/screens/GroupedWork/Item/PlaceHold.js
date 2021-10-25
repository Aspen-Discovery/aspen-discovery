import React, { Component, useState, useReducer } from "react";
import { Dimensions, Animated } from "react-native";
import { Center, Stack, HStack, VStack, Spinner, Toast, Button, Divider, Flex, Box, Text, Icon, Image, IconButton, FlatList, Badge, Avatar, Actionsheet, useDisclose, Pressable } from "native-base";
import AsyncStorage from "@react-native-async-storage/async-storage";
import * as SecureStore from 'expo-secure-store';
import { ListItem } from "react-native-elements";
import NavigationService from '../../../components/NavigationService';
import { MaterialIcons, Entypo, Ionicons, MaterialCommunityIcons } from "@expo/vector-icons";
import { create, CancelToken } from 'apisauce';

const PlaceHold = (props) => {
    return (
        <Box>
            <Text>Place hold screen</Text>
        </Box>
    )
}

async function placeHold(id, source, location) {
    const api = create({ baseURL: 'https://aspen-test.bywatersolutions.com/API', timeout: 10000 });
    const response = await api.get('/UserAPI?method=placeHold', { username: global.userKey, password: global.secretKey, sessionId: global.sessionId, itemSource: source, itemId: id, pickupBranch: location, patronId: patronId });

    if(response.ok) {
        const result = response.data;
        const fetchedData = result.hold;

        if(fetchedData.ok == true) {
            Toast.show({
                title: "Success",
                description: fetchedData.message,
                isClosable: true,
                duration: 8000,
                status: "success",
                accessibilityAnnouncement: fetchedData.message,
            });
        } else {
            Toast.show({
                title: "Error",
                description: fetchedData.message,
                isClosable: true,
                duration: 8000,
                status: "warning",
                accessibilityAnnouncement: fetchedData.message,
            });
        }

    } else {
        const fetchedData = response.problem;
        console.log(fetchedData);
        return fetchedData;
    }
}

export default PlaceHold;