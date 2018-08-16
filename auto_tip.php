<?php
/*auto @xrptipbot api
Consumer API keys


Access token & access token secret


*/
//https://github.com/J7mbo/twitter-api-php
require_once('twitter-api.php');

//max 300 tweets in 3 hours
$tip = '.000589';		//1 tip every 60 seconds

$settings = array(
    'oauth_access_token' => "",
    'oauth_access_token_secret' => "",
    'consumer_key' => "",
    'consumer_secret' => ""
);


//$url_friends = "https://api.twitter.com/1.1/friends/ids.json";
function get_friends($screen_name){
	global $settings;
	$url_friends = "https://api.twitter.com/1.1/friends/list.json";

	$requestMethod = 'GET';
	$getfield = "?screen_name={$screen_name}";

	$twitter = new TwitterAPIExchange($settings);
	$friends =  json_decode($twitter->setGetfield($getfield)
		->buildOauth($url_friends, $requestMethod)
		->performRequest());
		
		return isset($friends->users) ? $friends : get_friends($screen_name);
		
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

//$url = "https://api.twitter.com/1.1/statuses/update.json";
function status($screen_name){
	global $settings,$tip;
	$tags = [
		'xrplove',
		'worldpeas',
		'motivate',
		'success',
		'random',
		'freedom'
	];
	$tag = $tags[rand(0,sizeof($tags)-1)];
	$quote = get_quote();	
	
	$status = "Hi!  @{$screen_name} +{$tip} @xrptipbot - Just your friendly #XRP fairy visiting to say hi - {$quote[1]},{$quote[0]} - #XRPCommunity #{$tag}";
	
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
	$friends = get_friends("aplusk");
	$random = get_random_friend($friends);
	$friends = get_friends($random);
	$random = get_random_friend($friends , true);
	status($random);
	sleep(60);	//you can back this down to 45secs without breaking api throttle
}