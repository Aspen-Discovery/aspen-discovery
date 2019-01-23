#!/bin/bash

# zinioDeleteCovers.sh
# James Staub
# Nashville Public Library

if [[ $# -ne 1 ]]; then
        echo "Please provide site directory, e.g., ${0} opac.marmot.org"
        exit
fi

site=$1
#echo $site

rm -fr /data/vufind-plus/$site/covers/small/RBZ*.png
rm -fr /data/vufind-plus/$site/covers/small/rzz*.png
rm -fr /data/vufind-plus/$site/covers/medium/RBZ*.png
rm -fr /data/vufind-plus/$site/covers/medium/rzz*.png
rm -fr /data/vufind-plus/$site/covers/large/RBZ*.png
rm -fr /data/vufind-plus/$site/covers/large/rzz*.png

exit 0
