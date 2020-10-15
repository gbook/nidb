#!/bin/bash

#nii_qa
#Created by J Taylor on 11/19/09
#Purpose:  To perform various quality assurance tests on MRI data.  All MRI data
#  can be subject to SNR calculations.  Additionally, 4D data can be
#  analyzed in term of motion and "per-voxel SNR" (i.e. voxelwise mean/stdev).
#
#   There are two general SNR estimates that this script provides:
#
#   1)"Per-voxel SNR": take the mean of each voxel in a time series and
#   divide it by that same voxel's standard deviation.  Then average this
#   number for all within-brain voxels to come up with one number.  This
#   metric is of course irrelevant if one is inputting a single volume and
#   not a time series of data.
#
#   2)"Inside/Outside SNR": the more traditional approach of dividing the
#   average signal intensities within the brain with an average of signal
#   intensities outside the brain.  This program currently takes an 8 by 8
#   voxel box at the four corners of each slice as the noise estimate.
#
#   For both approaches, what is classified as "within-brain" is determined
#   by running the fsl brain extraction program BET on the input file.  The
#   results are therefore air to any imperfections in this brain extraction.
#
#Usage: nii_snr <arguments> 
#
#    ... where mandatory arguments include:
#
#    -i <file_name>: input nifti file.  This argument can be used
#	more than once (i.e., for batching).  Input file can be
#	3D or 4D, as noted by the dim4 field of the NIFTI header
#	(there is presently no support for multidimensional files
#	indexed via a dim field higher than 4).  Time series data must be in a
#   single 4D file to be treated as time series data.
#
#   ... and optional arguments include:
#
#   -echo: echo commands rather than execute them
#   -l: log results to default log file (see value of variable outlog in script)
#   -o <file_name>: log results to text file called <file_name>
#   -s <number>: skip to <number> volume in input file
#   -v <number>: set program verbosity.  Default is 1.  The following verbosity
#       levels are currently used:
#       0: print nothing to screen
#       1: print basic information and results to screen
#       2: print basic information, results, and debugging info to screen
#
#Examples:
#
#   1) An fMRI time series, skipping the first 4 volumes and writing the output
#       to a text file, and suppressing screen output:
#
#       nii_qa -i fmri.nii -s 4 -o fmri_snr.txt -v 0
#
#   2) An sMRI file
#
#       nii_qa -i smri.nii.gz
#
#
#Output: either or both of the following:
#   -results printed to screen (when verbosity >= 0)
#   -results printed to file (when -l or -o used, as described above)
#
#Dependencies: FSL
#
#Last update: 4/1/10 by JH
################## END HEADER #################

#Check for sufficient inputs.  If insufficient, print comments above
if [ ${#} -lt 1 ]; then
	cat ${0} | while read i; do
		if  [ `echo ${i}|grep -c "END HEADER"` -gt 0 ]; then
			break
		else
			echo $i|sed 's/^\#//g'
		fi
	done 
	exit 0
fi

#Read in mandatory command-line arguments
smode="eval"        #Set to eval for real running of script, echo for echoing
writelog_tf="F"     #Toggle writing out text output
writeout_tf="F"     #Toggle writing out text output
outlog="~/nii_qa.csv"  #Default log file
vbse=1              #Verbosity level
nin=0               #Counter for # of input images
nctr=1              #Counter for image processing loop
firstvol=0          #First volume to examine (useful to skip saturated images)

#Read in optional command-line arguments
until [ -z "$1" ]; do
	case "$1" in
		-i | -input	)
			in="${in} ${2}"
			let nin+=1
			;;
		-e | -echo | -smode	)
			smode="echo"
			;;
		-l | -log	)
       		writelog_tf="T"
			;;	
        -out | -o )
       		writeout_tf="T"
		    outlog=${2}
        	;;
        -s | -skip	)
			firstvol=${2}
			;;	
		-v | -verbose	)
			vbse=${2}
			;;	
		-t | -tmp	)
			tmpdir=${2}
			;;	
	esac
	shift
