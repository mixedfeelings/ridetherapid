<?php
//include('simple_html_dom.php');
$expiration = 2; //all searches will be kept in cache for 2 hours making every renewed search instant
$split_symbol = " : "; //this is the string that goes between the category and the rest of the query
//$min_query = 2; //this means that at 3 characters and lower, the query won't start. This makes the workflow faster.
$route = 0;

require_once('workflows.php');
include('simple_html_dom.php');
$query = $argv[1];
$w = new Workflows();
$cache = $w->cache();


$routes = array(
	100 => "Division",
	101 => "Northbound",
	102 => "Southbound",
	200 => "Kalamazoo",
	201 => "Northbound",
	202 => "Southbound",
	300 => "Madison",
	301 => "Northbound",
	302 => "Southbound",
	400 => "Eastern",
	401 => "Northbound",
	402 => "Southbound",
	500 => "Wealthy/Woodland",
	501 => "Northbound",
	502 => "Southbound",
	600 => "Eastown/Woodland",
	601 => "Northbound",
	602 => "Southbound",
	700 => "West Leonard",
	701 => "Eastbound",
	702 => "Westbound",
	800 => "Grandville/Rivertown",
	801 => "Northbound",
	802 => "Southbound",
	900 => "Alpine",
	901 => "Northbound",
	902 => "Southbound",
	1000 => "Clyde Park",
	1001 => "Northbound",
	1002 => "Southbound",
	1100 => "Plainfield",
	1101 => "Northbound",
	1102 => "Southbound",
	1200 => "West Fulton",
	1201 => "Westbound",
	1202 => "Eastbound",
	1300 => "Michigan/Fuller North",
	1301 => "Northbound",
	1302 => "Southbound",
	1400 => "East Fulton",
	1401 => "Westbound",
	1402 => "Eastbound",
	1500 => "East Leonard",
	1501 => "Eastbound",
	1502 => "Westbound",
	1600 => "Wyoming/Metro Health",
	1601 => "Northbound",
	1602 => "Southbound",
	1700 => "Woodland Mall/Airport",
	1701 => "Northbound",
	1702 => "Southbound",
	1800 => "Westside",
	1801 => "Westbound",
	1802 => "Eastbound",
	1900 => "Michigan/Fuller South",
	1901 => "Northbound",
	1902 => "Southbound",
	2400 => "Burton Crosstown",
	2401 => "Westbound",
	2402 => "Eastbound",
	2800 => "28th St. Crosstown",
	2801 => "Westbound",
	2802 => "Eastbound",
	3700 => "North Campus (GVSU)",
	3701 => "Loop",
	4400 => "44th St. Crosstown",
	4401 => "Westbound",
	4402 => "Eastbound",
	4800 => "South Campus (GVSU)",
	4801 => "Loop",
	5000 => "GVSU Connector",
	5001 => "Westbound",
	5002 => "Eastbound",
	6000 => "GRCCC",
	6001 => "Westbound",
	6002 => "Eastbound",
	8500 => "Combined 37/48 (GVSU)",
	8501 => "Loop",
	9000 => "Silver Line",
	9001 => "Northbound",
	9002 => "Southbound",
);

//parse query
$parts = explode($split_symbol, $query);

if(count($parts)>1){ //match route 
	$matched_route = false;
	$main_route = 0;
	reset($parts);
	foreach ($routes as $key => $name) {
		if($key%100==0 && strcmp(strtolower(preg_replace("/[^a-zA-Z0-9]+/", "", $name)), strtolower(preg_replace("/[^a-zA-Z0-9]+/", "", current($parts)))) === 0){
			$matched_route = true;
			$main_route = $key;
			break;
		}
	}
	$route = $main_route;
}

if(count($parts)>2){
	//match direction
	$matched_route = false;
	$direction = 0;
	next($parts);
	foreach ($routes as $key => $name) {
		if(floor($key/100)==$main_route/100 && $key!=$main_route && strpos(strtolower($name), strtolower(current($parts))) === 0){
			$matched_route = true;
			$direction = $key;
			$directionName = $name;
			break;
		}
	}
	$route = $direction;
}

