<?
 // ------------------------------------------------------------------------------
 // NiDB nidbapi.php
 // Copyright (C) 2004 - 2018
 // Gregory A Book <gregory.book@hhchealth.org> <gbook@gbook.org>
 // Olin Neuropsychiatry Research Center, Hartford Hospital
 // ------------------------------------------------------------------------------
 // GPLv3 License:

 // This program is free software: you can redistribute it and/or modify
 // it under the terms of the GNU General Public License as published by
 // the Free Software Foundation, either version 3 of the License, or
 // (at your option) any later version.

 // This program is distributed in the hope that it will be useful,
 // but WITHOUT ANY WARRANTY; without even the implied warranty of
 // MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 // GNU General Public License for more details.

 // You should have received a copy of the GNU General Public License
 // along with this program.  If not, see <http://www.gnu.org/licenses/>.
 // ------------------------------------------------------------------------------


	namespace NIDB;
	
	/* -------------------------------------------- */
	/* ------- GetModalityList -------------------- */
	/* -------------------------------------------- */
	function GetModalityList($withdesc = false) {
		$sqlstring = "select * from modalities order by mod_desc";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$modalities[] = $row['mod_code'];
			$descriptions[] = $row['mod_code'];
		}
			
		if ($withdesc) {
			return array($modalities,$descriptions);
		}
		else {
			return $modalities;
		}
	}

	
	/* -------------------------------------------- */
	/* ------- CreateUID -------------------------- */
	/* -------------------------------------------- */
	function CreateUID($prefix, $numletters=3) {
		mt_srand();
		
		$C1 = mt_rand(0,9);
		$C2 = mt_rand(0,9);
		$C3 = mt_rand(0,9);
		$C4 = mt_rand(0,9);
		
		$badarray = array('fuck','shit','piss','tits','dick','cunt','twat','jism','jizz','arse','damn','fart','hell','wang','wank','gook','kike','kyke','spic','arse','dyke','cock','muff','pusy','butt','crap','poop','slut','dumb','snot','boob','dead','anus','clit','homo','poon','tard','kunt','tity','tit','ass','dic','dik','fuk');
		$safe = false;
		
		while (!$safe) {
			# ASCII codes 65 through 90 are upper case letters
			$C5 = chr(mt_rand(65,90));
			$C6 = chr(mt_rand(65,90));
			$C7 = chr(mt_rand(65,90));
			if ($numletters == 4) {
				$C8 = chr(mt_rand(65,90));
			}
			
			$str = "$C5$C6$C7$C8";
			if (!in_array(strtolower($str),$badarray)) {
				$safe = true;
			}
		}
		
		$newID = "$prefix$C1$C2$C3$C4$C5$C6$C7$C8";
		return $newID;
	}

