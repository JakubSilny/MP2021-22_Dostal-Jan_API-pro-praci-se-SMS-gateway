<?php

header_remove("Set-Cookie");
header("Access-Control-Allow-Origin: *");

try {

    $headers = apache_request_headers();
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = explode('/', $uri);

    if ($_SERVER['REQUEST_METHOD'] == "OPTIONS" && isset($headers["Origin"]) && isset($headers["Access-Control-Request-Method"])) {

        if ($uri[2] == "api" && count($uri) == 3) {
            header("Access-Control-Max-Age: 86400");
            header('Access-Control-Allow-Methods: POST');
            header('Access-Control-Allow-Headers: *');
            http_response_code(204);
        }
        else if ($uri[2] == "api" && isset($uri[3]) && $uri[4] == "status" && $uri[3] != "" && count($uri) == 5) {
            header("Access-Control-Max-Age: 86400");
            header('Access-Control-Allow-Methods: GET');
            header('Access-Control-Allow-Headers: *');
            http_response_code(204);

        }
        else if ($uri[2] == "api" && $uri[3] == "queue" && count($uri) == 4) {
            header("Access-Control-Max-Age: 86400");
            header('Access-Control-Allow-Methods: GET');
            header('Access-Control-Allow-Headers: *');
            http_response_code(204);

        }
        else if ($uri[2] == "api" && $uri[3] == "stats" && count($uri) == 4) {
            header("Access-Control-Max-Age: 86400");
            header('Access-Control-Allow-Methods: GET');
            header('Access-Control-Allow-Headers: *');
            http_response_code(204);
        }
        else {

            http_response_code(204);
        }
    }
    else {

        header('Content-type: application/json');

        $key = $headers["XXXX"];

        if (empty($key)) {
            header('WWW-Authenticate: Bearer');

            throw new Exception("Unauthorized", 401);
        }

        $value = $core->sql->fetchValue("
            SELECT `uuid`
            FROM `sms_account`
            WHERE `uuid` = '".$core->sql->escape($key)."'
                AND active = 'Y'
        ");

        if(empty($value)) {

            header('WWW-Authenticate: apiKey');
            throw new Exception("Invalid API key in request header", 401);
        }

        $result = array();
        $code = 0;

        if($uri[2] == "api" && count($uri) == 3 && $_SERVER['REQUEST_METHOD'] == "POST") {

            if($_SERVER['CONTENT_TYPE'] != "application/json") {

                throw new Exception("Invalid request payload content type", 400);
            }

            $body = file_get_contents("php://input");

            $object = json_decode($body, true);

            if (!is_array($object)) {

                throw new Exception('Bad payload structure, must be formatted in JSON', 400);
            }

            if (!array_key_exists("number", $object) || !is_string($object["text"]) || !array_key_exists("text", $object) ||
            !preg_match('/^(\+420|00420)[1-9][0-9]{2}[0-9]{3}[0-9]{3}$/', $object["number"])) {

                throw new Exception("Invalid number and text properties in payload", 400);
            }

            $trans = array(
                'ä'=>'a','Ä'=>'A','á'=>'a','Á'=>'A','à'=>'a','À'=>'A','ã'=>'a','Ã'=>'A','â'=>'a','Â'=>'A','Å'=>'A','å'=>'a',
                'č'=>'c','Č'=>'C','ć'=>'c','Ć'=>'C','ď'=>'d','Ď'=>'D','ě'=>'e','Ě'=>'E','é'=>'e','É'=>'E','ë'=>'e','Ë'=>'E',
                'è'=>'e','È'=>'E','ê'=>'e','Ê'=>'E','í'=>'i','Í'=>'I','ï'=>'i','Ï'=>'I','ì'=>'i','Ì'=>'I','î'=>'i','Î'=>'I',
                'ľ'=>'l','Ľ'=>'L','ĺ'=>'l','Ĺ'=>'L','ń'=>'n','Ń'=>'N','ň'=>'n','Ň'=>'N','ñ'=>'n','Ñ'=>'N','ó'=>'o','Ó'=>'O',
                'ö'=>'o','Ö'=>'O','ô'=>'o','Ô'=>'O','ò'=>'o','Ò'=>'O','õ'=>'o','Õ'=>'O','ő'=>'o','Ő'=>'O','ř'=>'r','Ř'=>'R',
                'ŕ'=>'r','Ŕ'=>'R','š'=>'s','Š'=>'S','ś'=>'s','Ś'=>'S','ť'=>'t','Ť'=>'T','ú'=>'u','Ú'=>'U','ů'=>'u','Ů'=>'U',
                'ü'=>'u','Ü'=>'U','ù'=>'u','Ù'=>'U','ũ'=>'u','Ũ'=>'U','û'=>'u','Û'=>'U','ý'=>'y','Ý'=>'Y','ž'=>'z','Ž'=>'Z',
                'ź'=>'z','Ź'=>'Z'           
            );

            $text = strtr($object["text"], $trans);

            $converted = iconv('UTF-8', 'ASCII//TRANSLIT', $text);

            $filtered = preg_replace('/[^a-zA-Z0-9@£¥_!"#¤%&()*+,.:;<=>?¿§\-\/\n\r ]/', '?', $converted);

            if (strlen($filtered) > 160) {

                throw new Exception("Text property length in payload is too long", 400);
            }

            $core->sql->query("
                INSERT INTO sms_queue 
                SET
                    `account` = '{$value}',
                    `created` = NOW(),
                    `to` = '{$core->sql->escape($object["number"])}',
                    `body` = '{$core->sql->escape($filtered)}'
            ");

            $lastAutoIncrement = $core->sql->insert_id();

            $result = array("id" => $lastAutoIncrement);
            $code = 201;
        }

        else if($uri[2] == "api" && isset($uri[3]) && $uri[4] == "status" && $uri[3] != "" && count($uri) == 5 && $_SERVER['REQUEST_METHOD'] == "GET") {

            if (ctype_digit($uri[3]) == false || $uri[3] == "0") {

                throw new Exception("Badly entered smsId parameter in url path", 400);
            }

            $result = $core->sql->fetchArray("
                SELECT 
                    `id`, 
                    `status`, 
                    `sent`, 
                    `created`
                FROM `sms_queue` 
                WHERE `account` = '{$value}' AND `id` = ".(int) $uri[3]."
            ");

            if (empty($result)) {

                throw new Exception("Sms was not found", 404);
            }

            $code = 200;
        }

        else if($uri[2] == "api" && $uri[3] == "queue" && count($uri) == 4 && $_SERVER['REQUEST_METHOD'] == "GET") {

            $result = $core->sql->toArray("
                SELECT 
                    `id`, 
                    `body`, 
                    `status`, 
                    `to` AS num, 
                    `created`, 
                    `sent`
                FROM `sms_queue`
                WHERE `account` = '{$value}' AND `status` = 'PENDING'
                ORDER BY `created` DESC 
            ");

            $code = 200;
        }

        else if ($uri[2] == "api" && $uri[3] == "stats" && count($uri) == 4 && $_SERVER['REQUEST_METHOD'] == "GET") {

            $result = $core->sql->toArray("
                SELECT 
                    DATE(`sent`) AS dateOfSending,
                    COUNT(`id`) AS numberOfSentSms
                FROM `sms_queue` 
                WHERE `status` = 'SENT' AND `account` = '{$value}' AND DATE(`sent`) >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
                GROUP BY DATE(`sent`) HAVING COUNT(id) > 0 
                ORDER BY DATE(`sent`) DESC 
                LIMIT 100
            ");

            $code = 200;
        }

        else {
        
            throw new Exception("Entered endpoint is not known", 400);
        }

        http_response_code($code);
        echo json_encode($result);
    }
}

catch(Exception $e) {

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