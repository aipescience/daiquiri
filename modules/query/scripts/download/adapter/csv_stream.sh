#!/bin/bash

#  
#  Copyright (c) 2012, 2013 Jochen S. Klar <jklar@aip.de>,
#                           Adrian M. Partl <apartl@aip.de>, 
#                           AIP E-Science (www.aip.de)
#
#  Licensed under the Apache License, Version 2.0 (the "License");
#  you may not use this file except in compliance with the License.
#  See the NOTICE file distributed with this work for additional
#  information regarding copyright ownership. You may obtain a copy
#  of the License at
#
#  http://www.apache.org/licenses/LICENSE-2.0
#
#  Unless required by applicable law or agreed to in writing, software
#  distributed under the License is distributed on an "AS IS" BASIS,
#  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
#  See the License for the specific language governing permissions and
#  limitations under the License.
#

# parse key value pairs
for a in $(echo $1 | tr "&" " "); do
    b=(`echo $a | tr "=" " "`);
    eval ${b[0]}=${b[1]}
done

path=$(dirname $file)
fileName=$(basename $file)
fileName=${fileName%.*}

if [ ! -z "$toolBin" ]; then
	toolBin+="/"
fi

#move away any .sql file we might already have here
if [ -f $path/$fileName.sql ]
then
	mv $path/$fileName.sql $path/$fileName.sql.old
fi

#move away any .sql file we might already have here                                                                                                                       
if [ -f $path/$fileName.sql ]
then
        mv $path/$fileName.sql $path/$fileName.sql.old
fi

if [ -z "$socket" ]; then
    ${toolBin}mysql -h$host -P$port -u$username -p$password $dbname -e "SELECT * FROM \`$table\`" | sed 's/\t/","/g;s/^/"/;s/$/"/;s/\n//g' > $path/$fileName.txt.head
else
    ${toolBin}mysql -h$host -P$port -u$username -p$password $dbname -e "SELECT * FROM \`$table\`" | sed 's/\t/","/g;s/^/"/;s/$/"/;s/\n//g' > $path/$fileName.txt.head
fi

cat $path/$fileName.txt.head $path/$fileName.txt > $path/$fileName.txt.new
rm $path/$fileName.txt.head

#mysqldump writes \N for any NULL value, however we don't want this.                                                                                                      
#therefore we are using this regex:                                                                                                                                       
perl -pe 's/(^|,)\\N((?=,)|$)/\1/g' $path/$fileName.txt.new

rm $path/$fileName.txt.new

#plain text: replace all \N that are preceeded with a , or are at the beginning of the line                                                                               
#and are either followed by a , (don't add the , to the match) or are at the end of the line                                                                              

if [ -f $path/$fileName.txt ]
then
    rm $path/$fileName.txt
	rm $path/$fileName.sql
fi

#move old sql file back
if [ -f $path/$fileName.sql.old ]
then
	mv $path/$fileName.sql.old $path/$fileName.sql
fi
