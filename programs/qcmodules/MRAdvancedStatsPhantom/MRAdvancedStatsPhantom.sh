# for ado2:
#FSLDIR=/usr/local/fsl; PATH=${FSLDIR}/bin:${PATH}; . ${FSLDIR}/etc/fslconf/fsl.sh; export FSLDIR PATH;

# for cluster:
PATH=/opt/afni:/opt/bxh_xcede_tools/bin:${PATH};
export PATH;

perl MRAdvancedStatsPhantom.pl $1