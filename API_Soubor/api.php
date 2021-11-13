<?php

header('Content-type: application/json');
header("Access-Control-Allow-Origin: *");
header("Allow: GET, POST");
header("Access-Control-Max-Age: 3600");
header('Access-Control-Allow-Methods: GET, POST');

try {

	$headers = apache_request_headers();
    $key = $headers["X-API-Key"];

    if (empty($key)) {
        throw new Exception("Unauthorized", 401);
    }

          $value = $core->sql->fetchValue("
        SELECT `uuid`
        FROM `sms_account`
        WHERE `uuid` = '".$core->sql->escape($key)."'
        ");


    if($value == null) {
        header('WWW-Authenticate: Bearer');
        throw new Exception("Neplátný nebo chybějící API key v headeru requestu.", 401);
    }

    header('Access-Control-Allow-Headers: X-API-Key, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method, Access-Control-Allow-Origin');
   
	$result = array();

	$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = explode( '/', $uri );


	if($uri[2] == "api" && $_SERVER['REQUEST_METHOD']=="POST") {

        if($_SERVER['CONTENT_TYPE'] != "application/json") {
            throw new Exception("Neplatný typ obsahu payloadu", 400);
        }

        $body = file_get_contents("php://input");

        $object = json_decode($body, true);
 
        if (!is_array($object)) {
            throw new Exception('Špatná struktura payloadu', 400);
        }

        if (!array_key_exists("number", $object) || !is_string($object["text"]) || strlen($object["text"]) > 160 || !array_key_exists("text", $object) || !preg_match('/^(\+420|00420)[1-9][0-9]{2}[0-9]{3}[0-9]{3}$/', $object["number"])) {

            throw new Exception("Neplatný text nebo number v body requestu", 400);
        }

        $core->sql->query("
        INSERT INTO sms_queue SET
            account = '{$value}',
            `created` = NOW(),
            `to` = '{$core->sql->escape($object["number"])}',
            `body` = '{$core->sql->escape($object["text"])}'
    ");

        $lastAutoIncrement = $core->sql->insert_id();

        $result = array("id" => $lastAutoIncrement);

	} else if($uri[2] == "api" && isset($uri[3]) && $uri[4] == "status" && $uri[3] != "" && count($uri) == 5 && $_SERVER['REQUEST_METHOD']=="GET") {

        if (ctype_digit($uri[3]) == false || $uri[3] == "0") {
            throw new Exception("Špatně zadaný query parametr v URL cestě.", 400);
        }

        $result = $core->sql->fetchArray("
            SELECT id, status, sent, created
            FROM sms_queue 
            WHERE account = '{$value}'
            AND id = ".(int) $uri[3]."
            ");

        if ($result == null) {
            throw new Exception("SMS nebyla nalezena.", 404);
        }
	}
      else if($uri[2] == "api" && $uri[3] == "queue" && $_SERVER['REQUEST_METHOD']=="GET") {

        $result = $core->sql->toArray("
            SELECT id, body, status, `to` AS `num`, created, sent
            FROM sms_queue
            WHERE account = '{$value}'
            ORDER BY created DESC 
            ");

    }
     else if ($uri[2] == "api" && $uri[3] == "stats" && $_SERVER['REQUEST_METHOD']=="GET") {

        $result = $core->sql->toArray("
            SELECT DATE(sent) AS dateOfSending,
            COUNT(id) AS numberOfSentSms
            FROM sms_queue 
            WHERE status = 'SENT' 
            AND account = '{$value}'
            GROUP BY DATE(sent) 
            HAVING COUNT(id) > 0 
            ORDER BY DATE(sent) DESC 
            LIMIT 100
            ");



    } else {
		throw new Exception("Neznama case", 400);
	}

	http_response_code(200);
    echo json_encode($result);
} catch(Exception $e) {
	$errorCode = $e->getCode();
	if($errorCode == 200) {
		$errorCode = 500;
	}
	http_response_code($errorCode);
	echo json_encode(array(
		"error" => $e->getMessage()
	));
}
exit;

?>