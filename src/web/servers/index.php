<?
 // ------------------------------------------------------------------------------
 // NiDB index.php
 // Copyright (C) 2004 - 2022
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

	define("LEGIT_REQUEST", true);
	
	session_start();


?>

<html>
	<head>
		<link rel="icon" type="image/png" href="../images/squirrel.png">
		<title><?=$_SERVER['HTTP_HOST']?> - NiDB</title>
		<script src="https://cdn.jsdelivr.net/npm/mermaid@10.9.0/dist/mermaid.min.js"></script>
	</head>

<body>
    <h1><b>Olin Servers</b></h1>
<tbody>
<?
    
   require "functions.php";
//   require "includes_php.php";
   require "includes_html.php";

   $action = GetVariable("action");
   $selected_server_id = GetVariable("selected_server_id");
   $serverid = GetVariable("serverid");
   $sserverid = GetVariable("sserverid");
   $serviceid = GetVariable("serviceid");

switch($action)
    {
    case 'listservers':
            listservers();
            break;
    case 'serverinfo':
            showserver($serverid);
	    break;
    case 'addserver':
	    addserver();
	    listservers();
	    break;
    case 'addservice':
	    addservice($serverid);
            break;
    case 'updateserverinfo':
	    updateserver();
	    showserver($serverid);
	    break;
    case 'updateserviceinfo':
            updateservice();
            showserver($serverid);
	    break;
    case 'addrelation':
	    addrelation($sserverid,$selected_server_id);
            showserver($sserverid);
	    break;
    case 'deleteserver':
            deleteserver($serverid);
	    listservers ();
            break;
    case 'deleteservice':
	    deleteservice($serviceid);
            showserver($serverid);
            break;
    default:
//	    newforms ();
	    listservers();
            break;
    }


/* ---------------------------------------------- */
/* -----------------connectdb-------------------- */
/* ---------------------------------------------- */
function connectdb()
{


    // Database connection
    $db_host = "localhost";
    $db_username = "root";
    $db_password = "password";
    $db_name = "serversdb";

    // Connect to the database
    $mysqli = new mysqli($db_host, $db_username, $db_password, $db_name);
    // Check connection
    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }
    return $mysqli;
}

/* ---------------------------------------------- */
/* -----------------listservers------------------ */
/* ---------------------------------------------- */

function listservers()
{
    // Database connection
    $mysqli =  connectdb();
    // Fetch existing servers
    $serverQuery = "SELECT * FROM servers";
    $serverResult = $mysqli->query($serverQuery);

?>
    
   <div class="ui segment">
   <div class="column">
	<!---<h2 class="ui header"> List of Servers </h2>-->
		<h4>For details of a server, click on the server name below</h4>
    </div>

   <table class='ui celled compact table'>
   <thead>
   <tr><th>Server Name</th><th>IP Address</th><th>FDQN</th><th>CPUs</th><th>RAM (GB)</th><th></th></tr>
   </thead>
   <tbody>
    <form class="ui small form" action="index.php" method="post">
        <input type="hidden" name="action" value="addserver">
        <input type="hidden" name="serverid" value="<?=$serverid?>">
        <td> <input type="text" name="new_hostname"> </td>
        <td> <input type="text" name="new_ip"> </td>
        <td> <input type="text" name="new_fqdn"> </td>
        <td> <input type="number" name="new_no_cpus"> </td>
        <td> <input type="number" name="new_ram"> </td>
        <td> <button class="ui right floated button" type="submit" name="add_server" >Add Server</button></td>
    </form>
<?
    // Loop through each server
    while ($serverRow = $serverResult->fetch_assoc()) {
	    $serverid = $serverRow['server_id'];
	    $hostname = $serverRow['hostname'];
	    $ip = $serverRow['ip'];
	    $fqdn = $serverRow['fqdn'];
	    $nocpus = $serverRow['no_cpus'];
	    $ram = $serverRow['ram'];
?>
       	<tr>
		<td><a class="item" href="index.php?action=serverinfo&serverid=<?=$serverid?>"><?=$hostname?></a></td>
		<td><?=$ip?></td>
		<td><?=$fqdn?></td>
		<td><?=$nocpus?></td>
		<td><?=$ram?></td>
		<td style="width:75px;text-align:center" title="Delete This Server"><a href="index.php?action=deleteserver&serverid=<?=$serverRow['server_id']?>" onclick="return confirm('Are you sure to delete <?=$serverRow['hostname']?> server?\nThis will also delete all the services related to <?=$serverRow['hostname']?>')" class="redlinkbutton" style="font-size: large">X</a></td>
      	</tr>
<?}?>
    </tbody>
    </table>
   </div><br><br>
<?
    // Close the database connection
    $mysqli->close();
}


