<?php
$UPLOAD_DIR ="/home/mootlyprod/sharepoint.mootly.com/data/tmp_files/";
//print_r($_FILES);die();
if (!empty($_FILES["uploadedfile"])) {
    $myFile = $_FILES["uploadedfile"];
 
    if ($myFile["error"] !== UPLOAD_ERR_OK) {
        echo "<p>An error occurred.</p>";
        exit;
    }
 
    // ensure a safe filename
    $name = preg_replace("/[^A-Z0-9._-]/i", "_", $myFile["name"]);
 
    // don't overwrite an existing file
    $i = 0;
    $parts = pathinfo($name);
    while (file_exists($UPLOAD_DIR  . $name)) {
        $i++;
        $name = $parts["filename"] . "-" . $i . "." . $parts["extension"];
    }
 
    // preserve file from temporary directory
    $success = move_uploaded_file($myFile["tmp_name"],
        $UPLOAD_DIR . $name);
    if (!$success) { 
        echo "<p>Unable to save file.</p>";
        exit;
    }
 
    // set proper permissions on the new file
    chmod($UPLOAD_DIR . $name, 0644);
}

date_default_timezone_set('America/Los_Angeles');
//print($_FILES['uploadFile']);die();
$allContent = file_get_contents($UPLOAD_DIR . $name);
//now split it into an array
$array = preg_split ('/$\R?^/m', $allContent);
//print_r($array);

$previousCandidate = null;
?>
<?
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename=report.csv');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
print "\"Employee Name\",\"Business Date\",\"Clock In Time\",\"Clock Out Time\",\"Hours\",\"Minutes\",\"Total Time Worked\"\n";	
foreach ($array as $lineNumber=>$text) {
	//print ($lineNumber . "=" . $text) . "\n";
	$trimmedText = trim($text);
	//print ($lineNumber . "=" . $trimmedText) . "\n";
	if (stripos($trimmedText,"job") === 0) {
		//the previous line has the candidate name		
		$previousLine = $lineNumber - 2;
		//print ("$lineNumber = $trimmedText $previousLine \n");
		$candidateName = trim($array[$previousLine]);
		//print "$candidateName \n";
	}
	$in = null;
	$out = null;
	if (stripos($trimmedText,"In") === 0) {
		$in  = $trimmedText;
		$out = trim($array[$lineNumber + 1]);	
		$pattern = "/(In|Out)\\s+(.*?)\\s+(.*?)(AM|PM)$/";
		preg_match($pattern,$in,$inMatches);
		preg_match($pattern,$out,$outMatches);		
		//print "$candidateName ". print_r($inMatches).  print_r($outMatches) . "\n";	
		$inDate = date_create_from_format("m/d/Y g:i a","$inMatches[2] $inMatches[3] $inMatches[4]");
		$outDate = date_create_from_format("m/d/Y g:i a","$outMatches[2] $outMatches[3] $outMatches[4]");
		//print($inDate->getTimestamp()) . "\n";
		//print($outDate->getTimestamp()) . "\n";		
		$dateDiff = $outDate->diff($inDate);		
		$totalHours  = $dateDiff->format("%h");
		$totalMinutes  = $dateDiff->format("%i");
		$totalMinutesFraction  = ($dateDiff->format("%i") / 60);
		//print ("Total Hours Worked $totalHours $totalMinutes \n");
		print "\"$candidateName\",$inMatches[2]," . $inDate->format('m/d/Y g:i A') . "," . $outDate->format('m/d/Y g:i A') .  "," . $totalHours . "," . $totalMinutes . "," .  ($totalHours + $totalMinutesFraction) . "\n";	
	}	
}
?>