import React, { Component, useState, useReducer } from "react";
import { Dimensions, Animated, RefreshControl } from "react-native";
import { Center, Stack, Modal, FormControl, Radio, HStack, Spinner, Toast, Button, Divider, Flex, Box, Text, Icon, Image, IconButton, FlatList, Badge, Avatar, Actionsheet, useDisclose, Pressable } from "native-base";
import AsyncStorage from "@react-native-async-storage/async-storage";
import * as SecureStore from 'expo-secure-store';
import { ListItem } from "react-native-elements";
import { TabView, SceneMap, TabBar, NavigationState, SceneRendererProps } from "react-native-tab-view";
import NavigationService from '../../components/NavigationService';
import { MaterialIcons, Entypo, Ionicons, MaterialCommunityIcons } from "@expo/vector-icons";
import moment from "moment";
import { create, CancelToken } from 'apisauce';

// custom components and helper files
import { translate } from '../../util/translations';
import { loadingSpinner } from "../../components/loadingSpinner";
import { loadError } from "../../components/loadError";
import { getHolds } from '../../util/loadPatron';
import { freezeHold, thawHold, cancelHold, changeHoldPickUpLocation } from '../../util/accountActions';
import { getPickupLocations } from '../../util/loadLibrary';

export default class Holds extends Component {
	static navigationOptions = { title: translate('holds.title') };
	constructor(props) {
		super(props);
		this.state = {
			isLoading: true,
			hasError: false,
			error: null,
			isRefreshing: false,
			locations: null,
			forceReload: true,
            data: global.allHolds,
            unavailableHolds: global.unavailableHolds,
            availableHolds: global.availableHolds,
            allHolds: global.allUserHolds,
		};
	}

	componentDidMount = async () => {
        this.setState({
            isLoading: false,
        })

        await this._fetchLocations();
        await this._fetchHolds();

	};

	// grabs the items on hold for the account
    _fetchHolds = async () => {

        this.setState({
            isLoading: true,
        });

        await getHolds().then(response => {
            if(response == "TIMEOUT_ERROR") {
                this.setState({
                    hasError: true,
                    error: translate('error.timeout'),
                    isLoading: false,
                    forceReload: false,
                });
            } else {
                this.setState({
                    hasError: false,
                    error: null,
                    isLoading: false,
                    forceReload: false,
                });
            }
        })
    }

    _forceScreenReload = async () => {
        var forceReload = true;

        this.setState({
            isLoading: true,
            loadingMessage: "Updating your holds",
        });

        await getHolds(forceReload).then(response => {
            if(response == "TIMEOUT_ERROR") {
                this.setState({
                    hasError: true,
                    error: translate('error.timeout'),
                    isLoading: false,
                    loadingMessage: null,
                    forceReload: false,
                });
            } else {
                this.setState({
                    hasError: false,
                    error: null,
                    isLoading: false,
                    loadingMessage: null,
                    forceReload: false,
                });
            }
        })
    }

    _fetchLocations = async () => {

        this.setState({
            isLoading: true,
        });

        await getPickupLocations().then(response => {
            if(response == "TIMEOUT_ERROR") {
                this.setState({
                    hasError: true,
                    error: translate('error.timeout'),
                    isLoading: false,
                });
            } else {
                this.setState({
                    locations: response,
                    hasError: false,
                    error: null,
                    isLoading: false,
                });
            }
        })
    }

    // handles the on-press action
	openGroupedWork = (item) => {
        this.props.navigation.navigate("GroupedWork", { item });
	};

