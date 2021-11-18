import React, { Component, useState, useReducer, useCallback, useRef } from "react";
import { Dimensions, Animated, TouchableOpacity } from "react-native";
import { Center, Modal, Stack, HStack, VStack, Spinner, Toast, FormControl, Select, Radio, CheckIcon, Button, Divider, Flex, Box, Text, Icon, Image, IconButton, FlatList, Badge, Avatar, Actionsheet, useDisclose, Pressable, AlertDialog } from "native-base";
import AsyncStorage from "@react-native-async-storage/async-storage";
import * as SecureStore from 'expo-secure-store';
import { ListItem } from "react-native-elements";
import NavigationService from '../../components/NavigationService';
import { MaterialIcons, Entypo, Ionicons, MaterialCommunityIcons } from "@expo/vector-icons";
import { create, CancelToken } from 'apisauce';
import _ from "lodash";
import * as WebBrowser from 'expo-web-browser';

import { checkoutItem, placeHold, overDriveSample } from "../../util/recordActions";

const StatusIndicator = (props) => {

    const { data, format, language, patronId, locations, showAlert } = props;
    var dataArray = Object.values(data);
    var arrayToSearch = data.[`${format}`];

    const arrayToFilter = dataArray.map(function (variation, index, array) {
        let records = variation;
        return records;
    });

    var match = arrayToSearch.filter(function(item){
         return (item.format == format);
    })

    var match = match.filter(function(item){
         return (item.language == language);
    })

    if(match.length == 0) {

        return(
            <Center>
                <VStack mt={5} mb={0} bgColor="white" p={3} rounded="8px" shadow={1} alignItems="center" width={{ base: "100%", lg: "75%" }}>
                    <Text bold textAlign="center">No matches found for this title in {language} for {format} format.</Text>
                </VStack>
            </Center>
        );
    }

    if(match.length == 1) {
        const status = match[0].status;
        const available = match[0].available;
        const availableOnline = match[0].availableOnline;
        const action = match[0].action.title;
        const actionType = match[0].action.type;
        const actions = match[0].action;
        const id = match[0].id;
        const source = match[0].source;
        const copiesMessage = match[0].copiesMessage;

        const location = match[0].shelfLocation;
        const callNumber = match[0].callNumber;
        const eContent = match[0].isEContent;

        const edition = match[0].edition;
        const publisher = match[0].publisher;
        const publicationDate = match[0].publicationDate;

        if(available == true || availableOnline == true) {
            var badgeColor = "success";
        } else {
            var badgeColor = "danger";
        }

        return (
            <Center>
                <HStack justifyContent="space-between" mt={5} mb={0} bgColor="white" p={3} rounded="8px" shadow={1} alignItems="center" width={{ base: "100%", lg: "75%" }} space={3}>
                    <VStack space={1} alignItems="center" maxW="50%">
                        <Badge colorScheme={badgeColor} rounded="4px" _text={{ fontSize: 14, }} mb={.5}>{status}</Badge>
                        {source == "ils" ? <><Text fontSize={{ base: "sm", lg: "lg" }} color="muted.500" mt={-.5}><Text fontSize={{ base: "sm", lg: "lg" }} bold>{location} -</Text> {callNumber}</Text></> : null}
                        {copiesMessage != "" ? <><Text fontSize={{ base: "xxs", lg: "xs" }} color="muted.400">{copiesMessage}</Text></> : null}
                    </VStack>
                    <Button.Group direction="column" alignItems="center">
                    {actions.map((action) => {
                        if(action.type == "overdrive_sample") {
                            return (
                                 <Button size={{ base: "sm", lg: "md" }} colorScheme="primary" variant="outline" _text={{ padding: 0, textAlign: "center", fontSize: 14 }} style={{flex: 1, flexWrap: 'wrap'}} onPress={ () => doAction(id, action.type, global.patronId, action.formatId, action.sampleNumber)}>{action.title}</Button>
                            )
                        }
                        if(action.type == "ils_hold") {
                        /* Open pickup location modal */
                            return (
                                 <SelectPickupLocation locations={locations} label={action.title} action={action.type} record={id} patron={global.patronId} showAlert={showAlert} />
                            )
                        }
                            return (
                                 <Button size={{ base: "md", lg: "lg" }} colorScheme="primary" variant="solid" _text={{ padding: 0, textAlign: "center", fontSize: 14 }} style={{flex: 1, flexWrap: 'wrap'}} onPress={ async () => { await doAction(id, action.type, global.patronId).then(response => { showAlert(response) }) }}>{action.title}</Button>
                            );
                        })}
                    </Button.Group>
                </HStack>
            </Center>
        )
    }

    if(match.length > 1) {
	    return match.map((item, index) => {
            if(item.available == true || item.availableOnline == true) {
                var badgeColor = "success";
            } else {
                var badgeColor = "danger";
            }
            var actions = item.action;

	        return (
                <Center>
                    <HStack justifyContent="space-between" mt={5} mb={0} bgColor="white" p={3} rounded="8px" shadow={1} alignItems="center" width={{ base: "100%", lg: "75%" }} space={3}>
                        <VStack space={1} alignItems="center" maxW="50%">
                            <Badge colorScheme={badgeColor} rounded="4px" _text={{ fontSize: 14, }} >{item.status}</Badge>
                            {item.source == "ils" ? <><Text fontSize={{ base: "sm", lg: "lg" }} color="muted.500" mt={-.5}><Text fontSize={{ base: "sm", lg: "lg" }} bold>{item.shelfLocation} -</Text> {item.callNumber}</Text></> : null}
                            <Text fontSize={{ base: "xs", lg: "xs" }} color="muted.400">{item.publicationDate}</Text>
                            {item.copiesMessage != "" ? <><Text fontSize={{ base: "xxs", lg: "xs" }} color="muted.400">{item.copiesMessage}</Text></> : null}
                        </VStack>
                        <Button.Group direction="column">
                        {actions.map((action) => {
                        if(action.type == "overdrive_sample") {
                            return (
                                 <Button size={{ base: "sm", lg: "md" }} colorScheme="primary" variant="outline" _text={{ padding: 0, textAlign: "center", fontSize: 14 }} style={{flex: 1, flexWrap: 'wrap'}} onPress={ () => doAction(item.id, action.type, global.patronId, action.formatId, action.sampleNumber)}>{action.title}</Button>
                            )
                        }
                        if(action.type == "ils_hold") {
                        /* Open pickup location modal */
                            return (
                                 <SelectPickupLocation locations={locations} label={action.title} action={action.type} record={item.id} patron={global.patronId} showAlert={showAlert} />
                            )
                        }

                        return (
                                 <Button size={{ base: "md", lg: "lg" }} colorScheme="primary" variant="solid" _text={{ padding: 0, textAlign: "center", fontSize: 14 }} style={{flex: 1, flexWrap: 'wrap'}} onPress={ async () => { await doAction(item.id, action.type, global.patronId).then(response => { showAlert(response) }) }}>{action.title}</Button>
                        );

                        })}
                        </Button.Group>
                    </HStack>
                </Center>
	        )
        })
    }
}

