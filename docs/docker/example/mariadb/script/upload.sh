#!/usr/bin/expect

set pass "!1qaz@2wsx"
set path [lindex $argv 0];
set timeout 7200

spawn scp -o StrictHostKeyChecking=no $path ai4dt@140.116.56.30:/MarkQ/dbbackup
expect "connecting (yes/no)?" {send "yes\n" } \
"password:" { send "$pass\n" }

expect eof
