#!/bin/sh
# script to import all ABIDE data into NIDB
# change the project from 800000 to another # if you need to, but
# that project MUST exist in NIDB
# usage: importabide.pl /path/to/tgzs

FSLDIR=/usr/local/fsl
PATH=${FSLDIR}/bin:${PATH}
. ${FSLDIR}/etc/fslconf/fsl.sh
export FSLDIR PATH

# do the import. unzipping/importing/deleting is is done individually to preserve diskspace

tar -xvzf $1/Caltech.tgz -C $1
perl /nidb/programs/parseincomingabide.pl -c 800000 -l ABIDE -d $1/Caltech -s Caltech
rm -R $1/Caltech

tar -xvzf $1/CMU_a.tgz -C $1
perl /nidb/programs/parseincomingabide.pl -c 800000 -l ABIDE -d $1/CMU_a -s CMU
rm -R $1/CMU_a

tar -xvzf $1/CMU_b.tgz -C $1
perl /nidb/programs/parseincomingabide.pl -c 800000 -l ABIDE -d $1/CMU_b -s CMU
rm -R $1/CMU_b

tar -xvzf $1/KKI.tgz -C $1
perl /nidb/programs/parseincomingabide.pl -c 800000 -l ABIDE -d $1/KKI -s KKI
rm -R $1/KKI

tar -xvzf $1/Leuven_1.tgz -C $1
perl /nidb/programs/parseincomingabide.pl -c 800000 -l ABIDE -d $1/Leuven_1 -s Leuven
rm -R $1/Leuven_1

tar -xvzf $1/Leuven_2.tgz -C $1
perl /nidb/programs/parseincomingabide.pl -c 800000 -l ABIDE -d $1/Leuven_2 -s Leuven
rm -R $1/Leuven_2

tar -xvzf $1/MaxMun_a.tgz -C $1
perl /nidb/programs/parseincomingabide.pl -c 800000 -l ABIDE -d $1/MaxMun_a -s MaxMun
rm -R $1/MaxMun_a

tar -xvzf $1/MaxMun_b.tgz -C $1
perl /nidb/programs/parseincomingabide.pl -c 800000 -l ABIDE -d $1/MaxMun_b -s MaxMun
rm -R $1/MaxMun_b

tar -xvzf $1/MaxMun_c.tgz -C $1
perl /nidb/programs/parseincomingabide.pl -c 800000 -l ABIDE -d $1/MaxMun_c -s MaxMun
rm -R $1/MaxMun_c

tar -xvzf $1/MaxMun_d.tgz -C $1
perl /nidb/programs/parseincomingabide.pl -c 800000 -l ABIDE -d $1/MaxMun_d -s MaxMun
rm -R $1/MaxMun_d

tar -xvzf $1/NYU_a.tgz -C $1
perl /nidb/programs/parseincomingabide.pl -c 800000 -l ABIDE -d $1/NYU_a -s NYU
rm -R $1/NYU_a

tar -xvzf $1/NYU_b.tgz -C $1
perl /nidb/programs/parseincomingabide.pl -c 800000 -l ABIDE -d $1/NYU_b -s NYU
rm -R $1/NYU_b

tar -xvzf $1/NYU_c.tgz -C $1
perl /nidb/programs/parseincomingabide.pl -c 800000 -l ABIDE -d $1/NYU_c -s NYU
rm -R $1/NYU_c

tar -xvzf $1/NYU_d.tgz -C $1
perl /nidb/programs/parseincomingabide.pl -c 800000 -l ABIDE -d $1/NYU_d -s NYU
rm -R $1/NYU_d

tar -xvzf $1/NYU_e.tgz -C $1
perl /nidb/programs/parseincomingabide.pl -c 800000 -l ABIDE -d $1/NYU_e -s NYU
rm -R $1/NYU_e

tar -xvzf $1/OHSU.tgz -C $1
perl /nidb/programs/parseincomingabide.pl -c 800000 -l ABIDE -d $1/OHSU -s OHSU
rm -R $1/OHSU

tar -xvzf $1/Olin.tgz -C $1
perl /nidb/programs/parseincomingabide.pl -c 800000 -l ABIDE -d $1/Olin -s Olin
rm -R $1/Olin

tar -xvzf $1/Pitt.tgz -C $1
perl /nidb/programs/parseincomingabide.pl -c 800000 -l ABIDE -d $1/Pitt -s Pitt
rm -R $1/Pitt

tar -xvzf $1/SBL.tgz -C $1
perl /nidb/programs/parseincomingabide.pl -c 800000 -l ABIDE -d $1/SBL -s SBL
rm -R $1/SBL

tar -xvzf $1/SDSU.tgz -C $1
perl /nidb/programs/parseincomingabide.pl -c 800000 -l ABIDE -d $1/SDSU -s SDSU
rm -R $1/SDSU

tar -xvzf $1/Stanford.tgz -C $1
perl /nidb/programs/parseincomingabide.pl -c 800000 -l ABIDE -d $1/Stanford -s Stanford
rm -R $1/Stanford

tar -xvzf $1/Trinity.tgz -C $1
perl /nidb/programs/parseincomingabide.pl -c 800000 -l ABIDE -d $1/Trinity -s Trinity
rm -R $1/Trinity

tar -xvzf $1/UCLA_1.tgz -C $1
perl /nidb/programs/parseincomingabide.pl -c 800000 -l ABIDE -d $1/UCLA_1 -s UCLA
rm -R $1/UCLA_1

tar -xvzf $1/UCLA_2.tgz -C $1
perl /nidb/programs/parseincomingabide.pl -c 800000 -l ABIDE -d $1/UCLA_2 -s UCLA
rm -R $1/UCLA_2

tar -xvzf $1/UM_1.tgz -C $1
perl /nidb/programs/parseincomingabide.pl -c 800000 -l ABIDE -d $1/UM_1 -s UM
rm -R $1/UM_1

tar -xvzf $1/UM_2.tgz -C $1
perl /nidb/programs/parseincomingabide.pl -c 800000 -l ABIDE -d $1/UM_2 -s UM
rm -R $1/UM_2

tar -xvzf $1/Yale.tgz -C $1
perl /nidb/programs/parseincomingabide.pl -c 800000 -l ABIDE -d $1/Yale -s Yale
rm -R $1/Yale
