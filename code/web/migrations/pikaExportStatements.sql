SELECT * FROM user INTO OUTFILE '/data/aspen-discovery/{sitename}/pika_export/users.csv' FIELDS TERMINATED BY ',' ENCLOSED BY '"';

SELECT cat_username, roles.name FROM user_roles inner join user on user_roles.userId = user.id inner join roles on user_roles.roleId = roles.roleId INTO OUTFILE '/data/aspen-discovery/{sitename}/pika_export/userRoles.csv' FIELDS TERMINATED BY ',' ENCLOSED BY '"';

SELECT * FROM materials_request INTO OUTFILE '/data/aspen-discovery/{sitename}/pika_export/materials_request.csv' FIELDS TERMINATED BY ',' ENCLOSED BY '"';

SELECT user.cat_username, rating, review, dateRated, full_title, author, permanent_id, GROUP_CONCAT(CONCAT(type, ':', identifier)) FROM user_work_review inner join user on user_work_review.userId = user.id inner join grouped_work on permanent_id = groupedRecordPermanentId inner join grouped_work_primary_identifiers on grouped_work_id = grouped_work.id GROUP BY user.cat_username, rating, review, dateRated, full_title, author, permanent_id INTO OUTFILE '/data/aspen-discovery/{sitename}/pika_export/patronRatingsAndReviews.csv' FIELDS TERMINATED BY ',' ENCLOSED BY '"';

SELECT cat_username, user_reading_history_work.source, sourceId, user_reading_history_work.title, user_reading_history_work.author, user_reading_history_work.format, checkOutDate, grouped_work.full_title, grouped_work.author, permanent_id, GROUP_CONCAT(CONCAT(type, ':', identifier)) from user inner join user_reading_history_work on user.id = userId INNER JOIN grouped_work on permanent_id = groupedWorkPermanentId inner join grouped_work_primary_identifiers on grouped_work_id = grouped_work.id group by cat_username, user_reading_history_work.source, sourceId, user_reading_history_work.title, user_reading_history_work.author, user_reading_history_work.format, checkOutDate, grouped_work.full_title, grouped_work.author, permanent_id INTO OUTFILE '/data/aspen-discovery/{sitename}/pika_export/patronReadingHistory.csv' FIELDS TERMINATED BY ',' ENCLOSED BY '"';

SELECT cat_username, user_list.id, title, description, user_list.created, public, defaultSort from user_list INNER JOIN user on user_id = user.id where deleted = 0 INTO OUTFILE '/data/aspen-discovery/{sitename}/pika_export/patronLists.csv' FIELDS TERMINATED BY ',' ENCLOSED BY '"';

SELECT cat_username, listId, notes, dateAdded, full_title, author, permanent_id, GROUP_CONCAT(CONCAT(type, ':', identifier)) from user_list_entry inner join user_list on listId = user_list.id inner join user on user_id = user.id inner join grouped_work on permanent_id = user_list_entry.groupedWorkPermanentId inner join grouped_work_primary_identifiers on grouped_work_id = grouped_work.id GROUP BY cat_username, listId, notes, dateAdded, full_title, author, permanent_id, weight ORDER BY listId, weight, dateAdded INTO OUTFILE '/data/aspen-discovery/{sitename}/pika_export/patronListEntries.csv' FIELDS TERMINATED BY ',' ENCLOSED BY '"';

SELECT cat_username, user_not_interested.dateMarked, grouped_work.full_title, grouped_work.author, permanent_id, GROUP_CONCAT(CONCAT(type, ':', identifier)) from user inner join user_not_interested on user.id = userId INNER JOIN grouped_work on permanent_id = groupedRecordPermanentId inner join grouped_work_primary_identifiers on grouped_work_id = grouped_work.id group by cat_username, user_not_interested.dateMarked, grouped_work.full_title, grouped_work.author, permanent_id INTO OUTFILE '/data/aspen-discovery/{sitename}/pika_export/patronNotInterested.csv' FIELDS TERMINATED BY ',' ENCLOSED BY '"';

