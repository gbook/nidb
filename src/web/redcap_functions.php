<?
 // ------------------------------------------------------------------------------
 // NiDB redcap_functions.php
 // Copyright (C) 2004 - 2026
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

	if (!defined("LEGIT_REQUEST")) die ("This page cannot be called directly.");

	/* ------------------------------------------------------------------------------
	   REDCap API access layer.

	   All REDCap API traffic goes through RedCapAPIRequest(). Callers never build
	   their own curl handle, so timeouts, TLS verification, and error handling stay
	   consistent in one place.

	   The REDCap API is a single endpoint: everything is an HTTP POST of
	   url-encoded fields, where 'content' selects the operation and 'token'
	   identifies both the user and the REDCap project. There is no separate login
	   step -- the token *is* the credential -- so "authentication" here means
	   loading the correct token for a remote import and proving it works.

	   Credentials live in remote_imports.remote_url / remote_imports.remote_token
	   for rows with remote_type = 'redcap'.
	   ------------------------------------------------------------------------------ */

	define("REDCAP_CONNECT_TIMEOUT", 10);   /* seconds to establish the connection */
	define("REDCAP_TIMEOUT", 60);           /* seconds for the whole request */


	/* -------------------------------------------- */
	/* ------- RedCapGetCredentials --------------- */
	/* -------------------------------------------- */
	/* Load the REDCap API URL and token for a remote import.

	   $projectid scopes the lookup to the caller's project so an importid from
	   another project cannot be read. Returns an array with 'success' plus
	   'url'/'token'/'importname' on success, or 'message' on failure. */
	function RedCapGetCredentials($importid, $projectid) {
		$importid = (int)$importid;
		$projectid = (int)$projectid;

		if (($importid < 1) || ($projectid < 1))
			return array('success' => false, 'message' => 'Invalid import or project ID');

		$sql = "select import_name, remote_type, remote_url, remote_token, redcap_subjectid_field from remote_imports where remoteimport_id = ? and project_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sql);
		mysqli_stmt_bind_param($stmt, 'ii', $importid, $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sql, array($importid, $projectid));
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);

		if (!$row)
			return array('success' => false, 'message' => 'Remote import not found in this project');

		if ($row['remote_type'] != 'redcap')
			return array('success' => false, 'message' => 'This remote import is not a REDCap import');

		$url = trim($row['remote_url'] ?? '');
		$token = trim($row['remote_token'] ?? '');

		if ($url == '')
			return array('success' => false, 'message' => 'No REDCap API URL is configured for this import');
		if ($token == '')
			return array('success' => false, 'message' => 'No REDCap API token is configured for this import');

		return array(
			'success'      => true,
			'url'          => $url,
			'token'        => $token,
			'importname'   => $row['import_name'] ?? '',
			'subjectfield' => trim($row['redcap_subjectid_field'] ?? '')
		);
	}


	/* -------------------------------------------- */
	/* ------- RedCapValidateURL ------------------ */
	/* -------------------------------------------- */
	/* Sanity-check a REDCap API URL before we try to use it. Returns '' if the URL
	   looks usable, otherwise a human-readable reason. */
	function RedCapValidateURL($url) {
		$url = trim($url ?? '');

		if ($url == '')
			return 'The REDCap API URL is blank';

		$parts = parse_url($url);
		if (($parts === false) || !isset($parts['scheme']) || !isset($parts['host']))
			return 'The REDCap API URL is not a valid URL';

		$scheme = strtolower($parts['scheme']);
		if (($scheme != 'http') && ($scheme != 'https'))
			return 'The REDCap API URL must begin with http:// or https://';

		return '';
	}


	/* -------------------------------------------- */
	/* ------- RedCapAPIRequest ------------------- */
	/* -------------------------------------------- */
	/* Perform a single REDCap API call.

	   $url    - the REDCap API endpoint (eg https://redcap.example.edu/api/)
	   $token  - the REDCap API token for the project
	   $params - additional POST fields; 'content' is required by REDCap
	   $decode - true to json_decode the response body

	   Returns an array:
	     success    - bool, true only on HTTP 2xx with no transport/API error
	     message    - human-readable error, '' on success
	     httpcode   - HTTP status code (0 if the request never completed)
	     raw        - the raw response body
	     data       - decoded body when $decode is true and decoding succeeded

	   REDCap reports API-level problems (bad token, missing rights) as HTTP 400/403
	   with a JSON body of {"error":"..."}, so both the status code and the body are
	   inspected. */
	function RedCapAPIRequest($url, $token, $params = array(), $decode = true) {
		$out = array('success' => false, 'message' => '', 'httpcode' => 0, 'raw' => '', 'data' => null);

		$urlerror = RedCapValidateURL($url);
		if ($urlerror != '') {
			$out['message'] = $urlerror;
			return $out;
		}
		if (trim($token ?? '') == '') {
			$out['message'] = 'The REDCap API token is blank';
			return $out;
		}

		/* REDCap requires token/format/returnFormat on every call; callers supply
		   'content' and any operation-specific fields. Caller values win, except
		   the token, which is always ours. */
		$data = array_merge(
			array('format' => 'json', 'returnFormat' => 'json'),
			$params,
			array('token' => $token)
		);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, trim($url));
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, REDCAP_CONNECT_TIMEOUT);
		curl_setopt($ch, CURLOPT_TIMEOUT, REDCAP_TIMEOUT);
		/* verify TLS: the token is a bearer credential and must not be sent to an
		   unverified host. A site using a self-signed REDCap certificate should
		   install the CA rather than disabling verification here. */
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		/* do not follow redirects: a redirect would re-POST the token to whatever
		   host the redirect names */
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

		$response = curl_exec($ch);
		$out['httpcode'] = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if ($response === false) {
			$out['message'] = 'Could not reach the REDCap server: ' . curl_error($ch);
			curl_close($ch);
			return $out;
		}
		curl_close($ch);

		$out['raw'] = $response;

		/* pull an API error message out of the body if REDCap sent one */
		$apierror = '';
		$decoded = json_decode($response, true);
		if (is_array($decoded) && isset($decoded['error']))
			$apierror = trim($decoded['error']);

		if (($out['httpcode'] < 200) || ($out['httpcode'] > 299)) {
			if ($apierror != '')
				$out['message'] = 'REDCap returned an error: ' . $apierror;
			else if ($out['httpcode'] == 0)
				$out['message'] = 'No response from the REDCap server';
			else
				$out['message'] = 'REDCap returned HTTP ' . $out['httpcode'];
			return $out;
		}

		if ($apierror != '') {
			$out['message'] = 'REDCap returned an error: ' . $apierror;
			return $out;
		}

		if ($decode) {
			if ($decoded === null) {
				$out['message'] = 'REDCap returned a response that could not be parsed as JSON';
				return $out;
			}
			$out['data'] = $decoded;
		}

		$out['success'] = true;
		return $out;
	}


	/* -------------------------------------------- */
	/* ------- RedCapGetVersion ------------------- */
	/* -------------------------------------------- */
	/* Return the REDCap server version. The version export is plain text, not
	   JSON, so decoding is skipped. */
	function RedCapGetVersion($url, $token) {
		$res = RedCapAPIRequest($url, $token, array('content' => 'version'), false);
		if (!$res['success'])
			return $res;

		$res['version'] = trim($res['raw']);
		return $res;
	}


	/* -------------------------------------------- */
	/* ------- RedCapGetProjectInfo --------------- */
	/* -------------------------------------------- */
	/* Export the REDCap project attributes (title, ID, longitudinal flag, ...).
	   Requires the token to have API Export rights. */
	function RedCapGetProjectInfo($url, $token) {
		$res = RedCapAPIRequest($url, $token, array('content' => 'project'), true);
		if (!$res['success'])
			return $res;

		if (!is_array($res['data'])) {
			$res['success'] = false;
			$res['message'] = 'REDCap did not return project information';
		}
		return $res;
	}


	/* -------------------------------------------- */
	/* ------- RedCapTestConnection --------------- */
	/* -------------------------------------------- */
	/* Verify that a URL + token pair can actually talk to REDCap.

	   Exporting the project record is the useful test: it proves the endpoint is
	   a REDCap API, that the token is valid, and that it carries API Export
	   rights -- which is what every later import call will need.

	   Returns an array with 'success', 'message', and on success an 'info' array
	   of label => value pairs describing what was reached. */
	function RedCapTestConnection($url, $token) {
		$res = RedCapGetProjectInfo($url, $token);
		if (!$res['success']) {
			return array(
				'success'  => false,
				'message'  => $res['message'],
				'httpcode' => $res['httpcode'],
				'info'     => array()
			);
		}

		$p = $res['data'];
		$info = array();

		if (isset($p['project_title']))
			$info['REDCap project'] = $p['project_title'];
		if (isset($p['project_id']))
			$info['REDCap project ID'] = $p['project_id'];
		if (isset($p['is_longitudinal']))
			$info['Longitudinal'] = ($p['is_longitudinal'] ? 'Yes' : 'No');
		if (isset($p['has_repeating_instruments_or_events']))
			$info['Repeating instruments/events'] = ($p['has_repeating_instruments_or_events'] ? 'Yes' : 'No');

		/* the version is a nice-to-have; a token without version rights should not
		   fail an otherwise working connection */
		$ver = RedCapGetVersion($url, $token);
		if ($ver['success'] && ($ver['version'] != ''))
			$info['REDCap version'] = $ver['version'];

		return array(
			'success'  => true,
			'message'  => 'Successfully connected to REDCap.',
			'httpcode' => $res['httpcode'],
			'info'     => $info
		);
	}


	/* ------------------------------------------------------------------------------
	   Structure discovery.

	   The mapping UI needs the real shape of the remote REDCap project: which
	   arms/events exist, which forms live in which event, and which fields live on
	   each form. Each of these is a separate 'content' export.

	   Arms, events, and the form/event mapping only exist in longitudinal projects.
	   REDCap returns an error for them on a classic project, so RedCapGetStructure
	   only requests them when project info says the project is longitudinal --
	   which is more reliable than pattern-matching the error text.
	   ------------------------------------------------------------------------------ */


	/* -------------------------------------------- */
	/* ------- RedCapGetArms ---------------------- */
	/* -------------------------------------------- */
	/* Longitudinal only. Returns rows of arm_num / name. */
	function RedCapGetArms($url, $token) {
		return RedCapAPIRequest($url, $token, array('content' => 'arm'), true);
	}


	/* -------------------------------------------- */
	/* ------- RedCapGetEvents -------------------- */
	/* -------------------------------------------- */
	/* Longitudinal only. Returns rows of unique_event_name / event_name / arm_num.
	   arm_num on each event is how the arm is derived -- there is no separate arm
	   column in the mapping table. */
	function RedCapGetEvents($url, $token) {
		return RedCapAPIRequest($url, $token, array('content' => 'event'), true);
	}


	/* -------------------------------------------- */
	/* ------- RedCapGetFormEventMapping ---------- */
	/* -------------------------------------------- */
	/* Longitudinal only. Returns rows of arm_num / unique_event_name / form, ie
	   which instruments are enabled for which event. */
	function RedCapGetFormEventMapping($url, $token) {
		return RedCapAPIRequest($url, $token, array('content' => 'formEventMapping'), true);
	}


	/* -------------------------------------------- */
	/* ------- RedCapGetInstruments --------------- */
	/* -------------------------------------------- */
	/* All project types. Returns rows of instrument_name / instrument_label. */
	function RedCapGetInstruments($url, $token) {
		return RedCapAPIRequest($url, $token, array('content' => 'instrument'), true);
	}


	/* -------------------------------------------- */
	/* ------- RedCapGetMetadata ------------------ */
	/* -------------------------------------------- */
	/* The data dictionary: one row per field, with field_name, form_name,
	   field_type, field_label, select_choices_or_calculations, and
	   text_validation_type_or_show_slider_number.

	   $forms optionally restricts the export to specific instruments. */
	function RedCapGetMetadata($url, $token, $forms = array()) {
		$params = array('content' => 'metadata');
		if (!empty($forms) && is_array($forms))
			$params['forms'] = array_values($forms);

		return RedCapAPIRequest($url, $token, $params, true);
	}


	/* -------------------------------------------- */
	/* ------- RedCapStripHTML -------------------- */
	/* -------------------------------------------- */
	/* Reduce a REDCap label to plain text.

	   REDCap field labels, choice labels and instrument labels are rich text, so
	   they routinely contain markup (<b>, <i>, <br>, <div style=...>) and HTML
	   entities (&nbsp;, &amp;). That markup is noise on screen and must not end up
	   in instrument_items.item_notes, instrumentitem_map.string_val, or an
	   instrument name.

	   Order matters: line-breaking tags become spaces first so "A<br>B" does not
	   collapse to "AB"; tags are stripped before entities are decoded, so a label
	   containing a literal &lt;b&gt; keeps its visible angle brackets instead of
	   having them treated as a tag. */
	function RedCapStripHTML($s) {
		$s = (string)($s ?? '');
		if ($s === '')
			return '';

		/* drop script/style bodies entirely -- strip_tags would keep their text */
		$s = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $s);

		/* turn structural breaks into spaces so words do not run together */
		$s = preg_replace('#<\s*(br|/p|/div|/li|/tr|/h[1-6])\b[^>]*>#i', ' ', $s);

		$s = strip_tags($s);
		$s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');

		/* &nbsp; decodes to U+00A0, which is not matched by \s in every build */
		$s = str_replace("\xC2\xA0", ' ', $s);

		/* collapse runs of whitespace (including the newlines REDCap embeds) */
		$s = preg_replace('/\s+/u', ' ', $s);

		return trim($s);
	}


	/* -------------------------------------------- */
	/* ------- RedCapParseChoices ----------------- */
	/* -------------------------------------------- */
	/* Parse a REDCap choice string into an ordered code => label array.

	   REDCap format: "1, Male | 2, Female". The separator is a pipe, and only the
	   FIRST comma of each pair separates the code from the label -- labels may
	   legitimately contain commas.

	   Codes are not required to be numeric or contiguous ("AA, Option A" and
	   "1,2,3,99" are both valid), which is why they are kept as strings. */
	function RedCapParseChoices($choicestring) {
		$choices = array();
		$choicestring = trim($choicestring ?? '');
		if ($choicestring == '')
			return $choices;

		$parts = explode('|', $choicestring);
		foreach ($parts as $part) {
			$part = trim($part);
			if ($part == '')
				continue;

			$pos = strpos($part, ',');
			if ($pos === false) {
				/* malformed pair: treat the whole thing as its own code */
				$choices[$part] = $part;
				continue;
			}

			$code = trim(substr($part, 0, $pos));
			/* choice labels are rich text too, and are carried into
			   instrumentitem_map.string_val */
			$label = RedCapStripHTML(substr($part, $pos + 1));
			if ($code != '')
				$choices[$code] = $label;
		}

		return $choices;
	}


	/* -------------------------------------------- */
	/* ------- RedCapFieldChoices ----------------- */
	/* -------------------------------------------- */
	/* Return the code => label choices for a metadata row, or an empty array for
	   field types that have none.

	   Only dropdown/radio/checkbox store real choices in
	   select_choices_or_calculations -- for calc that column holds the calculation
	   and for slider it holds the slider labels, so neither is parsed. yesno and
	   truefalse have implicit choices that REDCap does not send. */
	function RedCapFieldChoices($field) {
		$type = strtolower(trim($field['field_type'] ?? ''));

		if (($type == 'dropdown') || ($type == 'radio') || ($type == 'checkbox'))
			return RedCapParseChoices($field['select_choices_or_calculations'] ?? '');

		if ($type == 'yesno')
			return array('1' => 'Yes', '0' => 'No');

		if ($type == 'truefalse')
			return array('1' => 'True', '0' => 'False');

		return array();
	}


	/* -------------------------------------------- */
	/* ------- RedCapSuggestNiDBType -------------- */
	/* -------------------------------------------- */
	/* Suggest a NiDB instrument_items.item_type for a REDCap field.

	   For 'text' fields the REDCap field_type says nothing useful -- a date, an
	   integer, and free text are all 'text'. The validation type
	   (text_validation_type_or_show_slider_number) is what actually carries the
	   type, so it is consulted for those.

	   Returns '' where NiDB cannot represent the field without an explicit choice
	   from the user (file) or where the field holds no data (descriptive). */
	function RedCapSuggestNiDBType($fieldtype, $validation) {
		$fieldtype = strtolower(trim($fieldtype ?? ''));
		$validation = strtolower(trim($validation ?? ''));

		switch ($fieldtype) {
			case 'dropdown':
			case 'radio':
			case 'yesno':
			case 'truefalse':
				return 'enum';
			case 'checkbox':
				return 'int';        /* each choice exports independently as 0/1 */
			case 'calc':
				return 'double';
			case 'slider':
				return 'int';
			case 'notes':
			case 'sql':
				return 'string';
			case 'file':
				return '';           /* user must pick image/csv/json */
			case 'descriptive':
				return '';           /* display-only; carries no data */
		}

		/* text (and anything unrecognised): fall back to the validation type.
		   'datetime' must be tested before 'date' since datetime_ymd starts with
		   both. */
		if (strpos($validation, 'datetime') === 0) return 'datetime';
		if (strpos($validation, 'date') === 0)     return 'datetime';
		if (strpos($validation, 'time') === 0)     return 'string';  /* no time-only NiDB type */
		if ($validation == 'integer')              return 'int';
		if (strpos($validation, 'number') === 0)   return 'double';

		return 'string';
	}


	/* -------------------------------------------- */
	/* ------- RedCapExpandField ------------------ */
	/* -------------------------------------------- */
	/* Turn one metadata row into the list of individually mappable values it
	   produces in a record export.

	   Most field types produce exactly one. A checkbox produces one per choice:
	   a 'race' checkbox with choices 1,2,99 exports as race___1, race___2 and
	   race___99, each an independent 0/1 -- so each gets its own mapping row,
	   distinguished by redcap_choice_code.

	   Each returned entry carries:
	     field         - REDCap field_name (goes in redcap_field)
	     choicecode    - checkbox choice code, '' otherwise (redcap_choice_code)
	     exportname    - the column name in the record export
	     form          - REDCap form_name (redcap_form)
	     fieldtype     - REDCap field_type (redcap_datatype)
	     validation    - REDCap validation type (redcap_validation)
	     label         - human-readable label for the UI
	     suggestedtype - suggested NiDB item_type
	     choices       - code => label, for carrying labels into instrumentitem_map
	     mappable      - false for fields that carry no data
	     isfile        - true if importing needs a separate content=file call
	*/
	function RedCapExpandField($field) {
		$out = array();

		$fieldname = trim($field['field_name'] ?? '');
		if ($fieldname == '')
			return $out;

		$form       = trim($field['form_name'] ?? '');
		$type       = strtolower(trim($field['field_type'] ?? ''));
		$validation = trim($field['text_validation_type_or_show_slider_number'] ?? '');
		/* strip markup once, here: this label is shown in the mapping UI and
		   stored as instrument_items.item_notes */
		$label      = RedCapStripHTML($field['field_label'] ?? '');
		$choices    = RedCapFieldChoices($field);

		$base = array(
			'field'      => $fieldname,
			'form'       => $form,
			'fieldtype'  => $type,
			'validation' => $validation,
			'mappable'   => ($type != 'descriptive'),
			'isfile'     => ($type == 'file')
		);

		if ($type == 'checkbox') {
			/* one mappable value per choice */
			foreach ($choices as $code => $choicelabel) {
				$entry = $base;
				$entry['choicecode']    = (string)$code;
				$entry['exportname']    = $fieldname . '___' . $code;
				$entry['label']         = ($label != '') ? "$label: $choicelabel" : $choicelabel;
				$entry['suggestedtype'] = 'int';
				/* a single checkbox choice is a 0/1, so its labels are fixed */
				$entry['choices']       = array('1' => 'Checked', '0' => 'Unchecked');
				$out[] = $entry;
			}

			/* a checkbox with no parseable choices still deserves a row so the UI
			   can show that something is wrong rather than silently dropping it */
			if (empty($choices)) {
				$entry = $base;
				$entry['choicecode']    = '';
				$entry['exportname']    = $fieldname;
				$entry['label']         = $label;
				$entry['suggestedtype'] = '';
				$entry['choices']       = array();
				$entry['mappable']      = false;
				$out[] = $entry;
			}

			return $out;
		}

		$entry = $base;
		$entry['choicecode']    = '';
		$entry['exportname']    = $fieldname;
		$entry['label']         = $label;
		$entry['suggestedtype'] = RedCapSuggestNiDBType($type, $validation);
		$entry['choices']       = $choices;
		$out[] = $entry;

		return $out;
	}


	/* -------------------------------------------- */
	/* ------- RedCapGetStructure ----------------- */
	/* -------------------------------------------- */
	/* One call that returns everything the mapping UI needs.

	   Returns an array with 'success' plus:
	     islongitudinal - bool
	     hasrepeating   - bool
	     projecttitle   - REDCap project title
	     arms           - arm_num => arm name          (empty for classic)
	     events         - unique_event_name => array(label, armnum)  (empty for classic)
	     formevents     - unique_event_name => list of form names     (empty for classic)
	     instruments    - form_name => form label
	     fields         - form_name => list of RedCapExpandField entries
	     warnings       - non-fatal problems worth showing the user
	*/
	function RedCapGetStructure($url, $token) {
		$out = array(
			'success'        => false,
			'message'        => '',
			'islongitudinal' => false,
			'hasrepeating'   => false,
			'projecttitle'   => '',
			'recordidfield'  => '',
			'arms'           => array(),
			'events'         => array(),
			'formevents'     => array(),
			'instruments'    => array(),
			'fields'         => array(),
			'warnings'       => array()
		);

		/* project info first: it tells us whether to ask for arms/events at all */
		$proj = RedCapGetProjectInfo($url, $token);
		if (!$proj['success']) {
			$out['message'] = $proj['message'];
			return $out;
		}
		$p = $proj['data'];
		$out['projecttitle']   = $p['project_title'] ?? '';
		$out['islongitudinal'] = !empty($p['is_longitudinal']);
		$out['hasrepeating']   = !empty($p['has_repeating_instruments_or_events']);

		/* instruments */
		$inst = RedCapGetInstruments($url, $token);
		if (!$inst['success']) {
			$out['message'] = 'Could not read the instrument list: ' . $inst['message'];
			return $out;
		}
		if (is_array($inst['data'])) {
			foreach ($inst['data'] as $row) {
				$name = trim($row['instrument_name'] ?? '');
				if ($name != '') {
					/* the form label becomes the default NiDB instrument name, so
					   it must be plain text before it can reach the database */
					$label = RedCapStripHTML($row['instrument_label'] ?? '');
					$out['instruments'][$name] = ($label !== '') ? $label : $name;
				}
			}
		}

		/* arms / events / form-event mapping: longitudinal projects only */
		if ($out['islongitudinal']) {
			$arms = RedCapGetArms($url, $token);
			if ($arms['success'] && is_array($arms['data'])) {
				foreach ($arms['data'] as $row) {
					$num = (string)($row['arm_num'] ?? '');
					if ($num != '')
						$out['arms'][$num] = trim($row['name'] ?? $num);
				}
			}
			else
				$out['warnings'][] = 'Could not read the arm list: ' . $arms['message'];

			$events = RedCapGetEvents($url, $token);
			if ($events['success'] && is_array($events['data'])) {
				foreach ($events['data'] as $row) {
					$uen = trim($row['unique_event_name'] ?? '');
					if ($uen == '')
						continue;
					$out['events'][$uen] = array(
						'label'  => trim($row['event_name'] ?? $uen),
						'armnum' => (string)($row['arm_num'] ?? '')
					);
				}
			}
			else
				$out['warnings'][] = 'Could not read the event list: ' . $events['message'];

			$fem = RedCapGetFormEventMapping($url, $token);
			if ($fem['success'] && is_array($fem['data'])) {
				foreach ($fem['data'] as $row) {
					$uen = trim($row['unique_event_name'] ?? '');
					$form = trim($row['form'] ?? '');
					if (($uen == '') || ($form == ''))
						continue;
					if (!isset($out['formevents'][$uen]))
						$out['formevents'][$uen] = array();
					$out['formevents'][$uen][] = $form;
				}
			}
			else
				$out['warnings'][] = 'Could not read the form/event mapping: ' . $fem['message'];
		}

		/* fields, grouped by form */
		$meta = RedCapGetMetadata($url, $token);
		if (!$meta['success']) {
			$out['message'] = 'Could not read the data dictionary: ' . $meta['message'];
			return $out;
		}
		if (is_array($meta['data'])) {
			/* REDCap's record ID field is by definition the first field of the
			   first instrument, so it is the first row of the data dictionary. It
			   appears under only that one form, even though a record export
			   returns it on every row. */
			if (isset($meta['data'][0]['field_name']))
				$out['recordidfield'] = trim($meta['data'][0]['field_name']);

			foreach ($meta['data'] as $row) {
				$entries = RedCapExpandField($row);
				foreach ($entries as $entry) {
					$form = ($entry['form'] != '') ? $entry['form'] : '(none)';
					if (!isset($out['fields'][$form]))
						$out['fields'][$form] = array();
					$out['fields'][$form][] = $entry;
				}
			}
		}

		if (empty($out['fields']))
			$out['warnings'][] = 'The REDCap project returned no fields.';

		$out['success'] = true;
		return $out;
	}


	/* -------------------------------------------- */
	/* ------- RedCapExportRecords ---------------- */
	/* -------------------------------------------- */
	/* Export record data as a flat table (one row per record/event/repeat instance).

	   $forms/$events optionally restrict the export. 'raw' returns coded values
	   (1, 2), 'label' returns the choice labels.

	   exportSurveyFields is on because it adds <form_name>_timestamp for
	   instruments enabled as surveys -- that timestamp is the only automatic
	   per-instance date REDCap provides, and the importer uses it as a date
	   fallback.

	   exportCheckboxLabel stays off so checkbox columns are 0/1 regardless of
	   rawOrLabel, which keeps field___code handling uniform. */
	function RedCapExportRecords($url, $token, $forms = array(), $events = array(), $rawOrLabel = 'raw', $fields = array()) {
		$params = array(
			'content'                => 'record',
			'type'                   => 'flat',
			'rawOrLabel'             => ($rawOrLabel === 'label') ? 'label' : 'raw',
			'rawOrLabelHeaders'      => 'raw',
			'exportCheckboxLabel'    => 'false',
			'exportSurveyFields'     => 'true',
			'exportDataAccessGroups' => 'false'
		);
		if (!empty($forms) && is_array($forms))
			$params['forms'] = array_values($forms);
		if (!empty($events) && is_array($events))
			$params['events'] = array_values($events);
		/* Individual fields to include on top of $forms. REDCap returns the union
		   of forms and fields, so this is how a field living on an unmapped form
		   (typically the subject ID) is guaranteed to be present. */
		if (!empty($fields) && is_array($fields))
			$params['fields'] = array_values($fields);

		$res = RedCapAPIRequest($url, $token, $params, true);
		if ($res['success'] && !is_array($res['data'])) {
			$res['success'] = false;
			$res['message'] = 'REDCap did not return a record list';
		}
		return $res;
	}


	/* -------------------------------------------- */
	/* ------- RedCapNormalizeDateTime ------------ */
	/* -------------------------------------------- */
	/* Convert a REDCap date/datetime string into a MySQL datetime, or '' if it
	   cannot be parsed. REDCap stores dates in the validation's display order but
	   exports them Y-M-D, so strtotime handles the common cases; the guard is here
	   because a text field mapped to a NiDB datetime can contain anything. */
	function RedCapNormalizeDateTime($v) {
		$v = trim($v ?? '');
		if ($v == '')
			return '';

		$ts = strtotime($v);
		if ($ts === false)
			return '';

		return date('Y-m-d H:i:s', $ts);
	}


	/* -------------------------------------------- */
	/* ------- RedCapCoerceValue ------------------ */
	/* -------------------------------------------- */
	/* Coerce a REDCap export value into something storable for the mapped NiDB
	   item_type. Returns array('ok' => bool, 'value' => string, 'message' => '').

	   'ok' is false when the value cannot be represented (eg non-numeric text
	   mapped to an int item) so the caller can log it rather than silently
	   storing a zero. */
	function RedCapCoerceValue($v, $itemType) {
		$v = trim($v ?? '');

		switch ($itemType) {
			case 'int':
				if (!is_numeric($v))
					return array('ok' => false, 'value' => '', 'message' => "value '$v' is not numeric");
				return array('ok' => true, 'value' => (string)(int)$v, 'message' => '');

			case 'double':
				if (!is_numeric($v))
					return array('ok' => false, 'value' => '', 'message' => "value '$v' is not numeric");
				return array('ok' => true, 'value' => (string)(double)$v, 'message' => '');

			case 'datetime':
				$d = RedCapNormalizeDateTime($v);
				if ($d == '')
					return array('ok' => false, 'value' => '', 'message' => "value '$v' is not a recognisable date");
				return array('ok' => true, 'value' => $d, 'message' => '');

			case 'enum':
			case 'string':
			default:
				/* enum values are stored as the raw code; the label lives in
				   instrumentitem_map */
				return array('ok' => true, 'value' => $v, 'message' => '');
		}
	}
?>
