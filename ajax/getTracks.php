<?php

require_once('../config/db.php');


$topics = $_GET['topics'];
$duration = $_GET['duration'];
//$topics = array();
//$topics[0] = 1;
//$topics[1] = 2;
//print_r($topics);

$tracks = getTracks($topics);

//limit tracks based on duration
$totalDuration = 0;
$targetDuration = $duration * 60; //duration comes to us in minutes. The db is in seconds

$i = 0;
$tracksLimited = array();
foreach($tracks as $track){
	if(($totalDuration + $track['duration']) < $targetDuration){
		$totalDuration += $track['duration'];
		array_push($tracksLimited , $track);
	}	
}

echo(json_encode( $tracksLimited ));





function getTracks($topics){
	//print_r($topics);
	$topics = join("','", $topics);

	$q="SELECT t1.*";
	$q.=" FROM  TrackTopic t3";
	$q.=" LEFT JOIN Track t1 ON ( t3.trackId = t1.id )";
	$q.=" WHERE t3.topicId IN ('$topics')";
	//$q.=" ORDER BY t1.id";
	//we really only want a subset, and don't want the same subset each time, so rand now so we don't have to worry about order later
	$q.=" ORDER BY RAND()";
	//the highest duration is 3 hours. 100 is way more than we will ever need, but shoot high
	$q.=" LIMIT 0 , 100";
	//echo $q;
	
	if (!$re=mysql_query($q)) {
		echo $status=': events fetch failed: '.$q."-->".mysql_error();
		return("");
	}
 
	$i=0;
	while ($row = mysql_fetch_assoc($re)) {
		$rows[$i]=$row;
		$i++;
	}
		
	return($rows);
}