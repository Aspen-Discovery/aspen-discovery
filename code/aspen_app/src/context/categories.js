import { create } from 'apisauce';
import _ from 'lodash';
import { createContext, useState } from 'react';

import { createAuthTokens, getHeaders, UsePostData } from '../util/apiAuth';
import { GLOBALS } from '../util/globals';
import { LIBRARY } from '../util/loadLibrary';

export const CategoriesData = () => {
     const [value, setValue] = useState();
     const postBody = UsePostData();
     const discovery = create({
          baseURL: LIBRARY.url ?? GLOBALS.url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               maxCategories: 10,
               LiDARequest: true,
               includeSubCategories: true,
          },
     });
     discovery
          .post('/SearchAPI?method=getAppActiveBrowseCategories', postBody)
          .then((response) => {
               if (response?.data?.result) {
                    const allItems = response.data.result;
                    const allCategories = [];
                    if (!_.isUndefined(allItems)) {
                         allItems.map(function (category, index, array) {
                              const subCategories = category['subCategories'] ?? [];
                              const manyLists = category['lists'] ?? [];
                              const records = category['records'] ?? [];
                              const lists = [];
                              if (!_.isUndefined(subCategories) && subCategories.length > 0) {
                                   subCategories.forEach((item) =>
                                        allCategories.push({
                                             key: item.key,
                                             title: item.title,
                                             source: item.source,
                                             records: item.records,
                                        })
                                   );
                              } else {
                                   if (!_.isUndefined(subCategories) || !_.isUndefined(manyLists) || !_.isUndefined(records)) {
                                        if (!_.isUndefined(subCategories) && subCategories.length > 0) {
                                             subCategories.forEach((item) =>
                                                  allCategories.push({
                                                       key: item.key,
                                                       title: item.title,
                                                       source: item.source,
                                                       records: item.records,
                                                  })
                                             );
                                        } else {
                                             if (!_.isUndefined(manyLists) && manyLists.length > 0) {
                                                  manyLists.forEach((item) =>
                                                       lists.push({
                                                            id: item.sourceId,
                                                            categoryId: item.id,
                                                            source: 'List',
                                                            title_display: item.title,
                                                       })
                                                  );
                                             }

                                             let id = category.key;
                                             const categoryId = category.key;
                                             if (lists.length !== 0) {
                                                  if (!_.isUndefined(category.listId)) {
                                                       id = category.listId;
                                                  }

                                                  let numNewTitles = 0;
                                                  if (!_.isUndefined(category.numNewTitles)) {
                                                       numNewTitles = category.numNewTitles;
                                                  }
                                                  allCategories.push({
                                                       key: id,
                                                       title: category.title,
                                                       source: category.source,
                                                       numNewTitles,
                                                       records: lists,
                                                       id: categoryId,
                                                  });
                                             } else {
                                                  if (!_.isUndefined(category.listId)) {
                                                       id = category.listId;
                                                  }

                                                  let numNewTitles = 0;
                                                  if (!_.isUndefined(category.numNewTitles)) {
                                                       numNewTitles = category.numNewTitles;
                                                  }
                                                  allCategories.push({
                                                       key: id,
                                                       title: category.title,
                                                       source: category.source,
                                                       numNewTitles,
                                                       records: category.records,
                                                       id: categoryId,
                                                  });
                                             }
                                        }
                                   }
                              }
                         });
                         setValue(allCategories);
                    }
               }
          })
          .catch((err) => {
               console.log(err);
          });

     return value;
};

export const categoriesContext = createContext({
     categories: [],
     updateCategories: () => {},
});