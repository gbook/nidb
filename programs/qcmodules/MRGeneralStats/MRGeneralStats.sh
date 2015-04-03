# for ado2:
#FSLDIR=/usr/local/fsl; PATH=${FSLDIR}/bin:${PATH}; . ${FSLDIR}/etc/fslconf/fsl.sh; export FSLDIR PATH;

# for cluster:
FSLDIR=/opt/fsl/fsl; PATH=${FSLDIR}/bin:${PATH}; . ${FSLDIR}/etc/fslconf/fsl.sh; export FSLDIR PATH;

perl MRGeneralStats.pl $1