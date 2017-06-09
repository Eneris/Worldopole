<?php
# Well, this file can only be loaded by your own server
# as it contains json datas formatted
# and you don't want to have other website to get your datas ;)
# If you want to use this file as an "API" just remove the first condition.

$pos = !empty($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], getenv('HTTP_HOST'));

if ($pos === false) {
	http_response_code(401);
	die('Restricted access');
}


include_once('../../config.php');

// Include & load the variables
// ############################

$variables 	= SYS_PATH.'/core/json/variables.json';
$config 	= json_decode(file_get_contents($variables));

// Manage Time Interval
// #####################

include_once('timezone.loader.php');


// Load the locale elements
############################

include_once('locales.loader.php');


// Load functions
##################
include_once(SYS_PATH.'/functions.php');

# MySQL
$mysqli 	= new mysqli(SYS_DB_HOST, SYS_DB_USER, SYS_DB_PSWD, SYS_DB_NAME, SYS_DB_PORT);
if ($mysqli->connect_error != '') {
	exit('Error MySQL Connect');
}
$mysqli->set_charset('utf8mb4');
$request = "";
if (isset($_GET['type'])) {
	$request = $_GET['type'];
}
$postRequest = "";
if (isset($_POST['type'])) {
	$postRequest = $_POST['type'];
	$request= "postRequest";
}
switch ($request) {
	############################
	//
	// Update datas on homepage
	//
	############################

	case 'home_update':
		// Right now
		// ---------

		$req 		= "SELECT COUNT(*) AS total FROM pokemon WHERE disappear_time >= UTC_TIMESTAMP()";
		$result 	= $mysqli->query($req);
		$data 		= $result->fetch_object();

		$values[] 	= $data->total;


		// Lured stops
		// -----------

		$req 		= "SELECT COUNT(*) AS total FROM pokestop WHERE lure_expiration >= UTC_TIMESTAMP()";
		$result 	= $mysqli->query($req);
		$data 		= $result->fetch_object();

		$values[] 	= $data->total;



		// Team battle
		// -----------

		$req 		= "SELECT COUNT(DISTINCT(gym_id)) AS total FROM gym";
		$result 	= $mysqli->query($req);
		$data 		= $result->fetch_object();

		$values[] 	= $data->total;

		// Team
		// 1 = bleu
		// 2 = rouge
		// 3 = jaune

		$req	= "SELECT COUNT(DISTINCT(gym_id)) AS total FROM gym WHERE team_id = '2'";
		$result	= $mysqli->query($req);
		$data	= $result->fetch_object();

		// Red
		$values[] = $data->total;


		$req	= "SELECT COUNT(DISTINCT(gym_id)) AS total FROM gym WHERE team_id = '1'";
		$result	= $mysqli->query($req);
		$data	= $result->fetch_object();

		// Blue
		$values[] = $data->total;


		$req	= "SELECT COUNT(DISTINCT(gym_id)) AS total FROM gym WHERE team_id = '3'";
		$result	= $mysqli->query($req);
		$data	= $result->fetch_object();

		// Yellow
		$values[] = $data->total;

		$req	= "SELECT COUNT(DISTINCT(gym_id)) AS total FROM gym WHERE team_id = '0'";
		$result	= $mysqli->query($req);
		$data	= $result->fetch_object();

		// Neutral
		$values[] = $data->total;

		header('Content-Type: application/json');
		echo json_encode($values);

		break;


	####################################
	//
	// Update latests spawn on homepage
	//
	####################################

	case 'spawnlist_update':
		// Recent spawn
		// ------------
		$total_spawns = array();
		$last_uid_param = "";
		if (isset($_GET['last_uid'])) {
			$last_uid_param = $_GET['last_uid'];
		}
		if ($config->system->recents_filter) {
			// get all mythic pokemon ids
			$mythic_pokemons = array();
			foreach ($pokemons->pokemon as $id => $pokemon) {
				if ($pokemon->spawn_rate < $config->system->recents_filter_rarity && $pokemon->rating >= $config->system->recents_filter_rating) {
					$mythic_pokemons[] = $id;
				}
			}

			// get last mythic pokemon
			$req = "SELECT pokemon_id, encounter_id, disappear_time, last_modified, (CONVERT_TZ(disappear_time, '+00:00', '".$time_offset."')) AS disappear_time_real,
					latitude, longitude, cp, individual_attack, individual_defense, individual_stamina, move_1, move_2
					FROM pokemon
					WHERE pokemon_id IN (".implode(",", $mythic_pokemons).")
					ORDER BY last_modified DESC
					LIMIT 0,12";
		} else {
			// get last pokemon
			$req = "SELECT pokemon_id, encounter_id, disappear_time, last_modified, (CONVERT_TZ(disappear_time, '+00:00', '".$time_offset."')) AS disappear_time_real,
					latitude, longitude, cp, individual_attack, individual_defense, individual_stamina, move_1, move_2
					FROM pokemon
					ORDER BY last_modified DESC
					LIMIT 0,12";
		}
		$result = $mysqli->query($req);
		while ($data = $result->fetch_object()) {
			$new_spawn = array();
			$pokeid = $data->pokemon_id;
			$pokeuid = $data->encounter_id;

			if ($last_uid_param != $pokeuid) {
				$last_seen = strtotime($data->disappear_time_real);

				$last_location = new stdClass();
				$last_location->latitude = $data->latitude;
				$last_location->longitude = $data->longitude;

				$encdetails = new stdClass();
				$encdetails->available = false;

				if ($config->system->recents_encounter_details) {
					$encdetails->cp = $data->cp;
					$encdetails->attack = $data->individual_attack;
					$encdetails->defense = $data->individual_defense;
					$encdetails->stamina = $data->individual_stamina;
					$encdetails->move1 = $data->move_1;
					$encdetails->move2 = $data->move_2;
					$encdetails->iv = number_format((100/45)*($encdetails->attack+$encdetails->defense+$encdetails->stamina), 1);
					if (isset($encdetails->cp) && isset($encdetails->attack) && isset($encdetails->defense) && isset($encdetails->stamina)) {
						$encdetails->available = true;
					}
				}

				if ($encdetails->available) {
					$move1 = $encdetails->move1;
					$move2 = $encdetails->move2;
					$html = '
				<div class="col-md-1 col-xs-4 pokemon-single" data-pokeid="'.$pokeid.'" data-pokeuid="'.$pokeuid.'" title="'.$encdetails->iv.'% - '.$move->$move1->name.' / '.$move->$move2->name.'" style="display: none;">';
				} else {
					$html = '
				<div class="col-md-1 col-xs-4 pokemon-single" data-pokeid="'.$pokeid.'" data-pokeuid="'.$pokeuid.'" style="display: none;">';
				}
				$html .= '
				<a href="pokemon/'.$pokeid.'"><img src="core/pokemons/'.$pokeid.$config->system->pokeimg_suffix.'" alt="'.$pokemons->pokemon->$pokeid->name.'" class="img-responsive"></a>
				<a href="pokemon/'.$pokeid.'"><p class="pkmn-name">'.$pokemons->pokemon->$pokeid->name.'</p></a>
				<a href="https://maps.google.com/?q='.$last_location->latitude.','.$last_location->longitude.'&ll='.$last_location->latitude.','.$last_location->longitude.'&z=16" target="_blank">
					<small class="pokemon-timer">00:00:00</small>
				</a>';
				if ($config->system->recents_encounter_details) {
					if ($encdetails->available) {
						if ($config->system->iv_numbers) {
							$html .= '
							<div class="progress" style="height: 15px; margin-bottom: 0">
								<div title="'.$locales->IV_ATTACK.': '.$encdetails->attack.'" class="progress-bar progress-bar-danger" role="progressbar" aria-valuenow="'.$encdetails->attack.'" aria-valuemin="0" aria-valuemax="45" style="width: '.(100 / 3).'%; line-height: 16px">
									<span class="sr-only">'.$locales->IV_ATTACK.': '.$encdetails->attack.'</span>'.$encdetails->attack .'
								</div>
								<div title="'.$locales->IV_DEFENSE.': '.$encdetails->defense.'" class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="'.$encdetails->defense.'" aria-valuemin="0" aria-valuemax="45" style="width: '.(100 / 3).'%; line-height: 16px">
									<span class="sr-only">'.$locales->IV_DEFENSE.': '.$encdetails->defense.'</span>'.$encdetails->defense .'
								</div>
								<div title="'.$locales->IV_STAMINA.': '.$encdetails->stamina.'" class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="'.$encdetails->stamina.'" aria-valuemin="0" aria-valuemax="45" style="width: '.(100 / 3).'%; line-height: 16px">
									<span class="sr-only">'.$locales->IV_STAMINA.': '.$encdetails->stamina.'</span>'.$encdetails->stamina .'
								</div>
							</div>';
						} else {
							$html .= '
							<div class="progress" style="height: 6px; width: 80%; margin: 5px auto 0 auto">
							<div title="'.$locales->IV_ATTACK.': '.$encdetails->attack.'" class="progress-bar progress-bar-danger" role="progressbar" aria-valuenow="'.$encdetails->attack.'" aria-valuemin="0" aria-valuemax="45" style="width: '.(((100 / 15) * $encdetails->attack) / 3).'%">
									<span class="sr-only">'.$locales->IV_ATTACK.': '.$encdetails->attack.'</span>
							</div>
							<div title="'.$locales->IV_DEFENSE.': '.$encdetails->defense .'" class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="'.$encdetails->defense.'" aria-valuemin="0" aria-valuemax="45" style="width: '.(((100 / 15) * $encdetails->defense) / 3).'%">
									<span class="sr-only">'.$locales->IV_DEFENSE.': '.$encdetails->defense.'</span>
							</div>
							<div title="'.$locales->IV_STAMINA.': '.$encdetails->stamina .'" class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="'.$encdetails->stamina.'" aria-valuemin="0" aria-valuemax="45" style="width: '.(((100 / 15) * $encdetails->stamina) / 3).'%">
									<span class="sr-only">'.$locales->IV_STAMINA.': '.$encdetails->stamina.'</span>
							</div>
							</div>';
						}
						$html .= '<small>'.$encdetails->cp.'</small>';
					} else {
						if ($config->system->iv_numbers) {
							$html .= '
							<div class="progress" style="height: 15px; margin-bottom: 0">
								<div title="'.$locales->IV_ATTACK.': not available" class="progress-bar progress-bar-danger" role="progressbar" aria-valuenow="'.$encdetails->attack.'" aria-valuemin="0" aria-valuemax="45" style="width: '.(100 / 3).'%; line-height: 16px">
									<span class="sr-only">'.$locales->IV_ATTACK.': '.$locales->NOT_AVAILABLE.'</span>?
								</div>
								<div title="'.$locales->IV_DEFENSE.': not available" class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="'.$encdetails->defense.'" aria-valuemin="0" aria-valuemax="45" style="width: '.(100 / 3).'%; line-height: 16px">
									<span class="sr-only">'.$locales->IV_DEFENSE.': '.$locales->NOT_AVAILABLE.'</span>?
								</div>
								<div title="'.$locales->IV_STAMINA.': not available" class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="'.$encdetails->stamina.'" aria-valuemin="0" aria-valuemax="45" style="width: '.(100 / 3).'%; line-height: 16px">
									<span class="sr-only">'.$locales->IV_STAMINA.': '.$locales->NOT_AVAILABLE.'</span>?
								</div>
							</div>';
						} else {
						$html .= '
						<div class="progress" style="height: 6px; width: 80%; margin: 5px auto 0 auto">
							<div title="IV not available" class="progress-bar" role="progressbar" style="width: 100%; background-color: rgb(210,210,210)" aria-valuenow="1" aria-valuemin="0" aria-valuemax="1">
								<span class="sr-only">IV '.$locales->NOT_AVAILABLE.'</span>
							</div>
						</div>';
						}
						$html .= '<small>???</small>';
					}
				}
				$html .= '
				</div>';
				$new_spawn['html'] = $html;
				$countdown = $last_seen - time();
				$new_spawn['countdown'] = $countdown;
				$new_spawn['pokemon_uid'] = $pokeuid;
				$total_spawns[] = $new_spawn;
			} else {
				break;
			}
		}

		header('Content-Type: application/json');
		echo json_encode($total_spawns);

		break;


	####################################
	//
	// List Pokestop
	//
	####################################

	case 'pokestop':
		$where = "";
		if ($config->system->only_lured_pokestops) {
			$where = "WHERE lure_expiration > UTC_TIMESTAMP() ORDER BY lure_expiration";
		}
		$req = "SELECT latitude, longitude, lure_expiration, UTC_TIMESTAMP() AS now, (CONVERT_TZ(lure_expiration, '+00:00', '".$time_offset."')) AS lure_expiration_real FROM pokestop ".$where."";

		//show all stops if no lure active
		if (!$mysqli->query($req)->fetch_object()) {
			$req = "SELECT latitude, longitude, lure_expiration, UTC_TIMESTAMP() AS now, (CONVERT_TZ(lure_expiration, '+00:00', '".$time_offset."')) AS lure_expiration_real FROM pokestop";
		}
		$result = $mysqli->query($req);

		$pokestops = [];

		while ($data = $result->fetch_object()) {
			if ($data->lure_expiration >= $data->now) {
				$icon = 'pokestap_lured.png';
				$text = sprintf($locales->POKESTOPS_MAP_LURED, date('H:i:s', strtotime($data->lure_expiration_real)));
			} else {
				$icon = 'pokestap.png';
				$text = $locales->POKESTOPS_MAP_REGULAR;
			}

			$pokestops[] = [
				$text,
				$icon,
				$data->latitude,
				$data->longitude
			];
		}

		header('Content-Type: application/json');
		echo json_encode($pokestops);

		break;


	####################################
	//
	// Update data for the gym battle
	//
	####################################

	case 'update_gym':
		$teams			= new stdClass();
		$teams->mystic 		= 1;
		$teams->valor 		= 2;
		$teams->instinct 	= 3;


		foreach ($teams as $team_name => $team_id) {
			$req	= "SELECT COUNT(DISTINCT(gym_id)) AS total, ROUND(AVG(gym_points),0) AS average_points FROM gym WHERE team_id = '".$team_id."'";
			$result	= $mysqli->query($req);
			$data	= $result->fetch_object();

			$return[] 	= $data->total;
			$return[]	= $data->average_points;
		}

		header('Content-Type: application/json');
		echo json_encode($return);

		break;


	####################################
	//
	// Get datas for the gym map
	//
	####################################


	case 'gym_map':
		$req	= "SELECT gym_id, team_id, gym_points, latitude, longitude, (CONVERT_TZ(last_scanned, '+00:00', '".$time_offset."')) AS last_scanned FROM gym";
		$result = $mysqli->query($req);

		$gyms = [];

		while ($data = $result->fetch_object()) {
			// Team
			// 1 = bleu
			// 2 = rouge
			// 3 = jaune

			switch ($data->team_id) {
				case 0:
					$icon	= 'map_white.png';
					$team	= 'No Team (yet)';
					$color	= 'rgba(0, 0, 0, .6)';
					break;

				case 1:
					$icon	= 'map_blue_';
					$team	= 'Team Mystic';
					$color	= 'rgba(74, 138, 202, .6)';
					break;

				case 2:
					$icon	= 'map_red_';
					$team	= 'Team Valor';
					$color	= 'rgba(240, 68, 58, .6)';
					break;

				case 3:
					$icon	= 'map_yellow_';
					$team	= 'Team Instinct';
					$color	= 'rgba(254, 217, 40, .6)';
					break;
			}

			// Set gym level
			$gym_level = gym_level($data->gym_points);

			if ($data->team_id != 0) {
				$icon .= $gym_level.".png";
			}

			$gyms[] = [
				$icon,
				$data->latitude,
				$data->longitude,
				$data->gym_id,
			];
		}

		header('Content-Type: application/json');
		echo json_encode($gyms);

		break;


	####################################
	//
	// Get datas for gym defenders
	//
	####################################

	case 'gym_defenders':
		$gym_id = $mysqli->real_escape_string($_GET['gym_id']);
		$req	= "SELECT gymdetails.name AS name, gymdetails.description AS description, gym.gym_points AS points, gymdetails.url AS url, gym.team_id AS team,
					(CONVERT_TZ(gym.last_scanned, '+00:00', '".$time_offset."')) AS last_scanned, gym.guard_pokemon_id AS guard_pokemon_id
					FROM gymdetails
					LEFT JOIN gym ON gym.gym_id = gymdetails.gym_id
					WHERE gym.gym_id='".$gym_id."'";
		$result = $mysqli->query($req);
		
		$gymData['gymDetails']['gymInfos'] = false;

		while ($data = $result->fetch_object()) {
			$gymData['gymDetails']['gymInfos']['name'] = $data->name;
			$gymData['gymDetails']['gymInfos']['description'] = $data->description;
			if ($data->url == null) {
				$gymData['gymDetails']['gymInfos']['url'] = '';
			} else {
				$gymData['gymDetails']['gymInfos']['url'] = $data->url;
			}
			$gymData['gymDetails']['gymInfos']['points'] = $data->points;
			$gymData['gymDetails']['gymInfos']['level'] = 0;
			$gymData['gymDetails']['gymInfos']['last_scanned'] = $data->last_scanned;
			$gymData['gymDetails']['gymInfos']['team'] = $data->team;
			$gymData['gymDetails']['gymInfos']['guardPokemonId'] = $data->guard_pokemon_id;
			$gymData['gymDetails']['gymInfos']['level'] = gym_level($data->points);
		}

		$req 	= "SELECT DISTINCT gympokemon.pokemon_uid, pokemon_id, iv_attack, iv_defense, iv_stamina, MAX(cp) AS cp, gymmember.gym_id
					FROM gympokemon INNER JOIN gymmember ON gympokemon.pokemon_uid=gymmember.pokemon_uid
					GROUP BY gympokemon.pokemon_uid, pokemon_id, iv_attack, iv_defense, iv_stamina, gym_id
					HAVING gymmember.gym_id='".$gym_id."'
					ORDER BY cp DESC";
		$result = $mysqli->query($req);

		$i = 0;

		$gymData['infoWindow'] = '
			<div class="gym_defenders">
			';
		while ($data = $result->fetch_object()) {
			$gymData['gymDetails']['pokemons'][] = $data;
			if ($data != false) {
				if ($config->system->iv_numbers) {
					$gymData['infoWindow'] .= '
					<div style="text-align: center; width: 50px; display: inline-block; margin-right: 3px">
						<a href="pokemon/'.$data->pokemon_id.'">
						<img src="core/pokemons/'.$data->pokemon_id.$config->system->pokeimg_suffix.'" height="50" style="display:inline-block" >
						</a>
						<p class="pkmn-name">'.$data->cp.'</p>
						<div class="progress" style="height: 12px; margin-bottom: 0">
							<div title="'.$locales->IV_ATTACK.': '.$data->iv_attack.'" class="progress-bar progress-bar-danger" role="progressbar" aria-valuenow="'.$data->iv_attack.'" aria-valuemin="0" aria-valuemax="45" style="width: '.(100 / 3).'%; line-height: 13px; font-size: 11px">
								<span class="sr-only">'.$locales->IV_ATTACK.' : '.$data->iv_attack.'</span>'.$data->iv_attack.'
								</div>
								<div title="'.$locales->IV_DEFENSE.': '.$data->iv_defense.'" class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="'.$data->iv_defense.'" aria-valuemin="0" aria-valuemax="45" style="width: '.(100 / 3).'%; line-height: 13px; font-size: 11px">
									<span class="sr-only">'.$locales->IV_DEFENSE.' : '.$data->iv_defense.'</span>'. $data->iv_defense .'
								</div>
								<div title="'.$locales->IV_STAMINA.': '.$data->iv_stamina.'" class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="'.$data->iv_stamina.'" aria-valuemin="0" aria-valuemax="45" style="width: '.(100 / 3).'%; line-height: 13px; font-size: 11px">
									<span class="sr-only">'.$locales->IV_STAMINA.' : '.$data->iv_stamina.'</span>'. $data->iv_stamina .'
								</div>
							</div>
						</div>';
				} else {
					$gymData['infoWindow'] .= '
					<div style="text-align: center; width: 50px; display: inline-block; margin-right: 3px">
						<a href="pokemon/'.$data->pokemon_id.'">
						<img src="core/pokemons/'.$data->pokemon_id.$config->system->pokeimg_suffix.'" height="50" style="display:inline-block" >
						</a>
						<p class="pkmn-name">'.$data->cp.'</p>
						<div class="progress" style="height: 4px; width: 40px; margin-bottom: 10px; margin-top: 2px; margin-left: auto; margin-right: auto">
							<div title="'.$locales->IV_ATTACK.': '.$data->iv_attack.'" class="progress-bar progress-bar-danger" role="progressbar" aria-valuenow="'.$data->iv_attack.'" aria-valuemin="0" aria-valuemax="45" style="width: '.(((100 / 15) * $data->iv_attack) / 3).'%">
								<span class="sr-only">'.$locales->IV_ATTACK.': '.$data->iv_attack.'</span>
							</div>
							<div title="'.$locales->IV_DEFENSE.': '.$data->iv_defense.'" class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="'.$data->iv_defense.'" aria-valuemin="0" aria-valuemax="45" style="width: '.(((100 / 15) * $data->iv_defense) / 3).'%">
								<span class="sr-only">'.$locales->IV_DEFENSE.': '.$data->iv_defense.'</span>
							</div>
							<div title="'.$locales->IV_STAMINA.': '.$data->iv_stamina.'" class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="'.$data->iv_stamina.'" aria-valuemin="0" aria-valuemax="45" style="width: '.(((100 / 15) * $data->iv_stamina) / 3).'%">
								<span class="sr-only">'.$locales->IV_STAMINA.': '.$data->iv_stamina.'</span>
							</div>
						</div>
					</div>'
						; }
			} else {
				$gymData['infoWindow'] .= '
				<div style="text-align: center; width: 50px; display: inline-block; margin-right: 3px">
					<a href="pokemon/'.$gymData['gymDetails']['gymInfos']['guardPokemonId'].'">
					<img src="core/pokemons/'.$gymData['gymDetails']['gymInfos']['guardPokemonId'].$config->system->pokeimg_suffix.'" height="50" style="display:inline-block" >
					</a>
					<p class="pkmn-name">???</p>
				</div>'
				;
			}
			$i++;
		}

		// check whether we could retrieve gym infos, otherwise use basic gym info
		if (!$gymData['gymDetails']['gymInfos']) {
			$req = "SELECT gym_id, team_id, gym_points, guard_pokemon_id, latitude, longitude, (CONVERT_TZ(last_scanned, '+00:00', '".$time_offset."')) AS last_scanned
				FROM gym WHERE gym_id='".$gym_id."'";
			$result = $mysqli->query($req);
			$data = $result->fetch_object();

			$gymData['gymDetails']['gymInfos']['name'] = $locales->NOT_AVAILABLE;
			$gymData['gymDetails']['gymInfos']['description'] = $locales->NOT_AVAILABLE;
			$gymData['gymDetails']['gymInfos']['url'] = 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/86/Solid_grey.svg/200px-Solid_grey.svg.png';
			$gymData['gymDetails']['gymInfos']['points'] = $data->gym_points;
			$gymData['gymDetails']['gymInfos']['level'] = 0;
			$gymData['gymDetails']['gymInfos']['last_scanned'] = $data->last_scanned;
			$gymData['gymDetails']['gymInfos']['team'] = $data->team_id;
			$gymData['gymDetails']['gymInfos']['guardPokemonId'] = $data->guard_pokemon_id;
			$gymData['gymDetails']['gymInfos']['level'] = gym_level($data->gym_points);

			$gymData['infoWindow'] .= '
				<div style="text-align: center; width: 50px; display: inline-block; margin-right: 3px">
					<a href="pokemon/'.$data->guard_pokemon_id.'">
					<img src="core/pokemons/'.$data->guard_pokemon_id.$config->system->pokeimg_suffix.'" height="50" style="display:inline-block" >
					</a>
					<p class="pkmn-name">???</p>
				</div>';
		}
		$gymData['infoWindow'] = $gymData['infoWindow'].'</div>';

		header('Content-Type: application/json');
		echo json_encode($gymData);

		break;


	case 'trainer':
		$name = "";
		$page = "0";
		$where = "";
		$order="";
		$team=0;
		$ranking=0;
		if (isset($_GET['name'])) {
			$trainer_name = mysqli_real_escape_string($mysqli, $_GET['name']);
			$where = " HAVING name LIKE '%".$trainer_name."%'";
		}
		if (isset($_GET['team']) && $_GET['team']!=0) {
			$team = mysqli_real_escape_string($mysqli, $_GET['team']);
			$where .= ($where==""?" HAVING":"AND ")." team = ".$team;
		}
		if (isset($_GET['page'])) {
			$page = mysqli_real_escape_string($mysqli, $_GET['page']);
		}
		if (isset($_GET['ranking'])) {
			$ranking = mysqli_real_escape_string($mysqli, $_GET['ranking']);
		}

		switch ($ranking) {
			case 1:
				$order=" ORDER BY active DESC ";
				break;
			case 2:
				$order=" ORDER BY maxCp DESC ";
				break;
			default:
				$order=" ORDER BY level DESC, active DESC ";
		}

		$limit = " LIMIT ".($page*10).",10 ";


		$req = "SELECT trainer.*, COUNT(actives_pokemons.trainer_name) AS active, max(actives_pokemons.cp) AS maxCp
				FROM trainer
				LEFT JOIN (SELECT DISTINCT gympokemon.pokemon_id, gympokemon.pokemon_uid, gympokemon.trainer_name, gympokemon.cp, DATEDIFF(UTC_TIMESTAMP(), gympokemon.last_seen) AS last_scanned
				FROM gympokemon
				INNER JOIN (SELECT gymmember.pokemon_uid, gymmember.gym_id FROM gymmember GROUP BY gymmember.pokemon_uid, gymmember.gym_id HAVING gymmember.gym_id <> '') AS filtered_gymmember
				ON gympokemon.pokemon_uid = filtered_gymmember.pokemon_uid) AS actives_pokemons ON actives_pokemons.trainer_name = trainer.name
				GROUP BY trainer.name ".$where.$order.$limit;

		$result = $mysqli->query($req);
		$trainers = array();
		while ($data = $result->fetch_object()) {
			$data->last_seen = date("Y-m-d", strtotime($data->last_seen));
			$trainers[$data->name] = $data;
		}
		foreach ($trainers as $trainer) {
			$reqRanking = "SELECT COUNT(1) AS rank FROM trainer WHERE trainer.level >= ".$trainer->level;
			$resultRanking = $mysqli->query($reqRanking);
			while ($data = $resultRanking->fetch_object()) {
				$trainer->rank = $data->rank ;
			}
			$req = "(SELECT DISTINCT gympokemon.pokemon_id, gympokemon.pokemon_uid, gympokemon.cp, DATEDIFF(UTC_TIMESTAMP(), gympokemon.last_seen) AS last_scanned, gympokemon.trainer_name, gympokemon.iv_defense, gympokemon.iv_stamina, gympokemon.iv_attack, TRUNCATE(gympokemon.cp_multiplier,6) as cp_multiplier, gympokemon.num_upgrades, filtered_gymmember.gym_id, filtered_gymmember.name as gym_name, '1' AS active
					FROM gympokemon INNER JOIN
					(SELECT gymmember.pokemon_uid, gymmember.gym_id, gymdetails.name FROM gymmember
					LEFT JOIN gymdetails ON gymmember.gym_id = gymdetails.gym_id
					GROUP BY gymmember.pokemon_uid, gymmember.gym_id HAVING gymmember.gym_id <> '') AS filtered_gymmember
					ON gympokemon.pokemon_uid = filtered_gymmember.pokemon_uid
					WHERE gympokemon.trainer_name='".$trainer->name."'
					ORDER BY gympokemon.cp DESC)";

			$resultPkms = $mysqli->query($req);
			$trainer->pokemons = array();
			$active_gyms=0;
			$pkmCount = 0;
			while ($resultPkms && $dataPkm = $resultPkms->fetch_object()) {
				$active_gyms++;
				$trainer->pokemons[$pkmCount++] = $dataPkm;
			}
			$trainer->gyms = $active_gyms;

			$req = "(SELECT DISTINCT gympokemon.pokemon_id, gympokemon.pokemon_uid, gympokemon.cp, DATEDIFF(UTC_TIMESTAMP(), gympokemon.last_seen) AS last_scanned, gympokemon.trainer_name, gympokemon.iv_defense, gympokemon.iv_stamina, gympokemon.iv_attack, TRUNCATE(gympokemon.cp_multiplier,6) as cp_multiplier, gympokemon.num_upgrades, null AS gym_id, '0' AS active
					FROM gympokemon LEFT JOIN
					(SELECT * FROM gymmember HAVING gymmember.gym_id <> '') AS filtered_gymmember
					ON gympokemon.pokemon_uid = filtered_gymmember.pokemon_uid
					WHERE filtered_gymmember.pokemon_uid IS NULL AND gympokemon.trainer_name='".$trainer->name."'
					ORDER BY gympokemon.cp DESC )";

			$resultPkms = $mysqli->query($req);

			while ($resultPkms && $dataPkm = $resultPkms->fetch_object()) {
				$trainer->pokemons[$pkmCount++] = $dataPkm;
			}
		}
		$json = array();
		$json['trainers'] = $trainers;
		$locale = array();
		$locale["today"] = $locales->TODAY;
		$locale["day"] = $locales->DAY;
		$locale["days"] = $locales->DAYS;
		$locale["ivAttack"] = $locales->IV_ATTACK;
		$locale["ivDefense"] = $locales->IV_DEFENSE;
		$locale["ivStamina"] = $locales->IV_STAMINA;
		$json['locale'] = $locale;

		header('Content-Type: application/json');
		echo json_encode($json);

		break;


	case 'gyms':
		$page = '0';
		$where = '';
		$order = '';
		$ranking = 0;
		if (isset($_GET['name']) && $_GET['name'] != '') {
			$gym_name = mysqli_real_escape_string($mysqli, $_GET['name']);
			$where = " WHERE name LIKE '%".$gym_name."%'";
		}
		if (isset($_GET['team']) && $_GET['team'] != '') {
			$team = mysqli_real_escape_string($mysqli, $_GET['team']);
			$where .= ($where == "" ? " WHERE" : " AND")." team_id = ".$team;
		}
		if (isset($_GET['page'])) {
			$page = mysqli_real_escape_string($mysqli, $_GET['page']);
		}
		if (isset($_GET['ranking'])) {
			$ranking = mysqli_real_escape_string($mysqli, $_GET['ranking']);
		}

		switch ($ranking) {
			case 1:
				$order = " ORDER BY name, last_modified DESC";
				break;
			case 2:
				$order = " ORDER BY gym_points DESC, last_modified DESC";
				break;
			default:
				$order = " ORDER BY last_modified DESC, name";
		}

		$limit = " LIMIT ".($page * 10).",10";

		$req = "SELECT gymdetails.gym_id, name, team_id, gym_points, (CONVERT_TZ(last_modified, '+00:00', '".$time_offset."')) as last_modified
				FROM gymdetails
				LEFT JOIN gym
				ON gymdetails.gym_id = gym.gym_id
				".$where.$order.$limit;

		$result = $mysqli->query($req);
		$gyms = array();
		while ($result && $data = $result->fetch_object()) {
			$pkm = array();
			if ($data->gym_points > 0) {
				$pkm_req = "SELECT DISTINCT gymmember.pokemon_uid, pokemon_id, cp, trainer_name
							FROM gymmember
							LEFT JOIN gympokemon
							ON gymmember.pokemon_uid = gympokemon.pokemon_uid
							WHERE gymmember.gym_id = '". $data->gym_id ."'
							ORDER BY cp DESC";
				$pkm_result = $mysqli->query($pkm_req);
				while ($pkm_result && $pkm_data = $pkm_result->fetch_object()) {
					$pkm[] = $pkm_data;
				}
			}
			$data->pokemon = $pkm;
			unset($data->pokemon_uids);
			$data->gym_id = str_replace('.', '_', $data->gym_id);
			$gyms[] = $data;
		}
		$json = array();
		$json['gyms'] = $gyms;
		$locale = array();
		$json['locale'] = $locale;

		header('Content-Type: application/json');
		echo json_encode($json);

		break;


	case 'gymhistory':
		$gym_id = '';
		$page = '0';
		if (isset($_GET['gym_id'])) {
			$gym_id = mysqli_real_escape_string($mysqli, $_GET['gym_id']);
			$gym_id = str_replace('_', '.', $gym_id);
		}
		if (isset($_GET['page'])) {
			$page = mysqli_real_escape_string($mysqli, $_GET['page']);
		}

		$entries = array();

		if ($gym_id != '') {
			$req = "SELECT gym_id, team_id, gym_points, pokemon_uids, (CONVERT_TZ(last_modified, '+00:00', '".$time_offset."')) as last_modified
					FROM gymhistory
					WHERE gym_id='".$gym_id."'
					ORDER BY last_modified DESC
					LIMIT ".($page * 10).",11";

			$result = $mysqli->query($req);
			while ($result && $data = $result->fetch_object()) {
				$pkm = array();
				if ($data->gym_points == 0) { $data->pokemon_uids = ''; }
				if ($data->pokemon_uids != '') {
					$pkm_uids = explode(',', $data->pokemon_uids);
					$pkm_req = "SELECT DISTINCT pokemon_uid, pokemon_id, cp, trainer_name
								FROM gympokemon
								WHERE pokemon_uid IN ('". implode("','", $pkm_uids) ."')
								ORDER BY cp DESC";
					$pkm_result = $mysqli->query($pkm_req);
					while ($pkm_result && $pkm_data = $pkm_result->fetch_object()) {
						$pkm[$pkm_data->pokemon_uid] = $pkm_data;
					}
				}
				$data->pokemon = $pkm;
				$data->gym_id = str_replace('.', '_', $data->gym_id);
				$entries[] = $data;
			}

			foreach ($entries as $idx => $entry) {
				$entry->gym_points_diff = 0;
				if ($idx < count($entries) - 1) {
					$next_entry = $entries[$idx+1];
					$entry->gym_points_diff = $entry->gym_points - $next_entry->gym_points;
					$entry->class = $entry->gym_points_diff > 0 ? 'gain' : ($entry->gym_points_diff < 0 ? 'loss' : '');
					$entry_pokemon = preg_split('/,/', $entry->pokemon_uids, null, PREG_SPLIT_NO_EMPTY);
					$next_entry_pokemon = preg_split('/,/', $next_entry->pokemon_uids, null, PREG_SPLIT_NO_EMPTY);
					$new_pokemon = array_diff($entry_pokemon, $next_entry_pokemon);
					$old_pokemon = array_diff($next_entry_pokemon, $entry_pokemon);
					foreach ($new_pokemon as $pkm) {
						$entry->pokemon[$pkm]->class = 'new';
					}
					foreach ($old_pokemon as $pkm) {
						$next_entry->pokemon[$pkm]->class = 'old';
					}
				}
				unset($entry->pokemon_uids);
			}

			if (count($entries) > 10) { array_pop($entries); }
		}

		$json = array();
		$json['entries'] = $entries;
		$locale = array();
		$json['locale'] = $locale;

		header('Content-Type: application/json');
		echo json_encode($json);

		break;


	case 'gymshaver':
		$where = '';
		$order = '';
		$page = '0';
		$ranking = '0';
		if (isset($_GET['name']) && $_GET['name'] != '') {
			$gym_name = mysqli_real_escape_string($mysqli, $_GET['name']);
			$where = " WHERE name LIKE '%".$gym_name."%'";
		}
		if (isset($_GET['team']) && $_GET['team'] != '') {
			$team = mysqli_real_escape_string($mysqli, $_GET['team']);
			$where .= ($where == "" ? " WHERE" : " AND")." team_id = ".$team;
		}
		if (isset($_GET['page'])) {
			$page = mysqli_real_escape_string($mysqli, $_GET['page']);
		}
		if (isset($_GET['ranking'])) {
			$ranking = mysqli_real_escape_string($mysqli, $_GET['ranking']);
		}

		switch ($ranking) {
			case 1:
				$order = " ORDER BY name, last_modified_end DESC";
				break;
			case 2:
				$order = " ORDER BY gym_points_end DESC, last_modified_end DESC";
				break;
			default:
				$order = " ORDER BY last_modified_end DESC, name";
		}

		$limit = " LIMIT ".($page * 5).",5";

		$entries = array();

		$req = "SELECT gym_id, name, team_id, gym_points_end, gym_points_start, pokemon_uids_end, pokemon_uids_start, (CONVERT_TZ(last_modified_end, '+00:00', '".$time_offset."')) AS last_modified_end, (CONVERT_TZ(last_modified_start, '+00:00', '".$time_offset."')) AS last_modified_start FROM gymshaving".$where.$order.$limit;

		$result = $mysqli->query($req);
		while ($result && $data = $result->fetch_object()) {
			$pokemon = array();
			$pokemon_end = explode(',', $data->pokemon_uids_end);
			$pokemon_start = explode(',', $data->pokemon_uids_start);
			$new_pokemon = array_diff($pokemon_end, $pokemon_start);
			$old_pokemon = array_diff($pokemon_start, $pokemon_end);
			$all_pokemon = array_merge($pokemon_end, $old_pokemon);
			foreach ($all_pokemon as $pkm) {
				$pokemon[$pkm] = new stdClass();
			}

			$data->gym_points_diff = $data->gym_points_end - $data->gym_points_start;
			$data->class = $data->gym_points_diff > 0 ? 'gain' : ($data->gym_points_diff < 0 ? 'loss' : '');

			$pkm_req = "SELECT pokemon_uid, pokemon_id, cp, trainer_name
						FROM gympokemon
						WHERE pokemon_uid IN ('".implode("','", $all_pokemon)."')";

			$pkm_result = $mysqli->query($pkm_req);
			while ($pkm_result && $pkm_data = $pkm_result->fetch_object()) {
				$pokemon[$pkm_data->pokemon_uid] = $pkm_data;
			}
			foreach ($new_pokemon as $pkm) {
				$pokemon[$pkm]->class = 'new';
			}
			foreach ($old_pokemon as $pkm) {
				$pokemon[$pkm]->class = 'old';
			}

			$data->pokemon = $pokemon;
			unset($data->pokemon_uids_end);
			unset($data->pokemon_uids_start);
			$data->gym_id = str_replace('.', '_', $data->gym_id);
			$entries[] = $data;
		}

		$json = array();
		$json['entries'] = $entries;
		$locale = array();
		$json['locale'] = $locale;

		header('Content-Type: application/json');
		echo json_encode($json);

		break;


	case 'gymshaver_count':
		$req = "SELECT * FROM gymshaving ORDER BY last_modified_end DESC";

		$result = $mysqli->query($req);

		$stats = new stdClass();
		$stats->total = 0;
		$stats->week = 0;
		$stats->day = 0;
		$nextDay = null;
		$nextWeek = null;

		$counts_shaver = array();
		$counts_victim = array();
		while ($result && $data = $result->fetch_object()) {
			if ($stats->total == 0) {
				$nextDay = strtotime('-1 day', strtotime($data->last_modified_end));
				$nextWeek = strtotime('-1 week', strtotime($data->last_modified_end));
			}
			$current = strtotime($data->last_modified_end);
			$stats->total++;
			if ($current > $nextDay) { $stats->day++; }
			if ($current > $nextWeek) { $stats->week++; }
			$pokemon_end = explode(',', $data->pokemon_uids_end);
			$pokemon_start = explode(',', $data->pokemon_uids_start);
			$new_pokemon = array_diff($pokemon_end, $pokemon_start);
			$old_pokemon = array_diff($pokemon_start, $pokemon_end);
			$pkm_req = "SELECT pokemon_uid, trainer_name
						FROM gympokemon
						WHERE pokemon_uid IN ('".implode("','", $new_pokemon)."')";
			$pkm_result = $mysqli->query($pkm_req);
			while ($pkm_result && $pkm_data = $pkm_result->fetch_object()) {
				$counts_shaver[$pkm_data->trainer_name] = $counts_shaver[$pkm_data->trainer_name] ? $counts_shaver[$pkm_data->trainer_name] + 1 : 1;
			}
			$pkm_req = "SELECT pokemon_uid, trainer_name
						FROM gympokemon
						WHERE pokemon_uid IN ('".implode("','", $old_pokemon)."')";
			$pkm_result = $mysqli->query($pkm_req);
			while ($pkm_result && $pkm_data = $pkm_result->fetch_object()) {
				$counts_victim[$pkm_data->trainer_name] = $counts_victim[$pkm_data->trainer_name] ? $counts_victim[$pkm_data->trainer_name] + 1 : 1;
			}
		}

		$shavers = array();
		$trainer_req = "SELECT * FROM trainer WHERE name IN ('".implode("','", array_keys($counts_shaver))."')";
		$trainer_result = $mysqli->query($trainer_req);
		while ($trainer_result && $trainer_data = $trainer_result->fetch_object()) {
				$entry = $trainer_data;
				$entry->count = $counts_shaver[$entry->name];
				$shavers[] = $entry;
		}

		$victims = array();
		$trainer_req = "SELECT * FROM trainer WHERE name IN ('".implode("','", array_keys($counts_victim))."')";
		$trainer_result = $mysqli->query($trainer_req);
		while ($trainer_result && $trainer_data = $trainer_result->fetch_object()) {
				$entry = $trainer_data;
				$entry->count = $counts_victim[$entry->name];
				$victims[] = $entry;
		}

		usort($shavers, function($a, $b) { return $a->count < $b->count; });
		usort($victims, function($a, $b) { return $a->count < $b->count; });

		$json = array();
		$json['shavers'] = array_slice($shavers, 0, 20);
		$json['victims'] = array_slice($victims, 0, 20);
		$json['stats'] = $stats;
		$locale = array();
		$json['locale'] = $locale;

		header('Content-Type: application/json');
		echo json_encode($json);

		break;


	case 'pokemon_slider_init':
		$req 		= "SELECT MIN(disappear_time) AS min, MAX(disappear_time) AS max FROM pokemon";
		$result 	= $mysqli->query($req);
		$bounds		= $result->fetch_object();

		header('Content-Type: application/json');
		echo json_encode($bounds);

		break;


	case 'pokemon_heatmap_points':
		$json="";
		if (isset($_GET['start'])&&isset($_GET['end']) && isset($_GET['pokemon_id'])) {
			$start = Date("Y-m-d H:i",(int)$_GET['start']);
			$end = Date("Y-m-d H:i",(int)$_GET['end']);
			$pokemon_id = mysqli_real_escape_string($mysqli, $_GET['pokemon_id']);
			$where = " WHERE pokemon_id = ".$pokemon_id." "
					. "AND disappear_time BETWEEN '".$start."' AND '".$end."'";
			$req 		= "SELECT latitude, longitude FROM pokemon".$where." ORDER BY disappear_time DESC LIMIT 10000";
			$result 	= $mysqli->query($req);
			$points = array();
			while ($result && $data = $result->fetch_object()) {
				$points[] 	= $data;
			}

			$json = json_encode($points);
		}

		header('Content-Type: application/json');
		echo $json;
		break;


	case 'maps_localization_coordinates':
		$json="";
		$req 		 = "SELECT MAX(latitude) AS max_latitude, MIN(latitude) AS min_latitude, MAX(longitude) AS max_longitude, MIN(longitude) as min_longitude FROM spawnpoint";
		$result 	 = $mysqli->query($req);
		$coordinates = $result->fetch_object();
		
		header('Content-Type: application/json');
		echo json_encode($coordinates);

		break;


	case 'pokemon_graph_data':
		$json="";
		if (isset($_GET['pokemon_id'])) {
			$pokemon_id = mysqli_real_escape_string($mysqli, $_GET['pokemon_id']);
			$req = "SELECT COUNT(*) AS total,
					HOUR(CONVERT_TZ(disappear_time, '+00:00', '".$time_offset."')) AS disappear_hour
					FROM (SELECT disappear_time FROM pokemon WHERE pokemon_id = '".$pokemon_id."' ORDER BY disappear_time LIMIT 10000) AS pokemonFiltered
					GROUP BY disappear_hour
					ORDER BY disappear_hour";
			$result	= $mysqli->query($req);
			$array = array_fill(0, 24, 0);
			while ($result && $data = $result->fetch_object()) {
				$array[$data->disappear_hour] = $data->total;
			}
			// shift array because AM/PM starts at 1AM not 0:00
			$array[] = $array[0];
			array_shift($array);

			$json = json_encode($array);
		}

		header('Content-Type: application/json');
		echo $json;
		break;


	case 'postRequest':
		break;

	default:
		echo "What do you mean?";
		exit();
	break;
}

