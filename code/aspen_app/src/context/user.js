import React, {createContext} from 'react';

const userContext = createContext({
	user: [],
	location: [],
	library: [],
	browseCategories: [],
	pushToken: null,
	updateUser: () => {},
	updateBrowseCategories: () => {},
});

export { userContext };