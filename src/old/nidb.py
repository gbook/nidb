# ------------------------------------------------------------------------------
# NIDB nidb.py
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

# the order of these calls is important...
import os
import re
import getopt
import math
import string
import random
from subprocess import call
import shutil
import glob
import time

# -----------------------------------------------------------------------------
# ---------- GenerateRandomString ---------------------------------------------
# -----------------------------------------------------------------------------
def GenerateRandomString(size=10, chars=string.ascii_letters + string.digits):
	return ''.join(random.choice(chars) for x in range(size))

	
# -----------------------------------------------------------------------------
# ---------- LoadConfig -------------------------------------------------------
# -----------------------------------------------------------------------------
# Load the NiDB configuration file which includes database and path info
# -----------------------------------------------------------------------------
def LoadConfig():
	global cfg
	cfg = {}
	if os.path.isfile('nidb.cfg'):
		filename = 'nidb.cfg'
	elif os.path.isfile('../nidb.cfg'):
		filename = '../nidb.cfg'
	elif os.path.isfile('../../prod/programs/nidb.cfg'):
		filename = '../../prod/programs/nidb.cfg'
	elif os.path.isfile('../../../../prod/programs/nidb.cfg'):
		filename = '../../../../prod/programs/nidb.cfg'
	elif os.path.isfile('../programs/nidb.cfg'):
		filename = '../programs/nidb.cfg'
	elif os.path.isfile('/home/nidb/programs/nidb.cfg'):
		filename = '/home/nidb/programs/nidb.cfg'
	elif os.path.isfile('/nidb/programs/nidb.cfg'):
		filename = '/nidb/programs/nidb.cfg'
	else:
		print "Could not open config file!"
		return

	print "Using config file [" + filename + "] attempting to open"
	f = open(filename, 'r')
	try:
		for line in f:
			line = line.strip()
			if (line != "") and (line[0] != "#"):
				[variable, value] = line.split(' = ')
				variable = re.sub('(\[|\])','',variable)
				cfg[variable] = value
				#print variable
	finally:
		f.close()
	return
