<?php
require __DIR__ . '/vendor/autoload.php';

$DIR = __DIR__.'/';

$testfilename = md5(rand(10^5,10^6)).'.txt';
$test = @file_put_contents($DIR.$testfilename, 'test');
@unlink($DIR.$testfilename);
if (!$test){ // $DIR is not writtable
    die ("local dir $DIR is not writable");
}

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient()
{
    $client = new Google_Client();
    $client->setApplicationName('Splitview for ARWC 2018');
    $client->setScopes(Google_Service_Sheets::SPREADSHEETS_READONLY);
    $client->setAuthConfig('credentials.json');
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');

    // Load previously authorized token from a file, if it exists.
    $tokenPath = 'token.json';
    if (file_exists($tokenPath)) {
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($accessToken);
    }

    // If there is no previous token or it's expired.
    if ($client->isAccessTokenExpired()) {
        // Refresh the token if possible, else fetch a new one.
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();

            if (!isset($_POST['code'])){?>
                <form action="" method="post">
                    <input type="text" name="code" placeholder="code">
                    <input type="submit" value="OK">
                </form>
                <a href="<?php echo $authUrl; ?>">get code</a>
            <?php die();}
            $authCode = trim($_POST['code']);

            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            $client->setAccessToken($accessToken);

            // Check to see if there was an error.
            if (array_key_exists('error', $accessToken)) {
                throw new Exception(join(', ', $accessToken));
            }
        }
        // Save the token to a file.
        if (!file_exists(dirname($tokenPath))) {
            mkdir(dirname($tokenPath), 0700, true);
        }
        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
    }
    return $client;
}


// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_Sheets($client);

// Prints the names and majors of students in a sample spreadsheet:
// https://docs.google.com/spreadsheets/d/1wa3uIqONMfbfF4caCdbTxDAB7w3eXXtwXiyjt5lM-bk/edit
$spreadsheetId = '1wa3uIqONMfbfF4caCdbTxDAB7w3eXXtwXiyjt5lM-bk';


$teams = array();

$range = 'teams!A2:D';
$response = $service->spreadsheets_values->get($spreadsheetId, $range);
$values = $response->getValues();

if (empty($values)) {
    echo "No data found for teams.\n";
    die();
} else {
    foreach ($values as $row) {
        $teams[intval($row[0])] = array("bib"=>$row[0],"name" => $row[1],"slug" => $row[2],"flag"=>strtolower($row[3]));
    }
}

$groups = array();
$range = 'groups!A2:C';

$response = $service->spreadsheets_values->get($spreadsheetId, $range);
$values = $response->getValues();

if (empty($values)) {
    echo "No data found for groups.\n";
    die();
} else {
    foreach ($values as $row) {
        $groups[] = array("teams"=>$row[0],"delay" => $row[1],"sport"=>$row[2]);
    }
}
$sleeptimes = array();
$range = 'sleep!A2:B';

$response = $service->spreadsheets_values->get($spreadsheetId, $range);
$values = $response->getValues();

if (!empty($values)) {
    foreach ($values as $row) {
        $sleeptimes[intval($row[0])] = $row[1];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
	<title></title>
	<link href="https://fonts.googleapis.com/css?family=Permanent+Marker|Josefin+Sans|Raleway|Oswald" rel="stylesheet">
    <link href="http://tools.raidsaventure.fr/flag-icon-css/css/flag-icon.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
<?php


$leaderColor1 = "#ED6C30";
$leaderColor2 = "#FCC21B";
$followColor1 = "#006CA2";
$followColor2 = "#40C0E7";

//$date = 'Dimanche 13.08.2017 15:00 GMT+2';
$date = date('l d.m.Y H:i')."GMT+2";

$night = '';//'https://images.unsplash.com/photo-1501812881134-f452d2634002?dpr=1&auto=format&fit=crop&w=1200&h=300&q=80&cs=tinysrgb&crop=';

$biker = file_get_contents(__DIR__.'/img/biker.svg');
$runner = file_get_contents(__DIR__.'/img/runner.svg');
$sleeper = file_get_contents(__DIR__.'/img/sleeper.svg');
$stand = file_get_contents(__DIR__.'/img/stand.svg');
$canoe = file_get_contents(__DIR__.'/img/canoe.svg');

?>
<div class="box" style="background-image: url('<?php echo $night; ?>');">
<table>
	<tbody>
		<tr>
			<td></td>
			<?php
			foreach ($groups as $group) {
				$teamsid =  explode(",", $group['teams']);
				?>
					<td class="title">
						<?php foreach ($teamsid as $teamid) {
							if ($teams[$teamid]["flag"]) {
                                echo "<img src=\"http://maps.opentracking.co.uk/presentation/flags/arws/".$teams[$teamid]["flag"].".png\">";
                                //echo "<span class=\"flag-icon flag-icon-" . $teams[$teamid]["flag"] . "\"></span>&nbsp;";
                            }
                            echo "#";
							echo $teams[$teamid]["bib"];
							echo " ";
							echo $teams[$teamid]["slug"];
							echo "<br/>";
						}?>
					</td>
				<?php
			}
			?>
		</tr>
		<tr>
		<td></td>
		<?php
		foreach ($groups as $key => $group) {
			?>
			<td class="illu">
	<?php
	$teamsid =  explode(",", $group['teams']);
	if ($key == 1){
		$runner = str_replace($leaderColor1, $followColor1, $runner);
		$runner = str_replace($leaderColor2, $followColor2, $runner);
		$biker = str_replace($leaderColor1, $followColor1, $biker);
		$biker = str_replace($leaderColor2, $followColor2, $biker);
		$stand = str_replace($leaderColor1, $followColor1, $stand);
		$stand = str_replace($leaderColor2, $followColor2, $stand);
		$canoe = str_replace($leaderColor1, $followColor1, $canoe);
		$canoe = str_replace($leaderColor2, $followColor2, $canoe);
	}
	switch ($group["sport"]) {
		case 'bike':
			for ($i=0; $i < min(count($teamsid),3); $i++) { 
				echo $biker;
			}
			break;
		case 'run':
			for ($i=0; $i < min(count($teamsid),3); $i++) { 
				echo $runner;
			}
			break;
		case 'stand':
			for ($i=0; $i < min(count($teamsid),3); $i++) { 
				echo $stand;
			}
			break;
		case 'canoe':
			for ($i=0; $i < min(count($teamsid),3); $i++) { 
				echo $canoe;
			}
			break;
		default:
			# code...
			break;
	}
	?>
	</td>
			<?php
		}
		?>
	</tr>
	<tr>
	<td></td>
		<?php
		foreach ($groups as $group) {
			echo '<td class="time">';
			if ($group['delay'] > 0)
				echo "+".$group['delay']."'";
			echo "</td>";
		}
		?>
	</tr>
	<tr class="allsleep">
	<td style="width: 20px;">
		<?php echo $sleeper; ?>
	</td>
		<?php
		foreach ($groups as $group) {
			$teamsid =  explode(",", $group['teams']);
			?>
				<td class="sleep">
					<?php foreach ($teamsid as $teamid) {
						if (isset($sleeptimes[$teamid]))
							echo $sleeptimes[$teamid]."<br/>";
					}?>
				</td>
			<?php
		}
		?>
	</tr>
	</tbody>
</table>
	<div class="footer">
		<span class="time">
			<?php echo $date; ?>
		</span>
		<a class="author" href="">
			RaidsAventure.fr
		</a>
	</div>
</div>
</body>
</html>