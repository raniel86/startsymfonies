#!/usr/bin/env bash

# check if is valid os
if [ "$(uname)" == "Darwin" ]; then
    echo
    echo "Invalid OS, try to use install_mac.sh instead."
    echo
    exit 1
fi

if [ -z ./.env ]; then
    cp ./.env.dist ./.env
fi

# variable definition
baseIp="127.0.0.1"
basePort="8000"
databasePath=$(cat ./.env |grep DATABASE_URL |sed 's/sqlite:\/\/\/%kernel\.project_dir%\///g' | awk -F'=' '{print $2}' |sed 's/^ //g' |sed 's/ $//g')
currentDir=$(pwd)

echo "SELECT PHP EXECUTABLE"
whereis php |sed 's/php: //g' |sed 's/ /\n/g'
echo
read -p "PHP executable ["$(whereis php |sed 's/php: //g' |sed 's/ /\n/g' |head -n1)"]: " phpExecutable
phpExecutable=${phpExecutable:-$(whereis php |sed 's/php: //g' |sed 's/ /\n/g' |head -n1)}

echo $phpExecutable > "./startsymfonies_php_executable.txt"

# installing symfony
$(echo $phpExecutable) $(whereis composer |sed 's/composer: //g' |sed 's/ /\n/g' |head -n1) install
yarn install
yarn dev

lineAutoStart="@reboot sleep 12 && $phpExecutable $currentDir/bin/console server:start $baseIp:$basePort"
lineAutoStartSymfonies="@reboot sleep 15 && $phpExecutable $currentDir/bin/console app:symfonies:start-all"
lineAutoStartExist=$(crontab -l |grep "$lineAutoStart" |wc -l)
lineAutoStartSymfoniesExist=$(crontab -l |grep "$lineAutoStartSymfonies" |wc -l)
serverRunning=$($(echo $phpExecutable) bin/console server:status -q;echo $?)

# create database if not exist
if [ ! -e "$databasePath" ]; then
    $(echo $phpExecutable) bin/console doctrine:schema:create
fi

# perform base commands for correct working
$(echo $phpExecutable) bin/console cache:clear
$(echo $phpExecutable) bin/console assets:install --symlink

# check if user want to perform a scan now
#echo
#read -p "Do you want to perform a scan for symfonies now [y/N]? " -n 1 -r
#if [[ $REPLY =~ ^[Yy]$ ]]; then
#    echo
#    echo 'SCANNING DIRECTORIES...'
#    $(echo $phpExecutable) bin/console app:symfonies:scan
#    echo 'SCAN COMPLETED'
#fi

# check if user want to autostart startsymfonies on pc boot
if [ "$lineAutoStartExist" -eq "0" ]; then
    echo
    read -p "Do you want to run startsymfonies on pc boot [y/N]? " -n 1 -r
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        (crontab -l; echo; echo "# STARTSYMFONIES - AUTOSTART"; echo "$lineAutoStart") |crontab -
    fi
fi

# check if user want to autostart symfonies with an ip and a port on pc boot
if [ "$lineAutoStartSymfoniesExist" -eq "0" ]; then
    echo
    read -p "Do you want to run all symfonies with an ip and a port on pc boot [y/N]? " -n 1 -r
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        (crontab -l; echo; echo "# STARTSYMFONIES - AUTOSTART SYMFONIES"; echo "$lineAutoStartSymfonies") |crontab -
    fi
fi

# run startsymfonies now if not running
if [ "$serverRunning" -eq "1" ]; then
    $(echo $phpExecutable) bin/console server:start $baseIp:$basePort
fi

echo
echo 'Startsymfonies successfully installed :)'
echo

exit 0