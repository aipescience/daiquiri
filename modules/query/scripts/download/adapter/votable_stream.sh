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

if [ ! -z "$toolBin" ]; then
	toolBin+="/"
fi

if [ -z "$socket" ]; then
    ${toolBin}mysqldump_vo --vo -h$host -P$port -u$username -p$password $dbname $table
else
    ${toolBin}mysqldump_vo --vo --socket=$socket -u$username -p$password $dbname $table
fi

