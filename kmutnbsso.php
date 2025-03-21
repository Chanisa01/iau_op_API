<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

session_start();
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


class kmutnbsso
{
    private $clientId;
    private $clientSecret;
    private $redirectUri;
    private $authUrl;
    private $tokenUrl;
    private $resourceUrl;

    public function __construct()
    {
        // Initialize with OAuth2 provider details
        //residence
        $this->clientId = 'm4F26kNf1gavGOtbdzdagO1FbZj0aR68'; //ขอจาก sso
        $this->clientSecret = 'RBqsfWeDJmCXnSSImZCAsrvHMUgUy6qP1hUlsjYh3vzCSHl5LK072fFp5IONHlIi'; //ขอจาก sso
        // $this->clientId = 'WptY2qDxGi1PtvMzsUBtyNoIPsv4sVMi'; //ขอจาก sso new
        // $this->clientSecret = 'rYGLzJegnjcfBnuWCaDtBidbSzLr2XlhZRxRm2jiw8VATqZK31VXNuMC6r0AgOBf'; //ขอจาก sso new
        // $this->redirectUri = 'https://finance.op.kmutnb.ac.th/callback.php';
        $this->redirectUri = 'http://localhost/finance/callback.php';
        $this->authUrl = 'https://sso.kmutnb.ac.th/auth/authorize';
        $this->tokenUrl = 'https://sso.kmutnb.ac.th/auth/token';
        $this->resourceUrl = 'https://sso.kmutnb.ac.th/resources/userinfo';
    }

    // Method to redirect user to the authorization page
    public function getAuthorizationUrl()
    {
        // Generate authorization URL
        $state = bin2hex(openssl_random_pseudo_bytes(16));  // Generate random state to protect against CSRF
        $_SESSION['oauth2state'] = $state;

        $authorizationUrl = $this->authUrl . '?' . http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'state' => $state,
            'scope' => 'profile pid personnel_info' // Adjust the scope as needed
        ]);
      
        // echo $authorizationUrl;
        // Redirect user to OAuth2 authorization URL
        header('Location: ' . $authorizationUrl);
        exit;
    }

    // Method to handle the OAuth2 callback and exchange code for an access token
    public function handleCallback()
    {        
               
        // Check if the state parameter matches
        if (empty($_GET['state']) || $_GET['state'] !== $_SESSION['oauth2state']) {
            unset($_SESSION['oauth2state']);
            exit('Invalid state');
        }

        // Exchange authorization code for an access token
        if (isset($_GET['code'])) {

            $accessToken = $this->getAccessToken($_GET['code']);
            // print_r($accessToken);
            // Get user details using the access token
            $userDetails = $this->getUserDetails($accessToken);

            return $userDetails;
        } else {
            return false;
            exit('Authorization code not provided');
        }
    }

    // Method to exchange the authorization code for an access token
    private function getAccessToken($authorizationCode)
    {
        // check curl enabled
        if (!function_exists('curl_init')) {
            exit('cURL not enabled');
        }else{
            // echo 'cURL enabled';
        }
        
        $postData = [
            'grant_type' => 'authorization_code',
            'code' => $authorizationCode,
            'redirect_uri' => $this->redirectUri,
            // 'client_id' => $this->clientId,
            // 'client_secret' => $this->clientSecret
        ];

        $headers = array(
            "Content-Type: application/x-www-form-urlencoded",
            "Authorization: Basic " . base64_encode($this->clientId . ":" . $this->clientSecret),
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->tokenUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);       
        
        if (curl_errno($ch)) {
            echo 'cURL error: ' . curl_error($ch);
            exit();
        }
        curl_close($ch);
        
        // // Debugging: Print the response from the OAuth server

        
        $response = json_decode($response, true);
        // print_r($response);
        
        if (isset($response['access_token'])) {
            return $response['access_token'];
        } else {
            // Debugging: Print error message
            // var_dump($response);
            exit();
            // exit('Failed to obtain access token');
        }
    }


    // Method to fetch user details using the access token
    private function getUserDetails($accessToken)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->resourceUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken
        ]);

        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }

    public function showError($error, $error_description)
    {
        echo '<div style="color:red"><strong>Error:</strong> ' . $error . ': ' . $error_description . '</div>';
    }

   
}
