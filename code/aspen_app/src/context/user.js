import React, {createContext} from 'react';

const userContext = createContext({
	user: [],
	location: [],
	library: [],
	browseCategories: [],
	updateUser: () => {},
	updateBrowseCategories: () => {},
});

export { userContext };