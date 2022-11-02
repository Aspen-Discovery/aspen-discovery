import React, {Component} from 'react';
import {ChevronRightIcon, HStack, Pressable, Text, VStack} from 'native-base';
import _ from 'lodash';

// custom components and helper files
import {userContext} from '../../context/user';
import {loadingSpinner} from '../../components/loadingSpinner';

export default class FacetCluster extends Component {
	static contextType = userContext;

	constructor(props, context) {
		super(props, context);
		this.state = {
			isLoading: true,
			term: this.props.term,
		};
		this._isMounted = false;
	}

	componentDidMount = async () => {
		this._isMounted = true;

		this.setState({
			isLoading: false,
			term: this.props.term,
		});
	};

	componentWillUnmount() {
		this._isMounted = false;
	}

	getAppliedFacets = (cluster) => {
		let applied = '';

		_.forEach(cluster, function(value) {
			if (applied.length === 0) {
				applied = applied.concat(_.toString(value['label']));
			} else {
				applied = applied.concat(', ', _.toString(value['label']));
			}
		});

		if (_.size(cluster) > 0) {
			return (
					<Text>{applied}</Text>
			);
		} else {
			return null;
		}
	};

	_handleOpenFacet = (cluster) => {
		const navigation = this.props.navigation;
		navigation.navigate('Facet', {
			data: cluster,
			defaultValues: [],
			title: cluster.label,
			term: this.state.term,
		});
	};

	render() {
		const cluster = this.props.options;

		if (this.state.isLoading) {
			return (loadingSpinner());
		}

		return (
				<Pressable borderBottomWidth="1" _dark={{borderColor: 'gray.600'}} borderColor="coolGray.200" py="5" onPress={() => this._handleOpenFacet(cluster)}>
					<VStack alignContent="center">
						<HStack justifyContent="space-between" align="center">
							<Text bold>x</Text>
							<ChevronRightIcon/>
						</HStack>

					</VStack>
				</Pressable>
		);
	}
}