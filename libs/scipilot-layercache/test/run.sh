#!/bin/bash

#

# THIS WAS TRYING TO GET XDEBUG REMOTE WORKING, BUT I DIDN'T MANAGE IT. JUST NOTHING HAPPENS...
# Simulate the following xdebug php.ini settings.
#php_flag xdebug.remote_enable On
#php_flag xdebug.remote_autostart On
#php_value  xdebug.remote_handler dbgp
#php_value xdebug.remote_port 9000
#php_value xdebug.remote_mode req
#
#; Restrict to specific desktops
#;pip
#php_value xdebug.remote_host 192.168.1.149
#
# From http://www.slideshare.net/lapistano/debugging-php-with-xdebug-inside-of-eclipse-pdt-21
#export DEBUG_CONFIG="idekey=ECLIPSE_DBGP"
# So I guessed:
#export DEBUG_CONFIG="idekey=ECLIPSE_DBGP remote_enable=On remote_autostart=On remote_autostart=On remote_handler=dbgp remote_port=9000 remote_mode=req remote_host=192.168.1.149"
# 
# Another method:
#phpunit --colors -d zend_extension="/usr/lib/php/modules/xdebug.so" -d xdebug.remote_enable=On -d xdebug.remote_autostart=On -d xdebug.remote_autostart=On -d xdebug.remote_handler=dbgp -d xdebug.remote_port=9000 -d xdebug.remote_mode=req -d xdebug.remote_host=192.168.1.149 .

# Coverage reporst are runing out of memory
export DEBUG_CONFIG="php_value memory_limit 100M"

clear
./phpunit.phar --colors --strict --stop-on-error tests #!> out/tests.log 
#phpunit --colors --coverage-html reports/ tests  > out/tests.log &
#tail -f out/tests.log

# Example handy single file runner copypasta
#clear; echo '****************************************************************\n' > /var/folders/c_/_y34mh7j61xfjjrzz677ry440000gn/T/fc/logs/cache.log; ./phpunit.phar --colors --strict  --stop-on-error --stop-on-failure  tests/classes/Cache/Layer/FileCacheTest.php 

export DEBUG_CONFIG=""