done


#Initialize more variables
myrand=`dd if=/dev/urandom count=128 bs=1 2>&1 | md5sum | cut -b-10`
tmpbase="/tmp/nii_qa_${myrand}" #Base name for temporary files
boxsize=10                      #Size of ROI box to draw for noise estimate

#Check dependencies
for i in fslval fslstats fslmaths fslroi mcflirt bet; do
    if [ "x`which ${i} 2>/dev/null`" == "x" ]; then
        echo "*** nii_qa ERROR: missing dependency ${i}"
        echo "*** Is FSL installed and in your default path?"
        exit 0
    fi
done

#Talk to user before processing
if [ ${vbse} -gt 0 ]; then
	echo "***************** RUNNING nii_qa *********************"
fi

#Loop over all file inputs
ls ${in}|while read myfile; do

	#Only proceeed if input is an existing file
	if [ -f ${myfile} ]; then

        #Create base name for output
        myoutbase=`echo ${myfile}|sed 's/\..*//g'`

		#Initialize image-specific variables
		id=`echo ${myfile}|cut -c 1-11`
		xdim=`fslval ${myfile} dim1`
		ydim=`fslval ${myfile} dim2`
		zdim=`fslval ${myfile} dim3`
		tdim=`fslval ${myfile} dim4`
		nvols=`echo "${tdim}-${firstvol}"|bc -l`
        if [ ${firstvol} -gt 0 ] && [ ${tdim} -le 1 ]; then
            myfirstvol=0
        else
            myfirstvol=${firstvol}
        fi

		#Update user
		if [ ${vbse} -gt 0 ] && [ ${smode} == "eval" ]; then
			echo "*** Image ${nctr} of ${nin}: ${myfile}"
			if [ ${myfirstvol} -gt 0 ]; then
			    echo "*** Skipping to vol ${firstvol} in time series"
			fi
		fi
		if [ ${vbse} -gt 2 ]; then
			echo "*** ${myfile} dimensions: ${xdim}, ${ydim}, ${zdim}, ${tdim}"
		fi

		#Create temp. input file, cropping vols if needed
		mycmd="fslroi ${myfile} ${tmpbase}_in ${myfirstvol} ${tdim}"
		$smode $mycmd

		#Create average volume
		mycmd="fslmaths ${tmpbase}_in -Tmean ${tmpbase}_tmean"
		$smode $mycmd

		#Brain extract average vol
		mycmd="bet ${tmpbase}_tmean ${tmpbase}_tmean_brain"
		$smode $mycmd

		#Convert brain to binary mask
		mycmd="fslmaths ${tmpbase}_tmean_brain -bin ${tmpbase}_tmean_brain"
		$smode $mycmd

		#Calculate inside/outside SNR
		#Calculate box coordinates 
		boxx=$((${xdim}-${boxsize}-1))
		boxy=$((${ydim}-${boxsize}-1))

		#Grab first corner
		mycmd="fslroi ${tmpbase}_in ${tmpbase}_roi1 0 ${boxsize} 0 ${boxsize} 0 ${zdim} 0 ${tdim}"
		$smode $mycmd

		#Grab second corner
		mycmd="fslroi ${tmpbase}_in ${tmpbase}_roi2 ${boxx} ${boxsize} 0 ${boxsize} 0 ${zdim} 0 ${tdim}"
		$smode $mycmd

		#Grab third corner
		mycmd="fslroi ${tmpbase}_in ${tmpbase}_roi3 ${boxx} ${boxsize} ${boxy} ${boxsize} 0 ${zdim} 0 ${tdim}"
		$smode $mycmd

		#Grab fourth corner
		mycmd="fslroi ${tmpbase}_in ${tmpbase}_roi4 0 ${boxsize} ${boxy} ${boxsize} 0 ${zdim} 0 ${tdim}"
		$smode $mycmd

		#Concatenate Noise ROIs
		mycmd="fslmerge -t ${tmpbase}_roi ${tmpbase}_roi1 ${tmpbase}_roi2 ${tmpbase}_roi3 ${tmpbase}_roi4"
		$smode $mycmd

		#Calculate average of Noise ROIs		
		mycmd="fslmaths ${tmpbase}_roi -Tmean ${tmpbase}_mroi"
		$smode $mycmd

		mycmd="fslstats ${tmpbase}_mroi -m"
		if [ ! "x${smode}" == "xecho" ]; then
			noise=`${mycmd}`
		else
			echo $mycmd
		fi
		
		#Calculate i/o SNR map
		mycmd="fslmaths ${tmpbase}_tmean -div ${noise} ${tmpbase}_iosnr; fslmaths ${tmpbase}_iosnr -mul ${tmpbase}_tmean_brain ${tmpbase}_iosnr"
		$smode $mycmd
		
		#If not in echo mode, get results and tell user
        if [ ${smode} == eval ]; then
    		iosnr_brain=`fslstats ${tmpbase}_iosnr -M`
    
	    	if [ ${vbse} -gt 0 ]; then
	    		echo "*** Inside/Outside SNR = ${iosnr_brain}"
	    	fi
            txt="`basename ${myfile}`\t${pvsnr_brain}\t${iosnr_brain}" 				
    		if [ ${writelog_tf} == "T" ]; then echo -e ${txt} >> ${outlog}; fi
    		if [ ${writeout_tf} == "T" ]; then
    		    #echo -e "image_name\tpvsnr_brain\tiosnr_brain\tmot_abs_mean\tmot_rel_mean\tmot_abs_disp_max\tmot_rel_disp_max" > ${outlog}
    		    echo -e "image_name\tpvsnr_brain\tiosnr_brain" > ${outlog}
    		    echo -e ${txt} >> ${outlog}
            fi
        fi
        
		#If input file is 4D, do additional QA calculations
		if [ ${tdim} -gt 3 ]; then

			#Calculate per-voxel SNR
			mycmd="fslmaths ${tmpbase}_in -Tstd ${tmpbase}_tstd"
			$smode $mycmd

			mycmd="fslmaths ${tmpbase}_tmean -div ${tmpbase}_tstd ${tmpbase}_pvsnr; fslmaths ${tmpbase}_pvsnr -mul ${tmpbase}_tmean_brain ${tmpbase}_pvsnr"
			$smode $mycmd

			mycmd="fslstats ${tmpbase}_pvsnr -M"
			if [ ! "x${smode}" == "xecho" ]; then
				pvsnr_brain=`${mycmd}`
				if [ ${vbse} -gt 0 ]; then
					echo "*** Per-voxel SNR = ${pvsnr_brain}"
				fi
			else
				echo $mycmd
			fi

			# run fsl_motion_outliers
			#mymotioncmd="fsl_motion_outliers -i ${tmpbase}_in -o ${tmpbase}_motionoutliers -s ${tmpbase}_motionoutliers2"
			#$smode $mymccmd

			#Run motion correction functions
			mymccmd="mcflirt -in ${tmpbase}_in -out ${tmpbase}_mcvol -rmsrel -rmsabs -plots -stats"
			$smode $mymccmd

            #Only proceed further if we're not in echo mode
             if [ ${smode} == "eval" ]; then

    			#Read absolute displacement vector into array
    			myctr=0
    			while read myval; do
    				abs_mean_vals[${myctr}]=${myval}
    				let myctr+=1
    			done < ${tmpbase}_mcvol_abs.rms

    			#Read relative displacement vetor into array
    			myctr=0
    			while read myval; do
    				rel_mean_vals[${myctr}]=${myval}
    				let myctr+=1
    			done < ${tmpbase}_mcvol_rel.rms

    			#Find max abs. displacement
    			abs_values=${#abs_mean_vals[@]}
    			abs_disp_max=${abs_mean_vals[0]}

    			#Cycle over elements in the array to find max displacement
    			for (( j=0; j < abs_values; j++ )); do
    				if [ `echo "${abs_mean_vals[j]} > $abs_disp_max" | bc` -gt 0 ]; then
    					abs_disp_max=${abs_mean_vals[j]}
    				fi
    			done
    			
    			#Find max rel. displacement
    			rel_values=${#rel_mean_vals[@]}
    			rel_disp_max=${rel_mean_vals[0]}

    			#Cycle over elements in the array to find max displacement
    			for (( i=0; i < rel_values; i++ )); do
    				if [ `echo "${rel_mean_vals[i]} > $rel_disp_max" | bc` -gt 0 ]; then
    					rel_disp_max=${rel_mean_vals[i]}
    				fi
    			done
    			
    			#Read in mean abs and rel displacements
    			abs_mean=`cat ${tmpbase}_mcvol_abs_mean.rms`
    			rel_mean=`cat ${tmpbase}_mcvol_rel_mean.rms`

    			#Order of motion params: rot(x) rot(y) rot(z) trans(x) trans(y) trans(z)
    			rotx=`awk '{print $1}' ${tmpbase}_mcvol.par`
    			roty=`awk '{print $2}' ${tmpbase}_mcvol.par`
    			rotz=`awk '{print $3}' ${tmpbase}_mcvol.par`
    			transx=`awk '{print $4}' ${tmpbase}_mcvol.par`
    			transy=`awk '{print $5}' ${tmpbase}_mcvol.par`
    			transz=`awk '{print $6}' ${tmpbase}_mcvol.par`

    			#Give feedback to user
    			if [ ${vbse} -gt 0 ]; then
    				echo "*** Motion: max absolute displacement: ${abs_disp_max}"
    				echo "*** Motion: max relative displacement: ${rel_disp_max}"
    				echo "*** Motion: mean absolute displacement ${abs_mean}"
    				echo "*** Motion: mean relative displacement ${rel_mean}"
    			fi
    		else
    			#If file wasn't 4D, set all 4D-related parameters
    			# to N/A
    			abs_disp="N/A"; abs_mean="N/A"
    			rel_disp="N/A"; rel_mean="N/A"
    			rotx="N/A"; roty="N/A"; rotz="N/A" 
    			transx="N/A"; transy="N/A"; transz="N/A"
    			pvsnr_brain="N/A"
    		fi

    		#Write results to text file, if requested
            txt="`basename ${myfile}`\t${pvsnr_brain}\t${iosnr_brain}\t${abs_mean}\t${rel_mean}\t${abs_disp_max}\t${rel_disp_max}" 				
            #txt="`basename ${myfile}`\t${pvsnr_brain}\t${iosnr_brain}" 				
    		if [ ${writelog_tf} == "T" ]; then echo -e ${txt} >> ${outlog}; fi
    		if [ ${writeout_tf} == "T" ]; then
    		    echo -e "image_name\tpvsnr_brain\tiosnr_brain\tmot_abs_mean\tmot_rel_mean\tmot_abs_disp_max\tmot_rel_disp_max" > ${outlog}
    		    #echo -e "image_name\tpvsnr_brain\tiosnr_brain" > ${outlog}
    		    echo -e ${txt} >> ${outlog}
            fi

        fi  #End smode loop

		#Increment image counter
		let nctr+=1

	else    #If image wasn't found, quit program
		echo "*** nii_qa ERROR: cannot find file ${myfile}, quitting"
       	exit
	fi
done

# Clean up temporary files
$smode mv ${tmpbase}*.par ${tmpdir}
$smode mv ${tmpbase}* ${tmpdir}
$smode rm -rf ${tmpbase}*

#Wrap up
if [ ${vbse} -gt 0 ]; then
	echo "******** nii_qa COMPLETE ********"
fi