SELECT primaryUser.cat_username, linkedUser.cat_username, user_link.linkingDisabled FROM user_link inner join user as primaryUser on primaryAccountId = primaryUser.id inner join user as linkedUser on linkedAccountId = linkedUser.id INTO OUTFILE '/data/aspen-discovery/{sitename}/pika_export/patronLinkedUsers.csv' FIELDS TERMINATED BY ',' ENCLOSED BY '"';

SELECT primaryUser.cat_username, blockedUser.cat_username, user_link_blocks.blockLinking FROM user_link_blocks inner join user as primaryUser on primaryAccountId = primaryUser.id inner join user as blockedUser on blockedLinkAccountId = blockedUser.id INTO OUTFILE '/data/aspen-discovery/{sitename}/pika_export/patronLinkBlocking.csv' FIELDS TERMINATED BY ',' ENCLOSED BY '"';

SELECT user.cat_username, search.id, search.session_id, search.folder_id, search.created, search.title, search.saved, search.search_object, search.searchSource FROM search LEFT JOIN user on search.user_id = user.id where saved = 1 INTO OUTFILE '/data/aspen-discovery/{sitename}/pika_export/saved_searches.csv' FIELDS TERMINATED BY ',' ENCLOSED BY '"';

SELECT * from list_widgets INTO OUTFILE '/data/aspen-discovery/{sitename}/pika_export/list_widgets.csv' FIELDS TERMINATED BY ',' ENCLOSED BY '"';

SELECT * from list_widget_lists INTO OUTFILE '/data/aspen-discovery/{sitename}/pika_export/list_widget_lists.csv' FIELDS TERMINATED BY ',' ENCLOSED BY '"';

SELECT source_grouped_work.full_title, source_grouped_work.author, sourceGroupedWorkId, GROUP_CONCAT(CONCAT(source_grouped_work_primary_identifiers.type, ':', source_grouped_work_primary_identifiers.identifier)) from merged_grouped_works INNER JOIN grouped_work as source_grouped_work on permanent_id = sourceGroupedWorkId inner join grouped_work_primary_identifiers as source_grouped_work_primary_identifiers on grouped_work_id = grouped_work.id group by cat_username, user_not_interested.dateMarked, grouped_work.full_title, grouped_work.author, permanent_id INTO OUTFILE '/data/aspen-discovery/{sitename}/pika_export/mergedWorks.csv' FIELDS TERMINATED BY ',' ENCLOSED BY '"';

SELECT source_grouped_work.full_title as sourceTitle,
       source_grouped_work.author as sourceAuthor,
       sourceGroupedWorkId,
       GROUP_CONCAT(CONCAT(source_grouped_work_primary_identifiers.type, ':', source_grouped_work_primary_identifiers.identifier)),
       destination_grouped_work.full_title as destinationTitle,
       destination_grouped_work.author as destinationAuthor,
       destinationGroupedWorkId,
       GROUP_CONCAT(CONCAT(destination_grouped_work_primary_identifiers.type, ':', destination_grouped_work_primary_identifiers.identifier)),
       notes
from merged_grouped_works
         INNER JOIN grouped_work as source_grouped_work on source_grouped_work.permanent_id = sourceGroupedWorkId
         inner join grouped_work_primary_identifiers as source_grouped_work_primary_identifiers on source_grouped_work_primary_identifiers.grouped_work_id = source_grouped_work.id
         INNER JOIN grouped_work as destination_grouped_work on destination_grouped_work.permanent_id = destinationGroupedWorkId
         inner join grouped_work_primary_identifiers as destination_grouped_work_primary_identifiers on destination_grouped_work_primary_identifiers.grouped_work_id = destination_grouped_work.id
GROUP BY sourceTitle, sourceAuthor, sourceGroupedWorkId, destinationTitle, destinationAuthor, destinationGroupedWorkId, notes
INTO OUTFILE '/data/aspen-discovery/{sitename}/pika_export/mergedGroupedWorks.csv' FIELDS TERMINATED BY ',' ENCLOSED BY '"';