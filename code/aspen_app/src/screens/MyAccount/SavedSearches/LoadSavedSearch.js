import React, {Component} from "react";
import {loadingSpinner} from "../../../components/loadingSpinner";
import {userContext} from "../../../context/user";

export default class LoadSavedSearch extends Component {
	constructor(props, context) {
		super(props, context);
		this.state = {
			isLoading: true,
			hasError: false,
			error: null,
		};
	}

	componentDidMount = () => {
		const { route } = this.props;
		const libraryUrl = this.context.library.baseUrl;
		const id = route.params?.search ?? 0;
		const title = route.params?.name ?? '';

		console.log(route.params);

		this.setState({
			isLoading: false,
		})

		this.openSavedSearch(id, title, libraryUrl);
	};

	componentWillUnmount() {

	}

	openSavedSearch = (id, title, url) => {
		this.props.navigation.push("AccountScreenTab", {screen: 'SavedSearch', params: { search: id, name: title, libraryUrl: url }});
	}

	static contextType = userContext;

	render() {
		const { route } = this.props;
		const url = this.context.library.baseUrl;
		const id = route.params?.search ?? 0;
		const title = route.params?.name ?? '';

		if(this.state.isLoading) {
			return (loadingSpinner());
		} else {
			this.props.navigation.navigate("AccountScreenTab", {screen: 'SavedSearch', params: { search: id, name: title, libraryUrl: url }});
		}

		return null;
	}
}