/* ---------------------------------------------- */
/* -----------------showserver------------------- */
/* ---------------------------------------------- */

function showserver($serverid)
{


    // Database connection
    $mysqli =  connectdb();
    // Fetch existing servers
    $serverQuery = "SELECT * FROM servers where server_id=$serverid";
    $serverResult = $mysqli->query($serverQuery);
?>
    <form>
                <input type="hidden" name="action" value="default">
                <button class="ui right floated huge button"  type="submit"> Show All Servers </button>
    </form><br><br>

<?

    // Loop through each server
    $server_num = 0;
    while ($serverRow = $serverResult->fetch_assoc()) {
	    $server_num = $server_num +1;
?>
	 <h2>Server: <?=$serverRow['hostname']?></h2>
	<table class="ui celled table">
	<td style="vertical-align:top">
	<h3><?=$serverRow['hostname']?> Details</h3>
	<div class="ui raised compact segment">
	<form class='ui form' action='index.php' method='post'>
	<input type='hidden' name='action' value='updateserverinfo'>
	<input type='hidden' name='serverid' value='<?=$serverRow['server_id']?>'>
	<input type='hidden' name='server_id' value='<?=$serverRow['server_id']?>'>
	<div class="inline fields">
	Hostname:<input type='text' name='hostname' value='<?=$serverRow['hostname']?>'>
	</div>
	<div class="inline fields">
	IP Addr: <input type='text' name='ip' value='<?=$serverRow['ip']?>'>
	</div>
        <div class="inline fields">
	FQDN: <input type='text' name='fqdn' value='<?=$serverRow['fqdn']?>'>
	</div>
        <div class="inline fields">
	No. of CPUs: <input type='number' name='no_cpus' value='<?=$serverRow['no_cpus']?>'>
	</div>
        <div class="inline fields">
	RAM (GB): <input type='number' name='ram' value='<?=$serverRow['ram']?>'>
	</div>
	<button  class='ui right floated button' type='submit' name='update_server' onclick='this.form.submit()'>Update Server</button>
	</form>
	</div>
	</td>
	<td style="vertical-align:top">



<?
        // Fetch corresponding services for this server
        $servicesQuery = "SELECT * FROM services WHERE server_id = '{$serverRow['server_id']}'";
        $servicesResult = $mysqli->query($servicesQuery);

	// Display services in a table
?>
	<table class='ui celled compact table'>
	<h3>Services provided by <?=$serverRow['hostname']?></h3>
	<thead>
	<tr><th>Service</th><th>Licenses</th><th>Websites</th><th>NFS Share</th><th></th><th></th></tr>
	</thead>
	<tbody>
	<tr>
            <form class="ui form" action="index.php" method="post">
                <input type="hidden" name="action" value="addservice">
                <input type="hidden" name="serverid" value="<?=$serverRow['server_id']?>">
                <td> <input type="text" name="new_service"> </td>
                <td> <input type="text" name="new_licenses"> </td>
		<td> <input type="text" name="new_websites"> </td>
		<td> <input type="text" name="new_nfsshare"> </td><br>
                <td title="Add Service"> <button class="ui center floated grey button" type="submit" name="add_service" >Add Service</button></td>
            </form>
        </tr>
<?
	while ($servicesRow = $servicesResult->fetch_assoc()) {
?>
	    <form class='ui form' action='index.php' method='post'>
	    <input type='hidden' name='action' value='updateserviceinfo'>
	    <input type='hidden' name='serverid' value='<?=$serverRow['server_id']?>'>
	    <input type='hidden' name='serviceid' value='<?=$servicesRow['service_id']?>'>
            <tr>
	    <td><input type='text' name='service' value='<?=$servicesRow['service']?>'></td>
	    <td><input type='text' name='licenses' value='<?=$servicesRow['licenses']?>'></td>
	    <td><input type='text' name='websites' value='<?=$servicesRow['websites']?>'></td>
	    <td><input type='text' name='nfsshare' value='<?=$servicesRow['nfsshare']?>'></td>
	    <td title="Update This Service" style="width: 55px;"><button  class='ui center floated button' type='submit' name='update_service' onclick='header('refresh: 2')'><i class="sync icon"></i></button></td>
	    <td title="Delete This Service" style="text-align:center"><a href="index.php?action=deleteservice&serviceid=<?=$servicesRow['service_id']?>&serverid=<?=$serverRow['server_id']?>" onclick="return confirm('Are you sure to delete <?=$servicesRow['service']?> service?')" class="redlinkbutton" style="font-size: large">X</a></td>
	    </tr>
	    </form>

<?	}?>
	</tbody>
	</table>
	    </td>
	    </table>
</div>
		<div class="ui two column stackable centered  grid">
		<table class="ui celled table">
		<div class="column">
		<td style="width: 500px; vertical-align:top">
		<h3>Add Services From Other Servers</h3>
		<div class="ui raised compact segment">
		<form  class='ui form' action='' method='post'>
		Select a Server:
		<select name='server_id<?=$server_num?>' onchange='this.form.submit()'>
			    <option value="">Select a Server</option>
                            <?
                            // Fetch existing servers for the dropdown
                            $serverQ = "SELECT * FROM servers WHERE server_id != '{$serverRow['server_id']}'";
			    $serverR = $mysqli->query($serverQ);

			    if ($serverR){
                            while ($serverRw = $serverR->fetch_assoc()) {
                                echo "<option value='{$serverRw['server_id']}'>{$serverRw['hostname']}</option>";
			    } 
			    } else {
				    echo "Error: ".$mysqli->error;
			    }
                            ?>
		</select><br> 
		</form>
<?
		$selected_server_id = $_POST["server_id$server_num"];
		$sserverid = $serverRow['server_id'];    
?>		<form class='ui form' action='index.php' method='post'>
		<input type="hidden" name="action" value="addrelation">
		<input type='hidden' name='sserverid' value='<?=$sserverid?>'>
		<input type='hidden' name='selected_server_id' value='<?=$selected_server_id?>'><?
		if (!empty($selected_server_id) && isset($_POST["server_id$server_num"])){
//			$selected_server_id = $_POST["server_id$server_num"];
//			echo $selected_server_id;
			// Fetch existing services for the Server
			$serviceQ = "SELECT * FROM services WHERE server_id = $selected_server_id";
//			print($serviceQ);
			$serviceR = $mysqli->query($serviceQ);
			if (!$serviceR){
				echo "Error Fetching Services: ".$mysqli->error;
			}
			elseif ($serviceR->num_rows > 0){
				while ($serviceRw = $serviceR->fetch_assoc()) {
					// Fetch existing relations for the Server
		                        $relationQ = "SELECT * FROM server_relationship WHERE server_id = $sserverid and service_id = '{$serviceRw['service_id']}'";
//              		        print($serviceQ);
		                        $relationR = $mysqli->query($relationQ);
		                        if ($relationR->num_rows == 0){

						echo "<input type='checkbox' name='services[]' value='{$serviceRw['service_id']}'>{$serviceRw['service']}"."<br>";
					}
					else {
						echo "<input type='checkbox' name='services[]' value='{$serviceRw['service_id']}' checked>{$serviceRw['service']}"."<br>";
					}
				    }
			} else{
                        	echo "No Services found in the selected Server";
			}
			
			echo "<button  class='ui right floated button' type='submit' name='update_relations'>Update</button>";
		}	
		
?>
		</form>
		</div>
		</div>
		</td style=""width: 700px; vertical-align:top">
		<div class="column">
		<td>
		<h3>Graph of services used from other servers</h3>
<?
//	$servicesResult->free();

	// Fetch corresponding servers, and services for this server to make the graph
	$servicesQuery = "SELECT (select hostname from servers where server_id='{$serverRow['server_id']}') as C_Sv, Ser.service as C_Ser , Sv.hostname as Host_Sv FROM `server_relationship` as Rel JOIN services as Ser on Rel.service_id = Ser.service_id JOIN servers as Sv on Sv.server_id = Ser.server_id WHERE Rel.server_id = '{$serverRow['server_id']}'";
        $servicesResult = $mysqli->query($servicesQuery);
	// Generate Mermaid Graph
	echo "<div class='mermaid' style='margin-left: 20px;'>";
        echo "graph LR;\n"; // Horizontal graph
	//	echo "  host[{$serverRow['hostname']}];\n";
   if($servicesResult->num_rows > 0){
	$serviceno = 0;
	while ($servicesRow = $servicesResult->fetch_assoc()) {
		echo "	Sv_{$serviceno}[{$servicesRow['Host_Sv']}] -- {$servicesRow['C_Ser']} --> host[{$servicesRow['C_Sv']}];\n";
		$serviceno = $serviceno+1;
        } 
   } else {
	   echo "  A[No Service ] -- is yet added --> host[{$serverRow['hostname']}];\n";}

    }

    ?>
	</div>
	</div>
	</td>
	<tr></tr>
	</table><br><br>
<?}?>
    <!-- Script to Reload the page -->
    <script>
    function reloadPage() {
        location.reload();
    }
   </script>

    <?php
    // Handle form submissions

    // Update existing server
    function updateserver(){
 	   // Database connection
	   $mysqli =  connectdb();

	    if (isset($_POST['update_server'])) {
	        $server_id = $_POST['server_id'];
	        $hostname = $_POST['hostname'];
	        $ip = $_POST['ip'];
	        $fqdn = $_POST['fqdn'];
	        $no_cpus = $_POST['no_cpus'];
		$ram = $_POST['ram'];

		// Handling NULL numbers
                if ($no_cpus=='' || trim($no_cpus)=='' ){
                        $no_cpus = 'NULL' ;
                }
                else{
                         $no_cpus = (int)$no_cpus;}

                if ($ram==''){
                        $ram = 'NULL';
                }

//		echo $no_cpus;
		$updateServerQuery = "UPDATE servers SET hostname='$hostname', ip='$ip', fqdn='$fqdn', no_cpus=$no_cpus, ram=$ram WHERE server_id='$server_id'";
//		echo $updateServerQuery;
        	$mysqli->query($updateServerQuery);
	    }
	// Close the database connection
        $mysqli->close();
	}

    // Update existing service
    function  updateservice(){
	   // Database connection
           $mysqli =  connectdb();
	    if (isset($_POST['update_service'])) {
	        $service_id = $_POST['serviceid'];
	        $service = $_POST['service'];
	        $licenses = $_POST['licenses'];
		$websites = $_POST['websites'];
		$nfsshare = $_POST['nfsshare'];

	        $updateServiceQuery = "UPDATE services SET service='$service', licenses='$licenses', websites='$websites', nfsshare='$nfsshare' WHERE service_id='$service_id'";
		$mysqli->query($updateServiceQuery);
	// Close the database connection
	$mysqli->close();
	
	    }
    }

