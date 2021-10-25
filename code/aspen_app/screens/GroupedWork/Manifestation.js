import React, { Component, useState, useReducer } from "react";
import { Dimensions, Animated } from "react-native";
import { Center, Stack, HStack, VStack, Spinner, Toast, Button, Divider, Flex, Box, Text, Icon, Image, IconButton, FlatList, Badge, Avatar, Actionsheet, useDisclose, Pressable } from "native-base";
import AsyncStorage from "@react-native-async-storage/async-storage";
import * as SecureStore from 'expo-secure-store';
import { ListItem } from "react-native-elements";
import NavigationService from '../../components/NavigationService';
import { MaterialIcons, Entypo, Ionicons, MaterialCommunityIcons } from "@expo/vector-icons";
import { create, CancelToken } from 'apisauce';

const Manifestation = (props) => {
    const { format, records, status, action } = props;
    const [expanded, setExpanded] = useState("");

    const allRecords = Object.entries(records);

    function renderRecord(items) {
        const entries = items["allRecords"][0];
        entries.map((record) => {
            return (
            null
            )
        })
    }

    return (
        <Button>{format}</Button>
    );
}

const Record = (props) => {

    const { data, status, action, shelfLocation, callNumber, totalCopies, availableCopies, format, key, holdable } = props;

    console.log(status);

    if(format == "Book") {
           return (
               <ListItem key={key} bottomDivider style={{ width: (Dimensions.get('window').width), backgroundColor: "transparent" }}>
               <ListItem.Content>
               <VStack alignItems="flex-start">
                   <ItemStatus available={available} status={status} />
                   <CallNumber shelfLocation={shelfLocation} callNumber={callNumber} />
               </VStack>
               </ListItem.Content>

               </ListItem>
           )
    } else {
           return (
               <ListItem key={key} bottomDivider style={{ width: (Dimensions.get('window').width) }}>

               <ListItem.Content>
               <VStack alignItems="flex-start">
                  <ItemStatus available={available} />
                  <Text bold>eContent Source</Text>
               </VStack>
               </ListItem.Content>

               </ListItem>
           )
    }


}

export default Manifestation;