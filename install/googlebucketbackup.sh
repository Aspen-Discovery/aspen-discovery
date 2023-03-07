# Set the database name, user, and password
DB_NAME=aspen
DB_USER=root
DB_PASS=

# Set the current date
DATE=`date +%Y%m%d`

# Set the backup file name and directory
BACKUP_FILE="$DB_NAME.$DATE.sql"
BACKUP_DIR=/data/aspen-discovery/alpha.test/sql_backup

# Set the Google Cloud Storage bucket name
BUCKET_NAME=kendra-test

#Perform Database Export using Aspen
#  cd /usr/local/aspen-discovery/code/web/cron; php backupAspen.php zeta.test
  # Result is written to /data/aspen-discovery/<sitename>/sql_backup

# Create the backup
 mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/$BACKUP_FILE

# Check if the mysqldump command was successful
if [ $? -ne 0 ]; then
  echo "Failed to create the backup"
  exit 1
fi


# Compress the backup file
gzip -f $BACKUP_DIR/$BACKUP_FILE

# check if compress is successfull
if [ $? -ne 0 ]; then
  echo "Failed to Compress the backup file"
  exit 1
fi

# Create backup filename
BACKUP_FILE="$BACKUP_FILE.gz"

# Use gsutil to upload the file to the bucket
gsutil cp $BACKUP_DIR/"$BACKUP_FILE" gs://$BUCKET_NAME/



# check if upload successfull
if [ $? -ne 0 ]; then
  echo "Failed to upload the backup"
  exit 1
fi

# Remove local backup file
# rm -f $BACKUP_DIR/$BACKUP_FILE
# echo "Backup successfully created and uploaded to the bucket"