if ($postRequest!="") {
	switch ($postRequest) {
		case 'pokemon_live':
			$json="";
			if (isset( $_POST['pokemon_id'])) {
				$pokemon_id = mysqli_real_escape_string($mysqli, $_POST['pokemon_id']);
				$inmap_pkms_filter="";
				$where = " WHERE disappear_time >= UTC_TIMESTAMP() AND pokemon_id = ".$pokemon_id;

				$reqTestIv = "SELECT MAX(individual_attack) AS iv FROM pokemon ".$where;
				$resultTestIv 	= $mysqli->query($reqTestIv);
				$testIv = $resultTestIv->fetch_object();
				if (isset( $_POST['inmap_pokemons'])&&( $_POST['inmap_pokemons']!="")) {
					foreach ($_POST['inmap_pokemons'] as $inmap) {
						$inmap_pkms_filter .= "'".$inmap."',";
					}
					$inmap_pkms_filter = rtrim($inmap_pkms_filter, ",");
					$where .= " AND encounter_id NOT IN (".$inmap_pkms_filter.") ";
				}
				if ($testIv->iv!=null && isset( $_POST['ivMin'])&&( $_POST['ivMin']!="")) {
					$ivMin = mysqli_real_escape_string($mysqli, $_POST['ivMin']);
					$where .= " AND ((100/45)*(individual_attack+individual_defense+individual_stamina)) >= (".$ivMin.") ";
				}
				if ($testIv->iv!=null && isset( $_POST['ivMax'])&&( $_POST['ivMax']!="")) {
					$ivMax = mysqli_real_escape_string($mysqli, $_POST['ivMax']);
					$where .= " AND ((100/45)*(individual_attack+individual_defense+individual_stamina)) <=(".$ivMax.") ";
				}
				$req = "SELECT pokemon_id, encounter_id, latitude, longitude, disappear_time,
						(CONVERT_TZ(disappear_time, '+00:00', '".$time_offset."')) AS disappear_time_real,
						individual_attack, individual_defense, individual_stamina, move_1, move_2
						FROM pokemon ".$where."
						ORDER BY disappear_time DESC
						LIMIT 5000";
				$result = $mysqli->query($req);
				$json = array();
				$json['points'] = array();
				$locale = array();
				$locale["ivAttack"] =  $locales->IV_ATTACK;
				$locale["ivDefense"] = $locales->IV_DEFENSE;
				$locale["ivStamina"] =  $locales->IV_STAMINA;
				$json['locale'] = $locale;
				while ($result && $data = $result->fetch_object()) {
					$pokeid=$data->pokemon_id;
					$data->name = $pokemons->pokemon->$pokeid->name;
					if (isset($data->move_1)) {
						$move1 = $data->move_1;
						$data->quick_move = $move->$move1->name;
					} else {
						$data->quick_move = "?";
					}
					if (isset($data->move_2)) {
						$move2 = $data->move_2;
						$data->charge_move = $move->$move2->name;
					} else {
						$data->charge_move = "?";
					}
					$json['points'][] 	= $data;
				}

				$json = json_encode($json);
			}

			header('Content-Type: application/json');

			echo $json;

		break;



		default:
			echo "What do you mean?";
			exit();
		break;
	}
}
