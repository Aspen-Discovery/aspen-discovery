import React, { Component, useState, useReducer } from "react";
import { Dimensions, Animated } from "react-native";
import { Center, Stack, HStack, Spinner, Toast, Button, Divider, Flex, Box, Text, Icon, Image, IconButton, FlatList, Badge, Avatar, Actionsheet, useDisclose, Pressable } from "native-base";
import AsyncStorage from "@react-native-async-storage/async-storage";
import * as SecureStore from 'expo-secure-store';
import { ListItem } from "react-native-elements";
import { TabView, SceneMap, TabBar, NavigationState, SceneRendererProps } from "react-native-tab-view";
import NavigationService from '../../components/NavigationService';
import { MaterialIcons, Entypo, Ionicons, MaterialCommunityIcons } from "@expo/vector-icons";
import moment from "moment";
import { create, CancelToken } from 'apisauce';

export default class Holds extends Component {
	constructor(props) {
		super(props);
		this.state = {
			isLoading: true,
			hasError: false,
			error: null,
		};
	}

	componentDidMount = async () => {
        this.setState({
            holdInfoLastLoaded: JSON.parse(await SecureStore.getItemAsync("holdInfoLastLoaded")),
            data: global.allHolds,
            unavailableHolds: global.unavailableHolds,
            availableHolds: global.availableHolds,
            isLoading: false,
        })

        // check to see if the data is stale for an automatic refresh
        let hours = moment().diff(moment(this.state.holdInfoLastLoaded), 'hours');
        if(hours >= 1) {
            console.log("Hold data older than 1 hour.")
            try {
                this._fetchHolds();
            } catch (e) {
                console.log("Unable to update.")
            }
        } else {
            console.log("Hold data still fresh.")
        }

	};

	// grabs the items on hold for the account
    _fetchHolds = async () => {

        this.setState({
            isLoading: true,
        });

        await getPatronHolds().then(response => {
            if(response == "TIMEOUT_ERROR") {
                this.setState({
                    hasError: true,
                    error: "Connection to the library timed out.",
                    isLoading: false,
                });
            } else {
                var thisMoment = moment().unix();
                this.setState({
                    data: response,
                    unavailableHolds: Object.values(response.unavailable),
                    availableHolds: Object.values(response.available),
                    hasError: false,
                    error: null,
                    isLoading: false,
                    holdInfoLastLoaded: thisMoment,
                });
            }
        })
    }

    // handles the on press action
    onPressItem = (item, navigation) => {
        NavigationService.navigate('ItemDetails', { item });
    };

	// renders the items on the screen
	renderUnavailableItem = (item) => {
        return (
            <UnavailableHoldItem
              data={item}
              onPressItem={this.onPressItem}
            />
        );
	}

	renderAvailableItem = (item) => {
        return (
            <AvailableHoldItem
              data={item}
            />
        );
	}


	_listEmptyComponent = () => {
        if(this.state.error) {
            return (
                <Center mt={5} mb={5}>
                    <Text bold fontSize="lg">
                        Error loading holds. Please try again later.
                    </Text>
                </Center>
            )
        }
		return (
			<Center mt={5} mb={5}>
				<Text bold fontSize="lg">
					You have no items on hold.
				</Text>
			</Center>
		);
	};

	render() {


		if (this.state.isLoading) {
			return (
				<Center flex={1}>
					<HStack>
						<Spinner accessibilityLabel="Loading..." />
					</HStack>
				</Center>
			);
		}

		return (
			<Box h="100%">
            <Center bg="white" pt={3} pb={3}>
                <Button
                    size="sm"
                    onPress={() => this._fetchHolds()}
                    startIcon={<Icon as={MaterialIcons} name="refresh" size={5} />}
                >
                    Reload Holds
                </Button>
            </Center>
				<FlatList
					data={this.state.availableHolds}
					renderItem={({ item }) => this.renderAvailableItem(item)}
					keyExtractor={(item) => item.id}
				/>
				<FlatList
					data={this.state.unavailableHolds}
					ListEmptyComponent={this._listEmptyComponent()}
					renderItem={({ item }) => this.renderUnavailableItem(item)}
					keyExtractor={(item) => item.id}
				/>
            </Box>
		);
	}
}

async function getPatronHolds() {

    const api = create({ baseURL: 'https://aspen-test.bywatersolutions.com/API', timeout: 10000 });
    const response = await api.get('/UserAPI?method=getPatronHolds', { source: 'all', username: global.userKey, password: global.secretKey });


    if(response.ok) {
        const result = response.data;
        const fetchedData = result.result;
        const allHolds = fetchedData.holds;

        global.allHolds = allHolds;
        global.unavailableHolds = Object.values(allHolds.unavailable);
        global.availableHolds = Object.values(allHolds.available);

        console.log("Patron holds updated.")
        return allHolds;

    } else {
        const fetchedData = response.problem;
        console.log(fetchedData);
        return fetchedData;
    }
}

