# Analysis API (`analysisapi.php`)

`analysisapi.php` is an HTTP alternative to the `nidb cluster -u <submodule>` command. It lets a pipeline job script running on a compute node check in, insert results, and update the analysis without the `nidb` binary or direct database access on that node. Only `curl` and network access to the NiDB web server are needed.

## Job script setup

When the pipeline module writes a job script (`modulePipeline.cpp`), it generates a fresh random token for the analysis, stores its SHA-256 hash in `analysis.analysis_apitoken`, and adds the following to the top of the script:

```bash
NIDB_ANALYSISID=1234; export NIDB_ANALYSISID;
NIDB_APITOKEN=<64 hex chars>; export NIDB_APITOKEN;
NIDB_APIURL=<siteurl>/analysisapi.php; export NIDB_APIURL;

nidbapi() {
    local response
    response=$(curl -sS --connect-timeout 30 --max-time 300 --retry 3 "$NIDB_APIURL" -d "analysisid=$NIDB_ANALYSISID" --data-urlencode "token=$NIDB_APITOKEN" --data-urlencode "hostname=$(hostname)" "$@")
    echo "$response"
    echo "$response" | grep -q '"success":true'
}
export -f nidbapi
```

- `nidbapi` fills in `analysisid` and `token`. Any other arguments are passed to `curl`. It prints the JSON response and returns non-zero if the call failed.
- `export -f` makes `nidbapi` available to child **bash** scripts, such as a pipeline step or result script that runs a separate `.sh` file. Scripts run with `/bin/sh` (dash) don't inherit it.
- A token can only modify the analysis it was issued for. Writing a new job script for the analysis (rerun or supplement) generates a new token and invalidates the old one.
- The token is cleared when the job checks in with a complete status (`complete`, `completererun`, or `completesupplement`, through either the API or `nidb cluster -u pipelinecheckin`). After that, any API call with the token fails with `Authentication failed.`, so the final check-in must be the job's last API call.

## Request and response format

- Parameters are POSTed. GET also works but puts the token in web server logs.
- Use `--data-urlencode` for values that may contain spaces or special characters. Use `-d` for simple values.
- The response is JSON: `{"success": true|false, "message": "..."}`. HTTP status is `200` on success, `400` for bad parameters, `401` for authentication failure, and `500` for a server error.

## Actions

| Action | Parameters | `nidb cluster` equivalent |
|--------|------------|---------------------------|
| `checkin` | `status`, [`message`], [`step`], [`command`], [`hostname`] | `-u pipelinecheckin` |
| `resultinsert` | `desc`, one of `text`/`number`/`file`/`image`, [`unit`] | `-u resultinsert` |
| `updateanalysis` | `numfiles`, `disksize` (bytes) | `-u updateanalysis` |
| `setcomplete` | `iscomplete` (`0` or `1`) | `-u checkcompleteanalysis` |

### checkin

Updates the analysis status, status message, status time, and hostname, and writes a row to `analysis_log`.

| `status` | Effect |
|----------|--------|
| `started` | Also sets `analysis_clusterstartdate` |
| `startedrerun`, `startedsupplement` | Start of a results rerun or supplement run |
| `processing` | Progress update (see step numbers below) |
| `error`, `notcompleted` | Failure states |
| `complete` | Also sets `analysis_clusterenddate` |
| `completererun` | Sets status `complete`, clears `analysis_rerunresults` |
| `completesupplement` | Sets status `complete`, clears `analysis_rerunresults` and `analysis_runsupplement` |

The step number comes from `step` if given. Otherwise it's parsed from a message like `processing step 2 of 5` or `processing supplement step 2 of 5`. For a `processing` check-in with no step number, the message text selects the log event: `processing result script`, `updating analysis files`, or `checking for completed files`. Any other message logs `cluster_checkinStep`. `hostname` is sent automatically by the job script's `nidbapi` function (the output of `hostname` on the compute node); if it's missing, it falls back to the request's IP address.

