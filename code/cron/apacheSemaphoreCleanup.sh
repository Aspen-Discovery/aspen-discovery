#!/bin/sh

EMAIL=$1
MAX_SEMAPHORES=15

IPCS=/usr/bin/ipcs
IPCRM=/usr/bin/ipcrm
MAIL=/bin/mail

COUNT=`${IPCS} | grep apache | wc -l`

if [ "$COUNT" -le $MAX_SEMAPHORES ]; then
       #all is well, there are no semaphore build-ups.
       exit 0;
fi

#we have more than MAX_SEMAPHORES, so clear them out and restart Apache.

LIST=/root/cron/semaphore.txt

${IPCS} | grep apache | awk '{print $2}' > ${LIST}
for i in `cat ${LIST}`; do
{
       ${IPCRM} -s $i;
};
done;

osVersion=$( cut -d ' ' -f 4 /etc/redhat-release | cut -d '.' -f 1 )
if [ $osVersion -ge 7 ]; then
        systemctl restart httpd
else
        service httpd restart
fi

TXT="${COUNT} semaphores cleared for apache for `hostname`"
echo "${TXT}" | ${MAIL} -s "${TXT}" ${EMAIL}
exit 1;
