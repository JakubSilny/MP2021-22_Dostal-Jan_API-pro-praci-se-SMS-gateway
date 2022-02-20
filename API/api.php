<?php

$statusKod = 0;

try {

	header_remove("Set-Cookie");
	header("Access-Control-Allow-Origin: *");

	$hlavicky = apache_request_headers();
	$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
	$url = explode('/', $url);

	if ($_SERVER['REQUEST_METHOD'] == "OPTIONS" && isset($hlavicky["Origin"]) && isset($hlavicky["Access-Control-Request-Method"])) {

		if ($url[2] == "api" && count($url) == 3) {
			header("Access-Control-Max-Age: 86400");
			header('Access-Control-Allow-Methods: POST');
			header('Access-Control-Allow-Headers: *');
		}
		else if ($url[2] == "api" && isset($url[3]) && $url[4] == "status" && $url[3] != "" && count($url) == 5) {
			header("Access-Control-Max-Age: 86400");
			header('Access-Control-Allow-Methods: GET');
			header('Access-Control-Allow-Headers: *');

		}
		else if ($url[2] == "api" && $url[3] == "queue" && count($url) == 4) {
			header("Access-Control-Max-Age: 86400");
			header('Access-Control-Allow-Methods: GET');
			header('Access-Control-Allow-Headers: *');

		}
		else if ($url[2] == "api" && $url[3] == "stats" && count($url) == 4) {
			header("Access-Control-Max-Age: 86400");
			header('Access-Control-Allow-Methods: GET');
			header('Access-Control-Allow-Headers: *');
		}

		http_response_code(204);

	}
	else {

		header('Content-type: application/json');

		$autentizacniKlic = $hlavicky["XXXX"];

		if (empty($autentizacniKlic)) {
			header('WWW-Authenticate: Bearer');

			throw new Exception("Unauthorized", 401);
		}

		$identifikatorUzivatele = $core->sql->fetchValue("
			SELECT `uuid`
			FROM `sms_account`
			WHERE `uuid` = '".$core->sql->escape($autentizacniKlic)."'
				AND active = 'Y'
		");

		if(empty($identifikatorUzivatele)) {

			header('WWW-Authenticate: apiKey');
			throw new Exception("Invalid API key in request header", 401);
		}

		$odpoved = array();

		if($url[2] == "api" && count($url) == 3 && $_SERVER['REQUEST_METHOD'] == "POST") {

			if($_SERVER['CONTENT_TYPE'] != "application/json") {

				throw new Exception("Invalid request payload content type", 400);
			}

			$teloDotazu = file_get_contents("php://input");

			$teloDotazu = json_decode($teloDotazu, true);

			if (!is_array($teloDotazu)) {

				throw new Exception('Bad payload structure, must be formatted in JSON', 400);
			}

			if (!array_key_exists("number", $teloDotazu) || !is_string($teloDotazu["text"]) || !array_key_exists("text", $teloDotazu) ||
			!preg_match('/^(\+420|00420)[1-9][0-9]{2}[0-9]{3}[0-9]{3}$/', $teloDotazu["number"])) {

				throw new Exception("Invalid number and text properties in payload", 400);
			}

			$slovnikDiakritickychPismen = array(
				'ä'=>'a','Ä'=>'A','á'=>'a','Á'=>'A','à'=>'a','À'=>'A','ã'=>'a','Ã'=>'A','â'=>'a','Â'=>'A','Å'=>'A','å'=>'a',
				'č'=>'c','Č'=>'C','ć'=>'c','Ć'=>'C','ď'=>'d','Ď'=>'D','ě'=>'e','Ě'=>'E','é'=>'e','É'=>'E','ë'=>'e','Ë'=>'E',
				'è'=>'e','È'=>'E','ê'=>'e','Ê'=>'E','í'=>'i','Í'=>'I','ï'=>'i','Ï'=>'I','ì'=>'i','Ì'=>'I','î'=>'i','Î'=>'I',
				'ľ'=>'l','Ľ'=>'L','ĺ'=>'l','Ĺ'=>'L','ń'=>'n','Ń'=>'N','ň'=>'n','Ň'=>'N','ñ'=>'n','Ñ'=>'N','ó'=>'o','Ó'=>'O',
				'ö'=>'o','Ö'=>'O','ô'=>'o','Ô'=>'O','ò'=>'o','Ò'=>'O','õ'=>'o','Õ'=>'O','ő'=>'o','Ő'=>'O','ř'=>'r','Ř'=>'R',
				'ŕ'=>'r','Ŕ'=>'R','š'=>'s','Š'=>'S','ś'=>'s','Ś'=>'S','ť'=>'t','Ť'=>'T','ú'=>'u','Ú'=>'U','ů'=>'u','Ů'=>'U',
				'ü'=>'u','Ü'=>'U','ù'=>'u','Ù'=>'U','ũ'=>'u','Ũ'=>'U','û'=>'u','Û'=>'U','ý'=>'y','Ý'=>'Y','ž'=>'z','Ž'=>'Z',
				'ź'=>'z','Ź'=>'Z'           
			);

			$vyfiltrovanyTextSms = strtr($teloDotazu["text"], $slovnikDiakritickychPismen);

			$vyfiltrovanyTextSms = iconv('UTF-8', 'ASCII//TRANSLIT', $vyfiltrovanyTextSms);

			$vyfiltrovanyTextSms = preg_replace('/[^a-zA-Z0-9@£¥_!"#¤%&()*+,.:;<=>?¿§\-\/\n\r ]/', '?', $vyfiltrovanyTextSms);

			if (strlen($vyfiltrovanyTextSms) == 0) {

				throw new Exception("Text property cannot be empty", 400);
			}
			else if (strlen($vyfiltrovanyTextSms) > 160) {

				throw new Exception("Text property length in payload is too long", 400);
			}

			$core->sql->query("
				INSERT INTO sms_queue 
				SET
					`account` = '".$core->sql->escape($identifikatorUzivatele)."',
					`created` = NOW(),
					`to` = '".$core->sql->escape($teloDotazu["number"])."',
					`body` = '".$core->sql->escape($vyfiltrovanyTextSms)."'
			");

			$identifikatorNoveVytvoreneSms = $core->sql->insert_id();

			$odpoved = array("id" => $identifikatorNoveVytvoreneSms);
			$statusKod = 201;
		}

		else if($url[2] == "api" && isset($url[3]) && $url[4] == "status" && $url[3] != "" && count($url) == 5 && $_SERVER['REQUEST_METHOD'] == "GET") {

			if (ctype_digit($url[3]) == false || $url[3] == "0") {

				throw new Exception("Badly entered smsId parameter in url path", 400);
			}

			$odpoved = $core->sql->fetchArray("
				SELECT 
					`id`, 
					`status`, 
					`sent`, 
					`created`
				FROM `sms_queue` 
				WHERE `account` = '".$core->sql->escape($identifikatorUzivatele)."' AND `id` = ".(int) $url[3]."
			");

			if (empty($odpoved)) {

				throw new Exception("SMS was not found", 404);
			}

			$statusKod = 200;
		}

		else if($url[2] == "api" && $url[3] == "queue" && count($url) == 4 && $_SERVER['REQUEST_METHOD'] == "GET") {

			$odpoved = $core->sql->toArray("
				SELECT 
					`id`, 
					`body` AS `text`, 
					`status`, 
					`to` AS `number`, 
					`created`, 
					`sent`
				FROM `sms_queue`
				WHERE `account` = '".$core->sql->escape($identifikatorUzivatele)."' AND `status` = 'PENDING'
				ORDER BY `created` DESC 
			");

			$statusKod = 200;
		}

		else if ($url[2] == "api" && $url[3] == "stats" && count($url) == 4 && $_SERVER['REQUEST_METHOD'] == "GET") {

			$odpoved = $core->sql->toArray("
				SELECT 
					DATE(`sent`) AS dateOfSending,
					COUNT(`id`) AS numberOfSentSms
				FROM `sms_queue` 
				WHERE `status` = 'SENT' AND `account` = '".$core->sql->escape($identifikatorUzivatele)."' AND DATE(`sent`) >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
				GROUP BY DATE(`sent`)
				ORDER BY DATE(`sent`) DESC 
			");

			$statusKod = 200;
		}

		else {
		
			throw new Exception("Entered endpoint is not known", 400);
		}

		http_response_code($statusKod);
		echo json_encode($odpoved);
	}
}

catch(Exception $e) {

	$statusKod = $e->getCode();
	
	if($statusKod == 200) {

		$statusKod = 500;
	}

	http_response_code($statusKod);
	echo json_encode(array(
		"error" => $e->getMessage()
	));
}

exit;

?>