```bash
nidbapi -d action=checkin -d status=started --data-urlencode "message=Cluster processing started"
nidbapi -d action=checkin -d status=processing --data-urlencode "message=processing step 2 of 5"
nidbapi -d action=checkin -d status=processing -d step=3 --data-urlencode "message=Running recon-all" --data-urlencode "command=recon-all -s sub01 -all"
nidbapi -d action=checkin -d status=error --data-urlencode "message=recon-all failed"
nidbapi -d action=checkin -d status=complete --data-urlencode "message=Cluster processing complete"
```

### resultinsert

See [User guide: inserting results](#user-guide-inserting-results) below. `desc` is required and exactly one of `text`, `number`, `file`, or `image` must be given per call. Result names and units are created automatically the first time they're used. If the same result is inserted again, its `result_count` is incremented.

### updateanalysis

Sets `analysis_numfiles` and `analysis_disksize`. Unlike `nidb cluster`, the server doesn't scan the directory, so the job script measures it:

```bash
nidbapi -d action=updateanalysis -d "numfiles=$(find "$analysispath" -type f | wc -l)" -d "disksize=$(du -sb "$analysispath" | cut -f1)"
```

### setcomplete

Sets `analysis_iscomplete`. Unlike `nidb cluster`, the server doesn't check for the pipeline's completion files (`pipeline_completefiles`), so the job script checks for them:

```bash
iscomplete=1
for f in stats/aseg.stats mri/aparc+aseg.mgz; do
    [ -e "$analysispath/$f" ] || { iscomplete=0; break; }
done
nidbapi -d action=setcomplete -d "iscomplete=$iscomplete"
```

### Error responses

```
{"success":false,"message":"Invalid status [running]. Must be one of: started, startedrerun, ..."}
{"success":false,"message":"Description of the result is blank. You must include a description/label (desc) of this result."}
{"success":false,"message":"More than one of text, number, file, or image was specified. Only one type of result can be specified at a time."}
{"success":false,"message":"Authentication failed."}
```

`Authentication failed.` covers a wrong token, a token from an older job script, a token from a job that has already finished, and an unknown analysis ID.

## User guide: inserting results

Pipeline steps and result scripts can save results to NiDB with the `nidbapi` command, which is already available inside every NiDB pipeline job. Each call saves one result with a description (`desc`) and one value:

| Result type | Option | Example value |
|-------------|--------|---------------|
| Number | `number` (optional `unit`) | `4123.5` |
| Text | `text` | `pass` |
| File | `file` | path to a file in the analysis directory |
| Image | `image` | path to an image in the analysis directory |

```bash
# a number, with or without a unit
nidbapi -d action=resultinsert --data-urlencode "desc=Left hippocampus volume" -d number=4123.5 --data-urlencode "unit=mm^3"
nidbapi -d action=resultinsert --data-urlencode "desc=Mean FD" -d number=0.182

# a number calculated by the script
vol=$(fslstats T1_brain.nii.gz -V | awk '{print $2}')
nidbapi -d action=resultinsert --data-urlencode "desc=Brain volume" -d "number=$vol" --data-urlencode "unit=mm^3"

# text
nidbapi -d action=resultinsert --data-urlencode "desc=QC rating" --data-urlencode "text=pass"

# a file or image produced by the analysis
nidbapi -d action=resultinsert --data-urlencode "desc=aseg stats" --data-urlencode "file=$(pwd)/stats/aseg.stats"
nidbapi -d action=resultinsert --data-urlencode "desc=Registration QC" --data-urlencode "image=$(pwd)/qc/reg_overlay.png"

# every row of a name,value,unit CSV file
while IFS=, read -r name value unit; do
    nidbapi -d action=resultinsert --data-urlencode "desc=$name" -d "number=$value" --data-urlencode "unit=$unit"
done < results.csv
```

Tips:

- Always use `--data-urlencode` for `desc`, `text`, `unit`, `file`, and `image` so that spaces and symbols are sent correctly.
- `file` and `image` store the path only, not the file itself. Keep the file in the analysis directory and don't move or delete it afterwards.
- `nidbapi` prints a response such as `{"success":true,"message":"Inserted result [QC rating] for analysis [1234]"}`. If `success` is `false`, the message explains why.
- If your result script runs as a separate file, start it with `#!/bin/bash` so it can use `nidbapi`.
