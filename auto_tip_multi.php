<?php
/*auto @xrptipbot api
Consumer API keys


Access token & access token secret


*/
//https://github.com/J7mbo/twitter-api-php
require_once('twitter-api.php');

$bots = [
	'xrp' => '@xrptipbot',
	'trx' => '@GoSeedit'
];

//max 300 tweets in 3 hours
$tip = '.000314';		//1 tip every 60 seconds

//how many tips/mentions/@ to send in one tweet
$ats = 5;

$dedication = false;	//set this to @ for dedication in message


$settings = array(
    'oauth_access_token' => "",
    'oauth_access_token_secret' => "",
    'consumer_key' => "",
    'consumer_secret' => ""
);


//$url_friends = "https://api.twitter.com/1.1/friends/ids.json";
function get_friends($screen_name){
	global $settings , $ats;
	//$url_friends = "https://api.twitter.com/1.1/friends/list.json";
	$url_friends = "https://api.twitter.com/1.1/friends/ids.json";	

	$twitter = new TwitterAPIExchange($settings);
	$friends =  json_decode($twitter->setGetfield("?screen_name={$screen_name}")
		->buildOauth($url_friends, 'GET')
		->performRequest());
	
	//return $friends;
	$ids = [];
	for($z=0; $z < $ats; $z++){
		$ids[] = $friends->ids[rand(0 , sizeof($friends->ids)-1)];
	}
	$url_users = "https://api.twitter.com/1.1/users/lookup.json";
	$twitter = new TwitterAPIExchange($settings);
	$users = json_decode($twitter->setGetfield("?user_id=" . join($ids , ","))
		->buildOauth($url_users, 'GET')
		->performRequest());
	return $users;
}//end function

/*$check - make sure user has a post in last 7 days*/
function get_random_friend($friends , $check = false){
	global $settings;
	$count = sizeof($friends->users)-1;
	$friend = $friends->users[rand(0 , $count)];
	
	if($check !== false){
		//echo "\tchecking {$friend->screen_name}\n";
		$requestMethod = 'GET';
		$getfield = "?screen_name={$friend->screen_name}&count=1";

		$twitter = new TwitterAPIExchange($settings);
		$tweets =  json_decode($twitter->setGetfield($getfield)
			->buildOauth("https://api.twitter.com/1.1/statuses/user_timeline.json", $requestMethod)
			->performRequest());		
		//print_r($tweets); die('test');
		$date1 = new DateTime();	//now
		$date2 = new DateTime($tweets[0]->created_at);
		$interval = $date1->diff($date2);
		//print_r($interval);			
		return ($interval->d < 7) ? $friend->screen_name : get_random_friend($friends);
	}
	
	return strlen($friend->screen_name) > 0 ? $friend->screen_name : get_random_friend($friends);
}//end function

function get_quote(){
	$data = [];
	$fp = @fopen("filtered.csv" , "r");
	while($row = fgetcsv($fp)){
		$data[] = $row;
	}
	fclose($fp);
	
	return $data[rand(0 , sizeof($data)-1)];
}//end function


function xrp_names($screen_name){
	global $tip;
	$temp = [];
	foreach($screen_name as $name){
		$temp[] = "@{$name->screen_name} +{$tip} @xrptipbot";
	}
	$names = join($temp , " ");	
	
	return $names;
}//end function

function trx_names($screen_name){
	global $tip;
	$temp = [];
	foreach($screen_name as $name){
		$temp[] = "@{$name->screen_name}";
	}
	$names = join($temp , " ") . " +{$tip} @GoSeedit";	
	
	return $names ;		
}//end function


//$url = "https://api.twitter.com/1.1/statuses/update.json";
function status($screen_name , $bot='xrp'){
	global $settings, $bots, $tip , $dedication;
	$tags = [
		'xrplove',
		'trxlove',
		'worldpeas',
		'motivate',
		'success',
		'random',
		'freedom'
	];
	$tag = $tags[rand(0,sizeof($tags)-1)];
	if(!empty($screen_name)){
		$func = "{$bot}_names";
		$names = $func($screen_name);
		
		$quote = get_quote();	
		
		$status = "Hi! {$names} - Just a friendly #{$bot} fairy visiting to say hi - #{$bot}Community #{$tag}";
		
		if($dedication !== false){
			$status .= " - bot run dedicated to @{$dedication}";
		}
	}//end check array
	//die($status);
	$postfields = array(
		'status' => $status
	);	

	$url = "https://api.twitter.com/1.1/statuses/update.json";
	$twitter = new TwitterAPIExchange($settings);//create new object
	echo $twitter->buildOauth($url, 'POST')
		->setPostfields($postfields)
		->performRequest() . "\n";	
}//end function

//set this up as cron job to run 1/hr

for($x=1; $x<1000 ;$x++){	
	$friends = get_friends("@geoffgolberg");
	status($friends , 'xrp');
	sleep(60);	//you can back this down to 45secs without breaking api throttle
}