async function freezeHold(cancelId, recordId, source) {
    const today = moment();
    const reactivationDate = "";
    const api = create({ baseURL: 'https://aspen-test.bywatersolutions.com/API', timeout: 10000 });
    const response = await api.get('/UserAPI?method=freezeHold', { username: global.userKey, password: global.secretKey, sessionId: global.sessionId, holdId: cancelId, recordId: recordId, itemSource: source });

    if(response.ok) {
        const result = response.data;
        const fetchedData = result.result;

        if(fetchedData.success == true) {
            Toast.show({
                title: "Hold frozen",
                description: fetchedData.message,
                status: "success",
                isClosable: true,
                duration: 8000,
                accessibilityAnnouncement: fetchedData.message,
                zIndex: 9999,
                placement: "top"
            });

            // try to reload holds
            await getPatronHolds();

        } else {
            Toast.show({
                title: "Unable to freeze hold",
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
        const fetchedData = response.problem;
        console.log(fetchedData);
        return fetchedData;
    }
}

async function thawHold(cancelId, recordId, source) {
    const api = create({ baseURL: 'https://aspen-test.bywatersolutions.com/API', timeout: 10000 });
    const response = await api.get('/UserAPI?method=activateHold', { username: global.userKey, password: global.secretKey, sessionId: global.sessionId, holdId: cancelId, recordId: recordId, itemSource: source });

    if(response.ok) {
        const result = response.data;
        const fetchedData = result.result;

        if(fetchedData.success == true) {
            Toast.show({
                title: "Hold thawed",
                description: fetchedData.message,
                status: "success",
                isClosable: true,
                duration: 8000,
                accessibilityAnnouncement: fetchedData.message,
                zIndex: 9999,
                placement: "top",
            });

        } else {
            Toast.show({
                title: "Unable to thaw hold",
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
        const fetchedData = response.problem;
        console.log(fetchedData);
        return fetchedData;
    }
}

async function cancelHold(cancelId, recordId, source) {
    const api = create({ baseURL: 'https://aspen-test.bywatersolutions.com/API', timeout: 10000 });
    const response = await api.get('/UserAPI?method=cancelHold', { username: global.userKey, password: global.secretKey, sessionId: global.sessionId, cancelId: cancelId, recordId: recordId, itemSource: source });

    if(response.ok) {
        const result = response.data;
        const fetchedData = result.result;

        if(fetchedData.success == true) {
            Toast.show({
                title: "Hold canceled",
                description: fetchedData.message,
                status: "success",
                isClosable: true,
                duration: 8000,
                accessibilityAnnouncement: fetchedData.message,
                zIndex: 9999,
                placement: "top",
            });

        } else {
            Toast.show({
                title: "Unable to cancel hold",
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
        const fetchedData = response.problem;
        console.log(fetchedData);
        return fetchedData;
    }
}

async function changeHoldPickUpLocation(holdId, newLocation) {
    const api = create({ baseURL: 'https://aspen-test.bywatersolutions.com/API', timeout: 10000 });
    const response = await api.get('/UserAPI?method=changeHoldPickUpLocation', { username: global.userKey, password: global.secretKey, sessionId: global.sessionId, holdId: cancelId, location: newLocation });

    if(response.ok) {
        const result = response.data;
        const fetchedData = result.result;

        if(fetchedData.success == true) {
            Toast.show({
                title: "Pickup location updated",
                description: fetchedData.message,
                status: "success",
                isClosable: true,
                duration: 8000,
                accessibilityAnnouncement: fetchedData.message,
                zIndex: 9999,
                placement: "top",
            });

        } else {
            Toast.show({
                title: "Unable to update pickup location",
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
        const fetchedData = response.problem;
        console.log(fetchedData);
        return fetchedData;
    }
}

function AvailableHoldItem(props) {
    const { data } = props;

    if(data.expirationDate) {
        var expirationDate = moment(data.expirationDate).format("MMM D, YYYY");
    } else {
        var expirationDate = "";
    }

    var title = data.title;
    var title = title.substring(0, title.lastIndexOf('/'));

    var author = data.author;
    var countComma = author.split(',').length-1;
    if (countComma > 1) {
        var author = author.substring(0, author.lastIndexOf(','));
    }

    return (
        <>
        <ListItem bottomDivider >
            <Avatar source={{ uri: data.coverUrl }} size="56px" alt={data.title}/>
            <ListItem.Content>
                <Text fontSize="sm" bold mb={1}>
                    {title}
                </Text>
                {data.status == "Ready to Pickup" && <Badge colorScheme="green" rounded="4px" mb={.5}>{data.status}</Badge>}
                <Text bold fontSize="xs">
                    Author: <Text fontSize="xs">{author}</Text>
                </Text>
                <Text bold fontSize="xs">
                    Format: <Text fontSize="xs">{data.format}</Text>
                </Text>
                <Text bold fontSize="xs">
                    Pickup Location: <Text fontSize="xs">{data.currentPickupName}</Text>
                </Text>
                <Text bold fontSize="xs">
                    Pickup By: <Text fontSize="xs">{expirationDate}</Text>
                </Text>
            </ListItem.Content>

        </ListItem>
        </>
        )
}

function UnavailableHoldItem(props) {
    const { onPressItem, data } = props;
    const { isOpen, onOpen, onClose } = useDisclose();


    // format some dates
    if(data.availableDate != null) {
        const availableDate = moment(data.availableDate).format("MMM D, YYYY");
    }

    if(data.reactivationDate != null) {
        const reactivationDate = moment(data.reactivationDate).format("MMM D, YYYY");
    }

    // check freeze status to see which option to display
    if(data.canFreeze == true) {
        if(data.frozen == true) {
            var label = "Thaw Hold";
            var method = "thawHold";
            var icon = "play";
        } else {
            var label = "Freeze Hold for 30 Days";
            var method = "freezeHold";
            var icon = "pause";
        }
    }

    if(data.status == "Pending") {
        var statusColor = "green";
    }

    var title = data.title;
    var title = title.substring(0, title.lastIndexOf('/'));

    var author = data.author;
    var countComma = author.split(',').length-1;
    if (countComma > 1) {
        var author = author.substring(0, author.lastIndexOf(','));
    }

    return (
    <>
    <Pressable onPress={onOpen}>
    <ListItem bottomDivider >
        <Avatar source={{ uri: data.coverUrl }} size="56px" alt={data.title}/>
        <ListItem.Content>
            <Text fontSize="sm" bold mb={1}>
                {title} {data.status == "Frozen" && <Badge colorScheme="yellow" rounded="4px" mt={-.5}>{data.status}</Badge>}
            </Text>
            <Text bold fontSize="xs">
                Author: <Text fontSize="xs">{author}</Text>
            </Text>
            <Text bold fontSize="xs">
                Format: <Text fontSize="xs">{data.format}</Text>
            </Text>
            <Text bold fontSize="xs">
                Pickup Location: <Text fontSize="xs">{data.currentPickupName}</Text>
            </Text>
            <Text bold fontSize="xs">
                Position: <Text fontSize="xs">{data.position}</Text>
            </Text>
        </ListItem.Content>

    </ListItem>
    </Pressable>
      <Actionsheet isOpen={isOpen} onClose={onClose} size="full">
        <Actionsheet.Content>
          <Box w="100%" h={60} px={4} justifyContent="center">
            <Text
              fontSize={16}
              color="gray.500"
              _dark={{
                color: "gray.300",
              }}
            >
              {title}
            </Text>
          </Box>
        {data.cancelable &&
        <Actionsheet.Item startIcon={ <Icon as={MaterialIcons} name="cancel"  color="trueGray.400" mr="1" size="6" /> }
            onPress={ () => {
                cancelHold(data.cancelId, data.recordId, data.source);
                onClose(onClose);
            }} >
            Cancel Hold
        </Actionsheet.Item>
        }
        {data.allowFreezeHolds &&
        <Actionsheet.Item startIcon={ <Icon as={MaterialCommunityIcons} name={icon}  color="trueGray.400" mr="1" size="6" /> }
            onPress={ () => {
                if (data.frozen == true) {
                    thawHold(data.cancelId, data.recordId, data.source)
                } else {
                    freezeHold(data.cancelId, data.recordId, data.source)
                };
                onClose(onClose);
            }}
            >
            {label}
        </Actionsheet.Item>
        }

        {data.locationUpdateable &&
        <Actionsheet.Item startIcon={ <Icon as={Ionicons} name="location"  color="trueGray.400" mr="1" size="6" /> }
            onPress={ () => {
                console.log("Change pickup location");
                onClose(onClose);
            }} >
            Change Pickup Location
        </Actionsheet.Item>
        }

        {data.groupedWorkId != null &&
        <Actionsheet.Item startIcon={ <Icon as={MaterialIcons} name="search"  color="trueGray.400" mr="1" size="6" /> }
            onPress={ () => {
                console.log("Open item");
                onClose(onClose);
            }} >
        Item Details
        </Actionsheet.Item>
        }
        </Actionsheet.Content>
      </Actionsheet>
    </>
    )
}