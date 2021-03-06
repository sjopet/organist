#!/bin/bash

# This script is used within the anyterm console
# it runs a generated script which is given as parameter
#

logbase=$(dirname $0)/../../../../../../app/logs/scripts/
console=$(dirname $0)/../../../../../../app/console
scriptid=$1

script="$logbase$scriptid.sh"
$console organist:getcommand --id=$scriptid > $script
chmod 777 $script

mkdir -p $logbase
logfile=$logbase""$scriptid".log"

# keep track of execution time
start=$(date +%s)

# init logfile
echo "" >> $logfile
echo `date` >> $logfile
echo "" >> $logfile

# execute script
# todo check script command for recording instead of using tee
$script 2>&1 | tee -a $logfile
exitcode=${PIPESTATUS[0]}

# calculate duration
end=$(date +%s)
diff=$(( $end - $start ))

# process log and remove temp script and log
echo "Saving log and clearing temporary files ..."
$console organist:processlog --id=$scriptid --exitcode=$exitcode
echo ""

# check if script was succesfull
if [ $exitcode != '0' ]
then
	echo -e "\e[37m\e[41m Deployment $command returned with wrong exitcode. Please review settings and/or try again \e[0m"
else
	if [ $diff != '0' ]
	then
		echo -e "\e[37m\e[42m Deployment $command was succesfully executed in $diff seconds \e[0m"
	else
		echo -e "\e[37m\e[42m Deployment $command was succesfully executed in almost zero seconds \e[0m"
	fi
fi

# Self destruct
rm $script

# Kill old ssh-agents. Anything that runs longer than one day. Sometimes they tend not to be killed
ps -eo pid,etime,comm | awk '$2~/^[0-9]*-/ && $3~/ssh-agent/ { print $1 }' | xargs --no-run-if-empty kill

# Be sure that anyterm displays all output. Sometimes it doestn display last output, probably because its asynchronous
read -n1 -t1

exit $exitcode