async function doAction(id, actionType, patronId, formatId = null, sampleNumber = null, pickupBranch = null) {
    const recordId = id.split(":");
    const source = recordId[0];
    const itemId = recordId[1];

    if(actionType.includes("checkout")) {
        const response = await checkoutItem(itemId, source, patronId);
        return response;
    } else if(actionType.includes("hold")) {

        if(!global.overdriveEmail && global.promptForOverdriveEmail == 1 && source == "overdrive") {
           const getPromptForOverdriveEmail = [];
           getPromptForOverdriveEmail['getPrompt'] = true;
           getPromptForOverdriveEmail['itemId'] = itemId;
           getPromptForOverdriveEmail['source'] = source;
           getPromptForOverdriveEmail['patronId'] = patronId;
           getPromptForOverdriveEmail['overdriveEmail'] = global.overdriveEmail;
           getPromptForOverdriveEmail['promptForOverdriveEmail'] = global.promptForOverdriveEmail;
           return getPromptForOverdriveEmail;
        } else {
            const response = await placeHold(itemId, source, patronId, pickupBranch);
            return response;
        }

    } else if(actionType.includes("sample")) {
        const response = await overDriveSample(formatId, itemId, sampleNumber);
        return response;
    }
}

const SelectPickupLocation = (props) => {

    const { locations, label, action, record, patron, showAlert } = props;

	const [showModal, setShowModal] = useState(false);
	let [value, setValue] = React.useState("");

	return (
	<>
        <Button onPress={() => setShowModal(true)} colorScheme="primary" size="md">{label}</Button>
        <Modal isOpen={showModal} onClose={() => setShowModal(false)} closeOnOverlayClick={false}>
            <Modal.Content>
                <Modal.CloseButton />
                <Modal.Header>{label}</Modal.Header>
                <Modal.Body>
                    <FormControl>
                        <FormControl.Label>I want to pick this up at</FormControl.Label>
                        <Radio.Group
                            name="pickupLocations"
                            accessibilityLabel="Select a pickup location"
                            value={value}
                            onChange={(nextValue) => {
                                setValue(nextValue);
                            }}
                            mt="1"
                        >
                            {locations.map((item, index) => {
                                return <Radio value={item.key} my={1}>{item.name}</Radio>;
                            })}
                        </Radio.Group>
                    </FormControl>
                </Modal.Body>
                <Modal.Footer>
                    <Button.Group space={2} size="md">
                        <Button colorScheme="muted" variant="outline" onPress={() => setShowModal(false)}>Cancel</Button>
                        <Button
                            onPress={ async () => { await doAction(record, action, patron, value).then(response => { showAlert(response) }); setShowModal(false); }}
                        >
                            {label}
                        </Button>
                    </Button.Group>
                </Modal.Footer>
            </Modal.Content>
        </Modal>
    </>
	)
}

export default StatusIndicator;