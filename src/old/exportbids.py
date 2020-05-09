# ------------------------------------------------------------------------------
# NIDB exportbids.py
# Copyright (C) 2004 - 2018
# Gregory A Book <gregory.book@hhchealth.org> <gbook@gbook.org>
# Olin Neuropsychiatry Research Center, Hartford Hospital
# ------------------------------------------------------------------------------
# GPLv3 License:
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
# ------------------------------------------------------------------------------

# -----------------------------------------------------------------------------
# This program provides BIDS export support for NiDB
# 
# [11/8/2018] - Greg Book
#		* Wrote initial program.
# -----------------------------------------------------------------------------
import sys
import nidb
import MySQLdb

# -----------------------------------------------------------------------------
# ---------- main -------------------------------------------------------------
# -----------------------------------------------------------------------------
def main():
	# get start time
	#t0 = time.clock()
	
	nidb.LoadConfig()
	db = MySQLdb.connect(host=nidb.cfg['mysqlhost'], user=nidb.cfg['mysqluser'], passwd=nidb.cfg['mysqlpassword'], db=nidb.cfg['mysqldatabase'])
	
	# indir is the original dicom directory for that series in the archive
	#moduleseriesid = sys.argv[1]
	
	# get all the path information from the database
	sqlstring = "select * from qc_moduleseries where qcmoduleseries_id = " + moduleseriesid
	result = db.cursor(MySQLdb.cursors.DictCursor)
	result.execute(sqlstring)
	row = result.fetchone()
	seriesid = row['series_id']
	modality = row['modality']
	
	# get the paths to the raw data, and copy it to a temp directory
	sqlstring = "select a.series_num, a.is_derived, a.data_type, a.bold_reps, a.img_rows, b.study_num, d.uid from {0}_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id left join projects e on c.project_id = e.project_id where a.{1}series_id = '{2}'".format(modality,modality,seriesid)
	print(sqlstring)
	result = db.cursor(MySQLdb.cursors.DictCursor)
	result.execute(sqlstring)
	row = result.fetchone()
	uid = row['uid']
	study_num = row['study_num']
	series_num = row['series_num']
	datatype = row['data_type']
	boldreps = row['bold_reps']
	imgrows = row['img_rows']

	if boldreps > 1:
		print("Bold reps greater than 1, skipping")
		exit(0)
	if imgrows > 512:
		print("Y dimension greater than 512 pixels, skipping")
		exit(0)
		
	# build the indir
	indir = "{0}/{1}/{2}/{3}/{4}".format(cfg['archivedir'], uid, study_num, series_num, datatype)
	print(indir)
	
	#exit(0)
	# create a tmp directory
	outdir = '/tmp/Py_' + GenerateRandomString()
	print ("Output directory: " + outdir)
	
	if not os.path.exists(outdir):
		os.makedirs(outdir)

	# create a nifti file to check the sizes
	#systemstring = "{0}/./dcm2nii -b '{0}/dcm2nii_4D.ini' -a y -e y -g y -p n -i n -d n -f n -o '{1}' {2}/*.dcm".format(cfg['scriptdir'],outdir,indir)
	#print("\nRunning: [" + systemstring + "]\n")
	#call(systemstring, shell=True)
	# rename the file to 4D
	#systemstring = "mv {0}/*.nii.gz {0}/4D.nii.gz".format(outdir)
	#print("\nRunning: [" + systemstring + "]\n")
	#call(systemstring, shell=True)
	
	# get file dimensions
	#systemstring = "fslval"
	#dim4 = 
	
	# copy all dicom files to outdir (in case you screw up and delete the raw dicoms :(
	systemstring = "cp " + indir + "/*.dcm " + outdir
	print("Running: [" + systemstring + "]")
	call(systemstring, shell=True)
	
	# go into the temp directory
	os.chdir(outdir)
	
	# convert all the dicom files in the input directory INTO the temp directory as png files
	systemstring = "mogrify -depth 16 -format png *.dcm"
	print("Running: [" + systemstring + "]")
	call(systemstring, shell=True)
	
	# get list of png files
	pngfiles = sorted(glob.glob('*.png'))
	#print pngfiles
	
	# check if there's only 1 file
	if len(pngfiles) < 2:
		print(0)
		exit(0)
	
	i = 0
	#totala = totalb = 0
	#print '[%s]' % ', '.join(map(str, pngfiles))

	allhist = []
	for pngfile in pngfiles:
		
		print(os.path.exists(pngfile))
		brain = matplotlib.image.imread(pngfile)
		type(brain)
		print(brain.shape)
		print(brain.dtype)
		#fft = numpy.log10(1+abs(numpy.fft.fftshift(numpy.fft.fft2(brain))))
		fft = 1+abs(numpy.fft.fftshift(numpy.fft.fft2(brain)))
		filename = "slice%d.png"%i
		matplotlib.image.imsave(filename,fft)
		
		print("Entering into azimuthalAverage({0}/{1}/{2})".format(uid,study_num,series_num))
		histogram = azimuthalAverage(fft)
		print("Should be done with azimuthalAverage({0}/{1}/{2})".format(uid,study_num,series_num))
		
		# remove last element, because its always a NaN
		#print 'Before [%s]' % ', '.join(map(str, histogram))
		#print histogram.shape
		histogram = numpy.delete(histogram, -1, 0)
		#print 'After [%s]' % ', '.join(map(str, histogram))
		# add this histo to the total histo
		allhist.append(histogram)

		#print allhist.size
		#print float(i)
		#print float(len(pngfiles)-1.0)
		
		c = str(float(i)/float(len(pngfiles)-1.0))
		#print "%.1f %% complete"%( (float(i)/(len(pngfiles)-1))*100)
		lines = pyplot.plot(numpy.log10(histogram))
		pyplot.setp(lines, color='#0000AA', alpha=0.25)
		#totala += a
		#totalb += b
		i+=1

	print("Hello")
	
	allhist2 = numpy.vstack(allhist)
	meanhistogram = allhist2.mean(axis=1)
	
	print(len(meanhistogram))
	#del meanhistogram[-1]
	
	print('[%s]' % ', '.join(map(str, allhist)))
	#a,b = linreg(range(len(meanhistogram)),meanhistogram)
	#print "a,b [%d,%d]",a,b
	
	# find mean slopes
	#meana = totala/float(i)
	#meanb = totalb/float(i)
	dists = []
	dists.extend(range(0,len(meanhistogram)))
	#print dists
	slope, intercept, r_value, p_value, std_err = stats.linregress(dists,meanhistogram)
	pyplot.setp(lines, color='#0000AA', alpha=0.25)
	print("R-value: ")
	print(slope)

	#write out the final composite histogram
	pyplot.xlabel('Frequency (lo -> hi)')
	pyplot.ylabel('Power (log10)')
	suptitle = 'Radial average of FFT (' + indir + ')'
	pyplot.suptitle(suptitle)
	title = "R^2: {0}".format(slope)
	pyplot.title(title)
	pyplot.grid(True)
	
	#slope, intercept = numpy.polyfit(meanhistogram, dists, 1)
	#idealhistogram = intercept + (slope * meanhistogram)
	
	
	#r_sq = numpy.r_squared(dists, idealhistogram)
	#r_sq = slope*slope
	#fit_label = 'Linear fit ({0:.2f})'.format(slope)
	#pyplot.plot(dists, idealhistogram, color='red', linestyle='--', label=fit_label)
	#pyplot.annotate('r^2 = {0:.2f}'.format(r_sq), (0.05, 0.9), xycoords='axes fraction')
	#pyplot.legend(loc='lower right')

	# save the figure
	pyplot.savefig('StructuralMotionHistogram.png')
	
	# record the slope/intercept
	if not os.path.exists(indir + "/qa"):
		os.makedirs(indir + "/qa")
	qafile = indir + "/qa/StructuralMotionR2.txt"
	file = open(qafile, "w")
	theline = "%f"%(slope)
	file.write(theline)
	file.close()
	
	# get stop time
	#t = time.clock() - t0
	
	# insert the result name into the database
	sqlstring = "select qcresultname_id from qc_resultnames where qcresult_name = 'MotionR2'"
	resultA = db.cursor(MySQLdb.cursors.DictCursor)
	resultA.execute(sqlstring)
	rowA = resultA.fetchone()
	if resultA.rowcount > 0:
		resultnameid = rowA['qcresultname_id']
	else:
		# insert a row
		sqlstring = "insert into qc_resultnames (qcresult_name, qcresult_type) values ('MotionR2','number')"
		print(sqlstring)
		resultB = db.cursor(MySQLdb.cursors.DictCursor)
		resultB.execute(sqlstring)
		resultnameid = resultB.lastrowid
		
	# InnoDB table... needs commit!
	sqlstring = "insert into qc_results (qcmoduleseries_id, qcresultname_id, qcresults_valuenumber, qcresults_datetime, qcresults_cputime) values ({0}, {1}, {2}, now(), {3})".format(moduleseriesid,resultnameid,slope,t)
	print(sqlstring)
	cursor = db.cursor()
	try:
		cursor.execute(sqlstring)
		db.commit()
	except:
		print("SQL statement [" + sqlstring + "] failed")
		db.rollback()
		exit(0)

	# insert the image name into the resultnames table
	sqlstring = "select qcresultname_id from qc_resultnames where qcresult_name = 'MotionR2 Plot'"
	resultA = db.cursor(MySQLdb.cursors.DictCursor)
	resultA.execute(sqlstring)
	rowA = resultA.fetchone()
	if resultA.rowcount > 0:
		resultnameid = rowA['qcresultname_id']
	else:
		# insert a row
		sqlstring = "insert into qc_resultnames (qcresult_name, qcresult_type) values ('MotionR2 Plot', 'image')"
		print(sqlstring)
		resultB = db.cursor(MySQLdb.cursors.DictCursor)
		resultB.execute(sqlstring)
		resultnameid = resultB.lastrowid
		
	# insert an entry for the image into the database
	sqlstring = "insert into qc_results (qcmoduleseries_id, qcresultname_id, qcresults_valuefile, qcresults_datetime) values ({0}, {1}, 'StructuralMotionHistogram.png', now())".format(moduleseriesid,resultnameid)
	print(sqlstring)
	cursor = db.cursor()
	try:
		cursor.execute(sqlstring)
		db.commit()
	except:
		print("SQL statement [" + sqlstring + "] failed")
		db.rollback()
		exit(0)

	# insert the R2 value into the mr_qa table
	sqlstring = "update mr_qa set motion_rsq = '{0}' where mrseries_id = {1}".format(r_value**2,seriesid)
	print(sqlstring)
	cursor = db.cursor()
	try:
		cursor.execute(sqlstring)
		db.commit()
	except:
		print("SQL statement [" + sqlstring + "] failed")
		db.rollback()
		exit(0)
	
	
	#copy the histogram back to the qa directory
	systemstring = "cp " + outdir + "/StructuralMotionHistogram.png " + "{0}/{1}/{2}/{3}/qa".format(cfg['archivedir'], uid, study_num, series_num)
	#print("Running: [" + systemstring + "]")
	call(systemstring, shell=True)
		
	# remove the temp directory and all its contents
	#shutil.rmtree(outdir)
	systemstring = "rm -r " + outdir
	print("Running: [" + systemstring + "]")
	call(systemstring, shell=True)
	
	exit(0)
	
# I guess this is needed to execute the main function if none other is called...
# basically defining an entry point into the program		
if __name__ == "__main__":
	sys.exit(main())