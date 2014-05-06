#!/bin/bash

#  
#  Copyright (c) 2012-2014 Jochen S. Klar <jklar@aip.de>,
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

if [ ! -z "$binPath" ]; then
	binPath+="/"
fi

if [ -z "$socket" ]; then
    ${binPath}mysql -h$host -P$port -u$username -p$password $dbname -e "SELECT * FROM \`$table\`" | sed 's/\t/","/g;s/^/"/;s/$/"/;s/\n//g'
else
    ${binPath}mysql -S$socket -u$username -p$password $dbname -e "SELECT * FROM \`$table\`" | sed 's/\t/","/g;s/^/"/;s/$/"/;s/\n//g'
fi
