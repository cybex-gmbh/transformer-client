<?php


namespace Cybex\Transformer;

use GuzzleHttp\Client;
use Exception;

class Transformer
{
    protected $secret;
    protected $api_url;
    protected $delivery_url;

    public function __construct(string $key, string $api_url, string $delivery_url)
    {
        if (empty($key)) {
            throw new \InvalidArgumentException('Transformer:: No secret key provided.');
        }

        $this->secret       = $key;
        $this->api_url      = $api_url;
        $this->delivery_url = $delivery_url;
    }

    /*
     * Api calls
     */
    //    add: key, filename, identifier, folder,                       url:  https://transformer.goodbaby.eu/api/v1/add
    //    update: key, filename, old_identifier, new_identifier         url:  https://transformer.goodbaby.eu/api/v1/update
    //    block: key, mediaid                                           url:  https://transformer.goodbaby.eu/api/v1/block
    //    delete: key, mediaid                                          url:  https://transformer.goodbaby.eu/api/v1/delete
    //    versions: key, identifier                                     url:  https://transformer.goodbaby.eu/api/v1/versions
    //    activate: key, mediaid                                        url:  https://transformer.goodbaby.eu/api/v1/activate

    public function add($filename, string $identifier, string $folder) {
        $this->makeRequest('add', ['filename' => $filename, 'identifier' => $identifier, 'folder' => $folder]);
    }

    public function update($filename, string $old_identifier, string $new_identifier) {
        $this->makeRequest('update', ['filename' => $filename, 'old_identifier' => $old_identifier, 'new_identifier' => $new_identifier]);
    }

    public function block(int $mediaid) {
        $this->makeRequest('block', ['mediaid' => $mediaid]);
    }

    public function delete(int $mediaid) {
        $this->makeRequest('delete', ['mediaid' => $mediaid]);
    }

    public function versions(string $identifier) {
        $this->makeRequest('versions', ['identifier' => $identifier]);
    }

    public function activate(int $mediaid) {
        $this->makeRequest('activate', ['mediaid' => $mediaid]);
    }


    /*
     * Delivery calls
     */
    //    get: /{stash}/{foldername}/{media}/{transformations?}         url:  https://images.goodbaby.eu/cybex/360images/image01/   OR {{--                https://images.goodbaby.eu/dev/baidiefische/budda/w-150+h-100
    //    getid: key, mediaid                                           url:  https://images.goodbaby.eu/getid/1


    public function get($stash, $folder, $identifier, $transformations = '') {
        $action_url = $stash . "/" . $folder . "/" . $identifier . "/" . $transformations;
        $this->makeRequest($action_url, [], true);
    }

    public function getid(int $mediaid) {
        $this->makeRequest('getid/' . $mediaid, [], true);
    }



    private function makeRequest(string $action_uri, array $params, $delivery_url = false) {

        $base_url = $this->api_url;
        $request_method = 'POST';

        if($delivery_url) {
            $base_url = $this->$delivery_url;
            $request_method = 'GET';
        }

        // Prepare API call.
        $guzzleClient = new Client([
            'base_uri' => $base_url,
            'timeout'  => 4,
        ]);

        $options = [
            'form_params' => [
                'key'   => $this->secret,
            ],
        ];

        // add $params to the options array
        if(count($params) > 0 ) {
            array_push($options['form_params'], $params);
        }

        // Request to transformer
        if($request_method == 'POST') {
            $response = $guzzleClient->request($request_method, $action_uri, $options);
        } else {
            $response = $guzzleClient->request($request_method, $action_uri);
        }

        // Evaluate response and handle errors.
        // We can't only check for 200 here. Will receive 200, 201, 400, 404 from transformer
//        if ($response->getStatusCode() == 200) {

        try {
            $json = $response->getBody();

            if ($json && $json['success']) {


            } else {
                $error       = $json['message'];
            }
        } catch (Exception $exception) {

            $error       = $exception->getCode() . ': ' . $exception->getMessage();
        }
    }
}