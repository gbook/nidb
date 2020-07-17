#!/bin/bash    
n=536;#the variable that I want to be incremented999
next_n=$[$n+1]
sed -i "/#the variable that I want to be incremented$/s/=.*#/=$next_n;#/" ${0}
echo $n
