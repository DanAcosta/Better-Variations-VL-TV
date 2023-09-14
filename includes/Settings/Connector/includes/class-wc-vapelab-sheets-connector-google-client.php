<?php

use Google\Client;



class WC_Vapelab_Sheets_Connector_Google_Client
{

    public $client;
   
    public function __construct($env = null)
    {
       
        $suffix = '_vl';
        if(WC_VAPELAB_SHEETS_CONNECTOR_ENV == 'DEV'){
            $suffix = '_dc';
        }

        

        $credentialsPath = plugin_dir_path( dirname( __FILE__ ) ). 'credentials'.$suffix.'.json';
        

        $tokenPath = plugin_dir_path( dirname( __FILE__ ) ). 'token'.$suffix.'.json';
        
        $google_client = new Google\Client();
       
        $google_client->setApplicationName('Google Sheets API PHP Quickstart');
        $google_client->setScopes('https://www.googleapis.com/auth/spreadsheets');
        $google_client->setAuthConfig($credentialsPath);
        $google_client->setAccessType('offline');
        $google_client->setPrompt('select_account consent');
        
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            
            $google_client->setAccessToken($accessToken);
        }
        
        // If there is no previous token or it's expired.
        if ($google_client->isAccessTokenExpired()) {
            // Refresh the token if possible, else fetch a new one.
           
            if ($google_client->getRefreshToken()) {
                $google_client->fetchAccessTokenWithRefreshToken($google_client->getRefreshToken());
               
                
            } else {
                // Request authorization from the user.
                $authUrl = $google_client->createAuthUrl();
                
                $authCode = trim($google_client->getPrompt());

                // Exchange authorization code for an access token.
                $accessToken = $google_client->fetchAccessTokenWithAuthCode($authCode);
                $google_client->setAccessToken($accessToken);

                // Check to see if there was an error.
                if (array_key_exists('error', $accessToken)) {
                    throw new Exception(join(', ', $accessToken));
                }
            }
            // Save the token to a file.
            
            
            file_put_contents($tokenPath, json_encode($google_client->getAccessToken()));

            
        }

        $this->client = $google_client;



    }
    
}