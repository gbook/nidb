#!/bin/sh

# mriqc.sh <indir> <outdir> <subjectUID>

docker run --rm -v $1:/data:ro -v $2:/out nipreps/mriqc:latest /data /out participant --participant-label sub-$3

#if [[ ! -z $1 ]];
#then
#	rm -rv $1
#fi