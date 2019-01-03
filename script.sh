#!/usr/bin/env bash
for i in {0..0}
do 
	php -d memory_limit=4096M structure.php data $i 10 > log_${i}.log 2>&1
done 
echo "\n\nCOMPLTED\n\n";