// Update server relationship
    function addrelation($sserverid,$selected_server_id){
	// Database connection
	$mysqli =  connectdb();
	
	
    	if (isset($_POST['update_relations'])) {
	        // Get Selected Server
	//        $selected_server_id = $_POST["server_id$server_num"];
//		echo "<br>"."Server ID: ". $sserverid."<br>";
//		 echo "<br>"."Selected Server ID: ". $selected_server_id."<br>";
	        // Get the Selected Services checkboxes
	        $selected_services = isset($_POST['services']) ? $_POST['services'] : array();
//		    echo "Service ID: ";
//		   print_r( $selected_services);
		

		 // Delete Unchecked Services
                        $relQ = "SELECT * FROM services WHERE server_id = $selected_server_id";
                        $relR = $mysqli->query($relQ);
                        if (!$relR){
                                echo "Error Fetching Service From Relations: ".$mysqli->error;
                        }
                        elseif ($relR->num_rows > 0){
				while ($relRw = $relR->fetch_assoc()) {
					if (!in_array("{$relRw['service_id']}", $selected_services)){
						$deleteServerRelation = "DELETE FROM  server_relationship where service_id='{$relRw[service_id]}' and server_id = $sserverid";
						$del_stmt = $mysqli->query($deleteServerRelation);
					}
				}
			}


		// Insert each checkboxed Services
		foreach ($selected_services as $serviceid_val) {
			// Fetch existing relations for the Server
	                $relationQ = "SELECT * FROM server_relationship WHERE server_id = $sserverid and service_id = $serviceid_val";
//      	        print($serviceQ);
                	$relationR = $mysqli->query($relationQ);
	                if ($relationR->num_rows == 0){
				$updateServerRelation = "INSERT IGNORE into server_relationship (server_id,service_id) VALUES ($sserverid,$serviceid_val)";
				$stmt = $mysqli->query($updateServerRelation);
			}
	        }
	}
//	showserver($sserverid);
        // Close the database connection
        $mysqli->close();
    }

    // Add new server
    function addserver(){
	    // Database connection
           $mysqli =  connectdb();
 	   if (isset($_POST['add_server'])) {
	        $new_hostname = $_POST['new_hostname'];
	        $new_ip = $_POST['new_ip'];
	        $new_fqdn = $_POST['new_fqdn'];
	        $new_no_cpus = $_POST['new_no_cpus'];
		$new_ram = $_POST['new_ram'];
		
		// Handling NULL numbers
		if ($new_no_cpus=='' || trim($new_no_cpus)=='' ){
			$new_no_cpus = 'NULL' ;
		}
		else{
			 $new_no_cpus = (int)$new_no_cpus;}
		
		if ($new_ram==''){
			$new_ram = 'NULL';
		}

		$addServerQuery = "INSERT INTO servers (hostname, ip, fqdn, no_cpus, ram) VALUES ('$new_hostname', '$new_ip', '$new_fqdn', $new_no_cpus, $new_ram)";
//		echo $addServerQuery;
	        if ($mysqli->query($addServerQuery)) {
	            echo "<p>New server added successfully</p>";
	        } else {
	            echo "<p>Error adding new server: " . $mysqli->error . "</p>";
	        }
	   }
//	   showserver($server_id);
	   // Close the database connection
	   $mysqli->close();
    }
    // Add new service
    function addservice($serverid){
           // Database connection
           $mysqli =  connectdb();
	   if (isset($_POST['add_service'])) {
	        $new_service = $_POST['new_service'];
	        $new_licenses = $_POST['new_licenses'];
		$new_websites = $_POST['new_websites'];
		$new_nfsshare =  $_POST['new_nfsshare'];
//	        $server_id = $_POST['server_id'];

	        $addServiceQuery = "INSERT INTO services (server_id, service, licenses, websites, nfsshare) VALUES ('$serverid', '$new_service', '$new_licenses', '$new_websites', '$new_nfsshare')";
        	if ($mysqli->query($addServiceQuery)) {
	            echo "<p>New service added successfully</p>";
	        } else {
	            echo "<p>Error adding new service: " . $mysqli->error . "</p>";
	        }
	   }
	   showserver($serverid);
	   // Close the database connection
           $mysqli->close();
    }
    // Delete a server
    function deleteserver($serverid){

           // Database connection
           $mysqli =  connectdb();

	   $delServerQuery = "DELETE FROM servers WHERE server_id='$serverid'";
	   $delServiceQuery = "DELETE FROM services WHERE server_id='$serverid'";
           $delRelationQuery = "DELETE FROM server_relationship WHERE server_id='$serverid'";
                if ($mysqli->query($delServerQuery) || $mysqli->query($delServiceQuery) || $mysqli->query($delRelationQuery)) {
                    echo "<p>Server Deleted</p>";
                } else {
                    echo "<p>Error deleting a server: " . $mysqli->error . "</p>";
                }
//           showserver($serverid);
           // Close the database connection
           $mysqli->close();
    }
    // Delete a service
    function deleteservice($serviceid){

           // Database connection
           $mysqli =  connectdb();

	    $delServiceQuery = "DELETE FROM services WHERE service_id='$serviceid'";
	    $delRelationQuery = "DELETE FROM server_relationship WHERE service_id='$serviceid'";
                if ($mysqli->query($delServiceQuery) || $mysqli->query($delRelationQuery)) {
                    echo "<p>Service Deleted</p>";
                } else {
                    echo "<p>Error deleting a service: " . $mysqli->error . "</p>";
                }
//           showserver($serverid);
           // Close the database connection
           $mysqli->close();
    }
    // Close the database connection
//    $mysqli->close();
?>



<script>
        // Render Mermaid diagrams
        mermaid.initialize({ startOnLoad: true });
</script>

</body>
</html>