$matched_route = false;
if(count($parts)==1){ // there is no main route
	if(strlen($query)==0){
		// output main routes
		$matched_route = true;
		foreach ($routes as $key => $name) {
			if($key%100==0){
				$routeNo = floor($key/100);
 				$w->result( $key, $name, $name, "Browse this route", "icons/$routeNo.png", 'no', "$name$split_symbol" );
			}
		}
	} else {
		//match all routes
		foreach ($routes as $key => $name) {
			foreach (preg_split("/[^a-zA-Z0-9]+/", $name) as $particule) {
				if(strpos(strtolower($particule), strtolower($query)) === 0){
					$matched_route = true;
					if($key%100!=0){
						$name = $routes[100*floor($key/100)].$split_symbol.$name;
					}
					$routeNo = floor($key/100);
					$w->result( $key, $name, $name, "Browse this route", "icons/$routeNo.png", 'no', "$name$split_symbol" );
				}
			}
		}
	}
} elseif(count($parts)==2) { // route selected
	if(strlen(end($parts))==0){
		//output all relevant sub categories
		$matched_route = true;
		foreach ($routes as $key => $name) {
			if(floor($key/100)==$main_route/100 && $key!=$main_route){
				$name = $routes[$main_route].$split_symbol.$name;
				$routeNum = floor($main_route/100);
				$w->result( $key, $name, $name, "Browse $name stops", "icons/$routeNum.png", 'no', "$name$split_symbol" );
			}
		}
	} 
} elseif(count($parts)==3) {
	$counter = 0;
	$routeNo = floor($main_route/100);		
	$url = "http://m.ridetherapid.org/next-bus?routeNumber=".$routeNo."&direction=".$directionName;
	$data = $w->request( $url );
	$html = str_get_html($data); 
	$stops = $html->find('select[name=stopID]',0);
	$arrtmp = $stops->children();
	$name = $routes[$main_route].$split_symbol.$name.$split_symbol;
	$stopID=0;
	foreach ($arrtmp as $child) {
		if ($counter++ == 0) continue; 
		$html2 = str_get_html($child);
		$stopID = $child->getAttribute('value');
		$stop = $html2->find("option", 0)->plaintext;			
		$w->result($stopID, '', $stop, "Stop #".$stopID, "icons/$routeNo.png", 'no', "$name#$stopID$split_symbol");
		$stopID = $stopID;
	}
} elseif(count($parts)==4) {
	$stopID =  ltrim($parts[2],'#');
	$routeNo = floor($main_route/100);	
	$url_time = "http://m.ridetherapid.org/api/routes/routeStopInfo?routeNumber=".$routeNo."&direction=".$directionName."&stopID=".$stopID;
	$data_time = $w->request( $url_time );	
	$html_time = str_get_html($data_time);
	$noBus = $html_time->find(".text-info",0)->plaintext;
	$name = $routes[$main_route].$split_symbol.$stopID.$split_symbol;
	if(!$noBus) {
		$nextTime = $html_time->find(".busDetails b",0)->plaintext;
		$busStatus = strtoupper($html_time->find(".busDetails strong",0)->plaintext);
		$busTimes = $html_time->find(".next .times strong",0)->plaintext;
		$busTimes = str_replace(' ', '', $busTimes);
		$busTimes = explode(",", $busTimes);
		//var_dump($busTimes[0]);
		$w->result('', $nextTime, $nextTime." - ".$busStatus, 'Add to Reminders (15 min warning)', "icons/$routeNo.png",'yes', "add Catch Bus at $nextTime"); 
		if ($busTimes[0]) {
			foreach ($busTimes as $time) {
				$w->result('', $time, $time, 'Add to Reminders (15 min warning)', "icons/$routeNo.png",'yes', ''); 
			}
		}
	}
	else {
		$w->result('', '', "No busses found", '', "icons/$routeNo.png", 'no', '');
	}
}

echo $w->toxml();
return;
?>