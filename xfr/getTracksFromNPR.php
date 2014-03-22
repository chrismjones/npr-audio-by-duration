<?php
	error_reporting(E_ALL);
	date_default_timezone_set('America/New_York');

	require_once('../config/config.php');
	rrequire_once('../config/db.php');
	
	//feeds
	//Feed ids were gathered from http://www.npr.org/api/queryGenerator.php
	//Category ids are the Joomla categories that we will places stories from that feed list into
		
		
		
		/*
			GET THE AVAILABLE TOPICS
		*/
		$q = "SELECT name FROM Topic";
		$queryResults = mysql_query($q);
		
		$approvedTopics = array();
		$i = 0;
		while($row = mysql_fetch_assoc($queryResults)){
			$approvedTopics[$i++] = $row['name'];
		}
		echo "PULLING FOR THESE TOPICS:<br>";
		print_r( $approvedTopics );
		echo "<br><br>";
		
		
		/* 
			SETUP THE LIST OF NPR FEEDS
		*/
		$endDate = date('Y-m-d');
		
		//News & Opinion
		$feeds = "1059,1060,1017,1013,1025,1136,1150,1057,1014,1007,1026,1090";
		loadNPRStories($feeds, $approvedTopics, $endDate);
		
		//Arts & Entertainment
		$feeds = "129527317,1141,1022,1137,1046,1138,1144";
		loadNPRStories($feeds, $approvedTopics, $endDate);
		
		//Lifestyle
		$feeds = "1047,1034,1032,1130,1049,1134,1053,1052,1066";
		loadNPRStories($feeds, $approvedTopics, $endDate);
		
		//Music
		$feeds = "92071316,10003,10002,1039,1107,1104,139998808,10001";
		loadNPRStories($feeds, $approvedTopics, $endDate);
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		function loadNPRStories($feeds, $approvedTopics, $endDate){
			echo "=========================================================================================<br>";
			echo "NPR API Feeds: ".$feeds;
			echo "<br>=========================================================================================<br><br>";
			
			//call the NPR xml api
			//	Add this to get a specific date and back... &endDate=2014-01-16&dateType=story
			$npr = simplexml_load_file('http://api.npr.org/query?id='.$feeds.'&action=Or&numResults=50&endDate='.$endDate.'&dateType=story&apiKey='.$GLOBALS['api_key']);
			//$npr = simplexml_load_file('http://api.npr.org/query?id=261379769&apiKey='.$GLOBALS['api_key']);
			
			foreach($npr->list->story as $row){
				
				
				//Article Topics 
				//We match these against the topics in our DB
				$i = 0;
 				$topics = array();
 				foreach($row->parent as $p){
					//genre gets us things like 'Classical' from music, which is not considered a topic by the feeds, but we do want.
					//'program' is things like "All Things Considered"
					if($p['type'] == 'topic' || $p['type'] == 'primaryTopic' || $p['type'] == 'genre' || $p['type'] == 'program'  || $p['type'] == 'series'){
						$topics[$i] = (string)$p->title;
					}
					$i++;
				}
				//slug seems to be consistent and good
				if($row->slug){
					$topics[$i] = (string)$row->slug;
 				}
 				 
				$topics = array_unique($topics);
 				//reindex things. Starting at 0 is convenient 
				$topics = array_values($topics);
				//compare the article topics with the DB topics and grab any overlap that exists
				$topics = array_intersect( $approvedTopics, $topics );
				
				
				/*
					AUDIO
					This app only wants things that have audio, so let's see.
				*/
				$filename = '';
				$audioDuration = 0;
				if($row->audio){
					//there exist type=standard and type=primary. 
					//"primary" seems to be the standard "this story has an audio file to show"
					//"standard" seems to be "this audio object is linked from something else, like a collection"
					foreach($row->audio as $audio){
						if($audio['type']=="primary"){
							if($audio->format->mp3['type'] == 'mp3'){
								//xpath makes no sense, so...
								foreach($audio->format->mp3 as $mp3){
									if($mp3['type'] == 'mp3'){ //m3u is also a type
										$filename = (string)$mp3;
									}
								}
							}
							if(!$filename && $audio->format->mediastream){
								$filename = $audio->format->mediastream;
							}
							$audioDuration = (string)$audio->duration;
						}
					}
				}
				 
				//the article has an audio track we can play and is in one of our approved topics? Let's use it.
				if($filename != '' && count($topics) ){
					//echo count($topics)."<br>";
					//echo $filename."<br>";
					//print_r($topics);
					//echo '<br>------------------------------<br />';
					/* This is purely informational while running the script */
					foreach($row->link as $link){
						if($link['type'] == 'api'){
							$apiLink = $link;
							echo $row->pubDate." --- <a href='".$apiLink."' title='".$row->title."'>".$row->title."</a><br>";	
							break;
						}
					}
					
					/* Add this article to the DB */
					insertIntoDb($topics,(string)$row->title, $filename, $audioDuration);
				}
				
		

		
			}
		}
		
function insertIntoDb($topics, $name, $file, $audioDuration){

	/*
		Get Topic IDs for the Passed in Topics
	*/
	$topics = join("','", $topics);
	$q0 = "SELECT id FROM Topic WHERE name IN ('$topics')";
	$result0 = mysql_query($q0);
	
	/*
		We want to insert these IDs into a table later,
		so build an array of the sql and join it for use lower in the script.
	*/
	$i = 0;
	$valuesToInsert = array();
	while($row = mysql_fetch_assoc($result0)){
		$valuesToInsert[$i++] = " VALUES (".$row['id'].", LAST_INSERT_ID() ) ";
	}
	$valuesToInsert = join(',',$valuesToInsert);
	
	
	/*
		Add the audio file and name to Track table
	*/
	$q  = "INSERT INTO Track (name, file, duration)";
	$q .= '	VALUES ("'.$name.'", "'.$file.'", "'.$audioDuration.'");';
	
	/*
		Add rows that match the topic to the track
	*/
	$q2 = " INSERT INTO TrackTopic (topicId, trackId) ";
	$q2 .= $valuesToInsert;
	$q2 .= ";";
	
	/*
		Use a transaction since the whole thing should die if any part breaks.
	*/
	mysql_query("BEGIN");
	
	$result = mysql_query($q);
	//echo mysql_error();
	$result2 = mysql_query($q2);
	//echo mysql_error();
	if(!$result || !$result2){
		// We aren't doing any other checks to make sure the files are unique,
		//	but there is a unique index on the filename in the Track table.
		//	So for the most part, a rollback is totally fine.
		mysql_query("ROLLBACK");
		echo "Transaction rolled back (probably a dupe filename).<br/><br/>";
	}else{
		mysql_query("COMMIT"); // transaction is committed
		echo "Track inserted.<br/><br/>";
	}
	
}