	// renders the items on the screen
	renderHoldItem = (item) => {
        return (
            <HoldItem
              data={item}
              onPressItem={this.onPressItem}
              navigation={this.props.navigation}
              locations={this.state.locations}
              forceScreenReload={this._forceScreenReload}
              openGroupedWork={this.openGroupedWork}
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

	_onRefresh() {
	    this.setState({ isRefreshing: true }, () => {
            this._forceScreenReload().then(() => {
                this.setState({ isRefreshing: false });
            });
	    });
	}

	_listEmptyComponent = () => {
		return (
			<Center mt={5} mb={5}>
				<Text bold fontSize="lg">
					{translate('holds.no_holds')}
				</Text>
			</Center>
		);
	};

	render() {
		if (this.state.isLoading) {
			return ( loadingSpinner(this.state.loadingMessage) );
		}

        if (this.state.hasError) {
            return ( loadError(this.state.error, this._fetchHolds) );
        }

		return (
			<Box h="100%">
				<FlatList
					data={global.allUserHolds}
					ListEmptyComponent={this._listEmptyComponent()}
					renderItem={({ item }) => this.renderHoldItem(item)}
					keyExtractor={(item) => item.id}
                    refreshControl={
                        <RefreshControl
                            refreshing={this.state.isRefreshing}
                            onRefresh={this._onRefresh.bind(this)}
                        />
                    }
				/>
            </Box>
		);
	}
}

function HoldItem(props) {
    const { onPressItem, data, navigation, locations, forceScreenReload, openGroupedWork } = props;
    const { isOpen, onOpen, onClose } = useDisclose();

    const [loading, setLoading] = useState(false);
    const startLoading = () => {
        setTimeout(() => {
            setLoading(false)
        }, 5000);
    };

    // format some dates
    if(data.availableDate != null) {
        var availableDateUnix = moment.unix(data.availableDate);
        var availableDate = moment(availableDateUnix).format("MMM D, YYYY");
    } else {
        var availableDate = "";
    }

    if(data.expirationDate) {
        var expirationDateUnix = moment.unix(data.expirationDate);
        var expirationDate = moment(expirationDateUnix).format("MMM D, YYYY");
    } else {
        var expirationDate = "";
    }

    // check freeze status to see which option to display
    if(data.canFreeze == true) {
        if(data.frozen == true) {
            var label = translate('holds.thaw_hold');
            var method = "thawHold";
            var icon = "play";
        } else {
            var label = translate('holds.freeze_hold_with_reactivation');
            var method = "freezeHold";
            var icon = "pause";
            if(data.available) {
                var label = translate('overdrive.delay_checkout');
                var method = "freezeHold";
                var icon = "pause";
            }
        }
    }

    if(data.status == "Pending") {
        var statusColor = "green";
    }

    if(data.title){
        var title = data.title;
        var title = title.substring(0, title.lastIndexOf('/'));
        if(title == '') {
            var title = data.title;
        }
    }

    if(data.author){
        var author = data.author;
        var countComma = author.split(',').length-1;
        if (countComma > 1) {
            var author = author.substring(0, author.lastIndexOf(','));
        }
    }

    var source = data.source;
    var holdSource = data.holdSource;
    if(source == 'ils') {
        var readyMessage = data.status;
    } else {
        var readyMessage = translate('overdrive.hold_ready');
    }

    var isAvailable = data.available;
    var updateLocation = data.locationUpdateable;
    if(data.available && data.locationUpdateable) {
        var updateLocation = false;
    }

    var checkoutOnline = false;
    if(data.available && source != 'ils') {
        var checkoutOnline = true;
    }

    var cancelable = false;
    if(!data.available && source != 'ils') {
        var cancelable = true;
    } else if (!data.available && source == 'ils') {
        var cancelable = true;
    }

    return (
    <>
    <Pressable onPress={onOpen}>
    <ListItem bottomDivider >
        <Avatar source={{ uri: data.coverUrl }} size="56px" alt={data.title}/>
        <ListItem.Content>
            <Text fontSize="sm" bold mb={1}>
                {data.frozen ? <Badge colorScheme="yellow" rounded="4px" mt={-.5}>{data.status}</Badge> : null}
                {data.available ?
                    <Badge colorScheme="green" rounded="4px" mt={-.5}>{readyMessage}</Badge>
                 : null} {title}
            </Text>

            {author ?
            <Text bold fontSize="xs">
                {translate('grouped_work.author')}: <Text fontSize="xs">{author}</Text>
            </Text>
             : null}
            <Text bold fontSize="xs">
                {translate('grouped_work.format')}: <Text fontSize="xs">{data.format}</Text>
            </Text>
            {data.source == "ils" ? <Text bold fontSize="xs">
                {translate('pickup_locations.pickup_location')}: <Text fontSize="xs">{data.currentPickupName}</Text>
            </Text>
            : null }
                {data.available ? <Text bold fontSize="xs">{translate('holds.pickup_by')}: <Text fontSize="xs">{expirationDate}</Text></Text> : <Text bold fontSize="xs">{translate('holds.position_queue')}: <Text fontSize="xs">{data.position}</Text></Text>}
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
        {data.groupedWorkId != null ?
        <Actionsheet.Item startIcon={ <Icon as={MaterialIcons} name="search"  color="trueGray.400" mr="1" size="6" /> }
            onPress={ () => {
            openGroupedWork(data.groupedWorkId);
            onClose(onClose);
            }} >
            {translate('grouped_work.view_item_details')}
        </Actionsheet.Item>
        : null
        }
        {cancelable ?
        <Actionsheet.Item startIcon={ <Icon as={MaterialIcons} name="cancel"  color="trueGray.400" mr="1" size="6" /> }
            onPress={ () => {
                cancelHold(data.cancelId, data.recordId, data.source);
                setTimeout(function(){forceScreenReload();}.bind(this),1000);
                onClose(onClose);
            }} >
            {translate('holds.cancel_hold')}
        </Actionsheet.Item>
         : null}
        {data.allowFreezeHolds ?
        <Actionsheet.Item startIcon={ <Icon as={MaterialCommunityIcons} name={icon}  color="trueGray.400" mr="1" size="6" /> }
            onPress={ () => {
                 if (data.frozen == true) {
                     thawHold(data.cancelId, data.recordId, data.source);
                 } else {
                     freezeHold(data.cancelId, data.recordId, data.source);
                 };
                setTimeout(function(){forceScreenReload();}.bind(this),1000);
                onClose(onClose);
            }}
            >
            {label}
        </Actionsheet.Item>
         : null}

        {updateLocation ?
        <SelectPickupLocation locations={locations} onClose={onClose} currentPickupId={data.pickupLocationId} holdId={data.cancelId}  />
         : null}

        </Actionsheet.Content>
      </Actionsheet>
    </>
    )
}

const SelectPickupLocation = (props) => {

    const { locations, label, onClose, currentPickupId, holdId } = props;

	const [showModal, setShowModal] = useState(false);
	let [value, setValue] = React.useState(currentPickupId);

	return (
	<>
        <Actionsheet.Item startIcon={ <Icon as={Ionicons} name="location"  color="trueGray.400" mr="1" size="6" /> }
            onPress={ () => {
                setShowModal(true);
            }} >
            {translate('pickup_locations.change_pickup_location')}
        </Actionsheet.Item>
        <Modal isOpen={showModal} onClose={() => setShowModal(false)} closeOnOverlayClick={false}>
            <Modal.Content>
                <Modal.CloseButton />
                <Modal.Header>{translate('pickup_locations.change_hold_location')}</Modal.Header>
                <Modal.Body>
                    <FormControl>
                        <FormControl.Label>{translate('pickup_locations.select_new_pickup')}</FormControl.Label>
                        <Radio.Group
                            name="pickupLocations"
                            value={value}
                            onChange={(nextValue) => {
                                setValue(nextValue);
                            }}
                            mt="1"
                        >
                            {locations.map((item, index) => {
                                const locationId = item.locationId;
                                const code = item.code;
                                const id = locationId.concat("_",code);
                                return <Radio value={id} my={1}>{item.name}</Radio>;
                            })}
                        </Radio.Group>
                    </FormControl>
                </Modal.Body>
                <Modal.Footer>
                    <Button.Group space={2} size="md">
                        <Button colorScheme="muted" variant="outline" onPress={() => setShowModal(false)}>{translate('general.close_window')}</Button>
                        <Button
                            onPress={() => {
                                changeHoldPickUpLocation(holdId, value);
                                setShowModal(false);
                                onClose(onClose);
                            }}
                        >
                            {translate('pickup_locations.change_location')}
                        </Button>
                    </Button.Group>
                </Modal.Footer>
            </Modal.Content>
        </Modal>
    </>
	)
}