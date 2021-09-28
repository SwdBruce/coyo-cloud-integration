<?php

const FILE_PATH = '';
const BASE_URL = '';
const CLIENT_ID = '';
const CLIENT_SECRET = '';
const USERNAME = '';
const PASSWORD = '';
const SEPARATOR = ',';
const GENERATE_TOKEN_ENDPOINT = BASE_URL . 'api/oauth/token?grant_type=password&username=' . USERNAME . '&password=' . PASSWORD;
const CREATE_USER_ENDPOINT = BASE_URL . 'api/users';

function callAPI(string $method, string $url, ?string $data = null, array $httpheader = null)
{
    header("Content-Type:application/json");
    $curl = curl_init();

    curl_setopt($curl, CURLOPT_HEADER, FALSE);
    if (!empty($httpheader)) {
        curl_setopt($curl, CURLOPT_HTTPHEADER, $httpheader);
    }

    switch ($method)
    {
        case 'POST':
            curl_setopt($curl, CURLOPT_POST, 1);

            if (!empty($data)) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
            break;
        case 'PUT':
            curl_setopt($curl, CURLOPT_PUT, 1);
            break;
        default:
            if (!empty($data)) {
                $url = sprintf("%s?%s", $url, http_build_query($data));
            }
            break;
    }

    // Optional Authentication:
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($curl, CURLOPT_USERPWD, CLIENT_ID . ':' . CLIENT_SECRET);

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($curl);

    curl_close($curl);

    return $result;
}

function generateToken(): object
{
    $tokenKsonResponse = callAPI('POST', GENERATE_TOKEN_ENDPOINT);
    return json_decode($tokenKsonResponse);
}

function createUser(string $accessToken, array $fields) : object
{
    $httpheader = ["Content-Type:application/json"];
    $jsonResponse = callAPI('POST', CREATE_USER_ENDPOINT . '?access_token=' . $accessToken, json_encode($fields), $httpheader);
    return json_decode($jsonResponse);
}

if (($file = fopen(FILE_PATH, 'r')) !== false) {
    header('Content-type: application/json');
    $token = generateToken();
    $createdUsers = [];
    $r = 0;
    $fields = [];
    while (($row = fgetcsv($file, 0, SEPARATOR)) !== false) {
        $firtsName = $row[1];
        $lastName = $row[2];
        $loginName = $row[3];
        $email = $row[4];
        $groups = explode('|', $row[5]);
        $password = $row[6];
        $fields = [
            'firstname' => $firtsName,
            'lastname' => $lastName,
            'loginName' => $loginName,
            'email' => $email,
            'groupIds' => $groups,
            'password' => $password,
            'activo' => true
        ];
        $createdUsers[] = createUser($token->access_token, $fields);
        $r++;
    }
    fclose($file);

    echo json_encode([
        'total_rows' => $r,
        'result' => $createdUsers
    ]);
} else {
    echo 'File not exists';
}