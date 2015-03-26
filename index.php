<?php

/*

    MINEMOD MULTISOLDER VERSION 0.1
    Links multiple solders together and acts as a single solder

    Limitations
    Only works with api/modpack queries, api/mod queries will 404
    
    Copyright Louis Knight-Webb 2015
    ALL RIGHTS RESERVED
    Distribution of the software in any form is only allowed with explicit, prior permission from the owner

*/

require 'Slim/Slim.php';
\Slim\Slim::registerAutoloader();

$config['solders'] = array(
    'minemod' => 'http://151.80.159.35/',
    'tpm' => 'http://solder.endermedia.com/'
);

$config['modpacks'] = array(
    'minemod-network',
    'simplify-for-minecraft',
    'steves-galaxy',
    'realm-of-mianite',
    'a-rogues-journey',
    'the-1710-pack',
    'dev-ills-modernized',
    '7-dayz-to-mine'
);

$config['selfurl'] = 'http://151.80.159.35/';

class SolderRequest {

    private $url;

    public function __construct($url) {

        $this->url = $url;

    }

    public function jsonRequest($req) {

        $ch = curl_init($this->url . $req);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $json = '';
        if( ($json = curl_exec($ch) ) === false)
        {
            return false;
        }
        else
        {
            return $json;
        }
        curl_close($ch);
        
    }

}

class SolderStore {

    public $url;
    public $request;
    public $responses;
    public $modpacks;

    public function __construct($request) {
        $this->request = $request;
    }

    public function populateModpacks() {
        $this->responses['json:api/modpack'] = $this->request->jsonRequest('api/modpack?include=full');
        $this->modpacks = json_decode($this->responses['json:api/modpack'], 1)['modpacks'];
    }

}

$service = new \Slim\Slim();

$service->get('/', function () {

    global $config;

    echo json_encode(array(
        'api'       =>  'TechnicSolder',
        'version'   =>  'MultiSolder 0.1',
        'stream'    =>  'dev'
    ));

});

$service->get('/modpack/?', function () {

    global $config;

    $solderStores;

    $combinedModpacks = array();

    foreach($config['solders'] as $solderName=>$solder) {
        $solderStores[$solderName] = new SolderStore(new SolderRequest($solder));
        $solderStores[$solderName]->populateModpacks();
        foreach($solderStores[$solderName]->modpacks as $ref=>$modpack) {
            if(!in_array($modpack['name'], $config['modpacks'])) {
                unset($solderStores[$solderName]->modpacks[$ref]);
            }
            //print_r($modpack);
        }
        $combinedModpacks = array_merge_recursive($combinedModpacks, $solderStores[$solderName]->modpacks);
    }
    
    echo json_encode(array(
        'modpacks'  =>  $combinedModpacks,
        'mirror_url'=>  $config['selfurl']
    ));
    
});

$service->get('/modpack/:slug/?', function ($slug) {

    global $config;

    $solderStores;

    $combinedModpacks = array();

    foreach($config['solders'] as $solderName=>$solder) {
        $solderStores[$solderName] = new SolderStore(new SolderRequest($solder));
        $solderStores[$solderName]->populateModpacks();
        if(array_key_exists($slug, $solderStores[$solderName]->modpacks)) {
            
            $modpack_slug_request = new SolderRequest($solder);

            echo $modpack_slug_request->jsonRequest('api/modpack/' . $slug);

        }
    }

});

$service->get('/modpack/:slug/:build/?', function ($slug, $build) {

    global $config;

    $solderStores;

    $combinedModpacks = array();

    foreach($config['solders'] as $solderName=>$solder) {
        $solderStores[$solderName] = new SolderStore(new SolderRequest($solder));
        $solderStores[$solderName]->populateModpacks();
        if(array_key_exists($slug, $solderStores[$solderName]->modpacks)) {
            
            $modpack_slug_request = new SolderRequest($solder);

            echo $modpack_slug_request->jsonRequest('api/modpack/' . $slug . '/' . $build);

        }
    }

});


$service->contentType('application/json');
$service->run();