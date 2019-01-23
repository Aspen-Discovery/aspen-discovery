#!/bin/bash

cd /data/vufind-plus/hoopla/marc

# It should be possible to use a directory listing to get all the files,
# but I haven't gotten it to work yet. plb 12-18-2015

curl --remote-name --remote-time --silent --show-error --compressed --time-cond /data/vufind-plus/hoopla/marc/USA_ALL_AB.mrc https://cassini.marmot.org/hooplamarc/USA_ALL_AB.mrc
curl --remote-name --remote-time --silent --show-error --compressed --time-cond /data/vufind-plus/hoopla/marc/USA_ALL_Comic.mrc https://cassini.marmot.org/hooplamarc/USA_ALL_Comic.mrc
curl --remote-name --remote-time --silent --show-error --compressed --time-cond /data/vufind-plus/hoopla/marc/USA_ALL_eBook.mrc https://cassini.marmot.org/hooplamarc/USA_ALL_eBook.mrc
curl --remote-name --remote-time --silent --show-error --compressed --time-cond /data/vufind-plus/hoopla/marc/USA_ALL_Music.mrc https://cassini.marmot.org/hooplamarc/USA_ALL_Music.mrc
curl --remote-name --remote-time --silent --show-error --compressed --time-cond /data/vufind-plus/hoopla/marc/USA_ALL_TV.mrc https://cassini.marmot.org/hooplamarc/USA_ALL_TV.mrc
curl --remote-name --remote-time --silent --show-error --compressed --time-cond /data/vufind-plus/hoopla/marc/USA_ALL_Video.mrc https://cassini.marmot.org/hooplamarc/USA_ALL_Video.mrc

#curl --remote-name --remote-time --silent --show-error --compressed --time-cond /data/vufind-plus/hoopla/marc/USA_AB.mrc https://cassini.marmot.org/hooplamarc/USA_AB.mrc
#curl --remote-name --remote-time --silent --show-error --compressed --time-cond /data/vufind-plus/hoopla/marc/USA_ALL_Comic.mrc https://cassini.marmot.org/hooplamarc/USA_ALL_Comic.mrc
#curl --remote-name --remote-time --silent --show-error --compressed --time-cond /data/vufind-plus/hoopla/marc/USA_ALL_eBook.mrc https://cassini.marmot.org/hooplamarc/USA_ALL_eBook.mrc
#curl --remote-name --remote-time --silent --show-error --compressed --time-cond /data/vufind-plus/hoopla/marc/USA_ALL_Music.mrc https://cassini.marmot.org/hooplamarc/USA_ALL_Music.mrc
#curl --remote-name --remote-time --silent --show-error --compressed --time-cond /data/vufind-plus/hoopla/marc/USA_No_PA_Music.mrc https://cassini.marmot.org/hooplamarc/USA_No_PA_Music.mrc
#curl --remote-name --remote-time --silent --show-error --compressed --time-cond /data/vufind-plus/hoopla/marc/USA_Only_PA_Music.mrc https://cassini.marmot.org/hooplamarc/USA_Only_PA_Music.mrc
#curl --remote-name --remote-time --silent --show-error --compressed --time-cond /data/vufind-plus/hoopla/marc/USA_TV_Video.mrc https://cassini.marmot.org/hooplamarc/USA_TV_Video.mrc
#curl --remote-name --remote-time --silent --show-error --compressed --time-cond /data/vufind-plus/hoopla/marc/USA_Video.mrc https://cassini.marmot.org/hooplamarc/USA_Video.mrc
#
# Check that the Hoopla Marc is updating monthly
OLDHOOPLA=$(find /data/vufind-plus/hoopla/marc/ -name "*.mrc" -mtime +30)
if [ -n "$OLDHOOPLA" ]
then
	echo "There are Hoopla Marc files older than 30 days : "
	echo "$OLDHOOPLA"
fi
