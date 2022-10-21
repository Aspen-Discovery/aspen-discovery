import React, {Component} from "react";
import {ScrollView} from "react-native";
import {Box, Text, VStack, HStack, Pressable, CheckIcon} from "native-base";
import _ from "lodash";
import {Rating} from "react-native-elements";

// custom components and helper files
import {userContext} from "../../../context/user";
import {loadingSpinner} from "../../../components/loadingSpinner";
import {translate} from "../../../translations/translations";

export default class Facet_Rating extends Component {
	constructor(props, context) {
		super(props, context);
		this.state = {
			isLoading: true,
			appliedRating: "",
			value: "",
			item: this.props.data,
			category: this.props.category,
			updater: this.props.updater,
		};
		this._isMounted = false;
	}

	componentDidMount = async () => {
		this._isMounted = true;
		this.setState({
			isLoading: false,
		})

		this.appliedRating();
	}

	componentWillUnmount() {
		this._isMounted = false;
	}

	getRatingCount(star) {
		const {item} = this.state;
		let results = 0;
		if(_.find(item, ['value', star])) {
			results = _.find(item, ['value', star]);
			results = results['numResults'];
		}
		return results;
	}

	appliedRating = () => {
		const {item, category} = this.state;
		let appliedFilterValue = "";
		let value = "";
		if(_.find(item, ['isApplied', true])) {
			let appliedFilterObj = _.find(item, ['isApplied', true]);
			value = appliedFilterObj['value'];
			appliedFilterValue = "&filter[]=" + encodeURI(category + ':' + value)
		}

		this.setState({
			appliedRating: appliedFilterValue,
			value: value
		})
	}

	updateSearch = (star) => {
		const {category, updater} = this.state;
		let value = "&filter[]=" + encodeURI(category + ':' + star);
		this.setState({
			appliedRating: value,
			value: star
		})

		updater(category, star, false);
	}

	static contextType = userContext;

	render() {
		if(this.state.isLoading) {
			return (loadingSpinner());
		}

		return (
			<ScrollView>
				<Box safeArea={5}>
					<VStack space={3}>
						<Pressable onPress={() => this.updateSearch('fiveStar')}>
							<HStack space={2}>
								{this.state.value === "fiveStar" ? <CheckIcon size="5" color="emerald.500" /> : null}
								<Rating imageSize={20} readonly startingValue={5} />
								<Text>({this.getRatingCount('fiveStar')})</Text>
							</HStack>
						</Pressable>
						<Pressable onPress={() => this.updateSearch('fourStar')}>
							<HStack space={2}>
								{this.state.value === "fourStar" ? <CheckIcon size="5" color="emerald.500" /> : null}
								<Rating imageSize={20} readonly startingValue={4} />
								<Text>({this.getRatingCount('fourStar')})</Text>
							</HStack>
						</Pressable>
						<Pressable onPress={() => this.updateSearch('threeStar')}>
							<HStack space={2}>
								{this.state.value === "threeStar" ? <CheckIcon size="5" color="emerald.500" /> : null}
								<Rating imageSize={20} readonly startingValue={3} />
								<Text>({this.getRatingCount('threeStar')})</Text>
							</HStack>
						</Pressable>
						<Pressable onPress={() => this.updateSearch('twoStar')}>
							<HStack space={2}>
								{this.state.value === "twoStar" ? <CheckIcon size="5" color="emerald.500" /> : null}
								<Rating imageSize={20} readonly startingValue={2} />
								<Text>({this.getRatingCount('twoStar')})</Text>
							</HStack>
						</Pressable>
						<Pressable onPress={() => this.updateSearch('oneStar')}>
							<HStack space={2}>
								{this.state.value === "oneStar" ? <CheckIcon size="5" color="emerald.500" /> : null}
								<Rating imageSize={20} readonly startingValue={1} />
								<Text>({this.getRatingCount('oneStar')})</Text>
							</HStack>
						</Pressable>
						<Pressable onPress={() => this.updateSearch('Unrated')}>
							<HStack space={2}>
								{this.state.value === "Unrated" ? <CheckIcon size="5" color="emerald.500" /> : null}
								<Text>{translate('filters.unrated')} ({this.getRatingCount('Unrated')})</Text>
							</HStack>
						</Pressable>
					</VStack>
				</Box>
			</ScrollView>
		)
	}
}