#!/bin/bash

#
#  Copyright (c) 2012-2015  Jochen S. Klar <jklar@aip.de>,
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
#once is not enough... any smarter ideas to get rid of all this?
fileName=${fileName%.*}
fileName=${fileName%.*}

cd $path

# check if the tools we are using are actually here
if [ ! -f ${binPath}/mysql ]
then
    echo "Error: Could not locate mysql at ${binPath}/mysql" > $path/$fileName.err
    exit
fi

if [ ! -f ${binPath}/mysqldump ]
then
    echo "Error: Could not locate mysqldump at ${binPath}/mysqldump" > $path/$fileName.err
    exit
fi

if [ -z "$socket" ]; then
    ${binPath}mysql -h$host -P$port -u$username -p$password $dbname -e "SELECT * FROM \`$table\`" | sed 's/\t/","/g;s/^/"/;s/$/"/;s/\n//g' > $file 2> $path/$fileName.err
else
    ${binPath}mysql -S$socket -u$username -p$password $dbname -e "SELECT * FROM \`$table\`" | sed 's/\t/","/g;s/^/"/;s/$/"/;s/\n//g' > $file 2> $path/$fileName.err
fi

#mysqldump writes \N for any NULL value, however we don't want this.                                                                                                      
#therefore we are using this regex:                                                                                                                                       
#perl -pe 's/(^|,)\\N((?=,)|$)/\1/g' $path/$fileName.txt.new > $path/$fileName.txt 2>> $path/$fileName.err
#rm $path/$fileName.txt.new 2>> $path/$fileName.err
#plain text: replace all \N that are preceeded with a , or are at the beginning of the line                                                                               
#and are either followed by a , (don't add the , to the match) or are at the end of the line                                                                              

#clean error file from MYSQL stupidity
sed -i '/Warning:/d' $path/$fileName.err

#if error file has no size, remove it...
if [ ! -s $path/$fileName.err ]
then
    rm $path/$fileName.err

    #if there was no error we can now compress the files if requested
    if [[ "$compress" == "zip" ]]; then
        mv $file $file.old
        zip $file $file.old
        rm $file.old
    fi    

    if [[ "$compress" == "gzip" ]]; then
        mv $file $file.old
        gzip -c $file.old > $file
        rm $file.old
    fi    

    if [[ "$compress" == "bzip2" ]]; then
        mv $file $file.old
        bzip2 -c $file.old > $file
        rm $file.old
    fi    

    if [[ "$compress" == "pbzip2" ]]; then
        mv $file $file.old
        pbzip2 -c $file.old > $file
        rm $file.old
    fi
fi
