#!/usr/bin/env bash
if [[ $# -ne 3 ]]; then
	echo "You must provide 3 parameters: username, password, Pika database name"
fi
USER=$1
PASSWORD=$2
DBNAME=$3
mysqldump --user=$1 --password=$PASSWORD --add-drop-table $DBNAME \
 archive_requests\
 archive_subjects\
 author_enrichment\
 browse_category\
 browse_category_library\
 browse_category_location\
 browse_category_subcategories\
 claim_authorship_requests\
 editorial_reviews\
 list_widget_lists\
 list_widget_lists_links\
 list_widgets\
 marriage\
 materials_request\
 materials_request_fields_to_display\
 materials_request_form_fields\
 materials_request_formats\
 materials_request_status\
 merged_grouped_works\
 nongrouped_records\
 obituary\
 person\
 search\
 search_stats\
 search_stats_new\
 spelling_words\
 tags\
 user\
 user_link\
 user_link_blocks\
 user_list\
 user_list_entry\
 user_not_interested\
 user_rating\
 user_reading_history_work\
 user_roles\
 user_staff_settings\
 user_tags\
 user_work_review\
 > /data/$DBNAME.migration.sql
gzip /data/$DBNAME.migration.sql
