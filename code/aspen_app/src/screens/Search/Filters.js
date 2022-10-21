import React, {Component} from "react";
import {SafeAreaView, ScrollView} from "react-native";
import {Box, Text, Pressable, HStack} from "native-base";
import _ from "lodash";

// custom components and helper files
import {userContext} from "../../context/user";
import {loadingSpinner} from "../../components/loadingSpinner";
import FacetCluster from "./FacetCluster";
import {GLOBALS} from "../../util/globals";

export default class Filters extends Component {
	constructor(props, context) {
		super(props, context);
		this.state = {
			isLoading: true,
			options: this.props.route.params?.options ?? [],
			term: this.props.route.params?.term ?? "",
			appliedFilters: [],
		};
		this._isMounted = false;
	}

	componentDidMount = async () => {
		this._isMounted = true;
		this.setState({
			isLoading: false,
		});
	}

	componentWillUnmount() {
		this._isMounted = false;
	}

	componentDidUpdate(prevProps) {
		if(prevProps.route.params?.pendingUpdates !== this.props.route.params?.pendingUpdates) {
			// handle going back to the filter categories with pending changes
			const result = this.props.route.params?.pendingUpdates;
			//console.log(result);
			//this.setPendingAppliedValues(result);
		}
	}

	renderFacet = (payload) => {
		return(
			<Pressable>
				<HStack space={3}>
					<Text>
						{payload}
					</Text>
				</HStack>
			</Pressable>
		)
	}

	static contextType = userContext;

	render() {
		const user = this.context.user;
		const location = this.context.location;
		const library = this.context.library;

		if(this.state.isLoading) {
			return (loadingSpinner());
		}

		const options = this.state.options;

		return (
			<SafeAreaView style={{flex: 1}}>
				<ScrollView>
					<Box safeArea={5}>
						{options.map((facet) => {
							return(
								<FacetCluster
									facet={facet}
									term={this.state.term}
									navigation={this.props.navigation}
									pendingUpdates=""
									options={this.state.options}
								/>
							)
						})}
					</Box>
				</ScrollView>
			</SafeAreaView>
		)
	}
}