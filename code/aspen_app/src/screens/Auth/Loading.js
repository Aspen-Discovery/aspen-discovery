import React, {Component} from "react";
import { Animated, Easing } from 'react-native';
import {Center, VStack, Spinner, Heading} from "native-base";
import _ from "lodash";
import Constants from "expo-constants";
import {userContext} from "../../context/user";

class LoadingScreen extends Component {
	constructor() {
		super();
		this.state = {
			isLoading: true,
			spinAnim: new Animated.Value(0),
		}
	}

	componentDidMount = async () => {

		if (!this.state.isLoading) {
			this.props.navigation.replace('Home');
		}

		Animated.loop(Animated.timing(
			this.state.spinAnim,
			{
				toValue: 1,
				duration: 4000,
				easing: Easing.linear,
				useNativeDriver: true
			}
		)).start();

	}

	checkContext = () => {
		this.setState({
			isLoading: false,
		})
	}

	static contextType = userContext;

	render() {
		const spin = this.state.spinAnim.interpolate({
			inputRange: [0, 1],
			outputRange: ['0deg', '360deg']
		});

		const user = this.context.user;
		const location = this.context.location;
		const library = this.context.library;
		const browseCategories = this.context.browseCategories;

		if(_.isEmpty(user) || _.isEmpty(location) || _.isEmpty(library) || _.isEmpty(browseCategories)) {
			return (
				<Center flex={1} px="3">
					<VStack space={5} alignItems="center">
						<Spinner size="lg" />
						<Heading color="primary.500" fontSize="md">
							Dusting the shelves...
						</Heading>
					</VStack>
				</Center>
			)
		} else {
			this.props.navigation.navigate('Tabs');
		}

		return null;
	}
}

export default LoadingScreen;
