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
        return $this->makeApiRequest('add', ['filename' => fopen($filename, 'r'), 'identifier' => $identifier, 'folder' => $folder]);
    }

    public function update($filename, string $old_identifier, string $new_identifier) {
        return $this->makeApiRequest('update', ['filename' => fopen($filename, 'r'), 'old_identifier' => $old_identifier, 'new_identifier' => $new_identifier]);
    }

    public function block(int $mediaid) {
        return $this->makeApiRequest('block', ['mediaid' => $mediaid]);
    }

    public function delete(int $mediaid) {
        return $this->makeApiRequest('delete', ['mediaid' => $mediaid]);
    }

    public function versions(string $identifier) {
        return $this->makeApiRequest('versions', ['identifier' => $identifier]);
    }

    public function activate(int $mediaid) {
        return $this->makeApiRequest('activate', ['mediaid' => $mediaid]);
    }


    /*
     * Delivery calls
     */
    //    get: /{stash}/{foldername}/{media}/{transformations?}         url:  https://images.goodbaby.eu/cybex/360images/image01/   OR {{--                https://images.goodbaby.eu/dev/baidiefische/budda/w-150+h-100
    //    getid: key, mediaid                                           url:  https://images.goodbaby.eu/getid/1

    // Returns only a string representation of the requested url
    public function getUrl($stash, $folder, $identifier, $transformations = '') {
        return $this->delivery_url . '/' . $stash . '/' . $folder . '/' . $identifier . '/' . $transformations;
    }

    public function get($stash, $folder, $identifier, $transformations = '') {
        $action_url = $stash . '/' . $folder . '/' . $identifier . '/' . $transformations;
        $this->makeDeliveryRequest($action_url);
    }

    public function getid(int $mediaid) {
        $this->makeDeliveryRequest('getid/' . $mediaid);
    }

    private function makeDeliveryRequest(string $action_url) {

        // Prepare API call.
        $guzzleClient = new Client([
            'base_uri' => $this->delivery_url,
            'timeout'  => 4,
        ]);

        $options = [
            'form_params' => [
                'key'   => $this->secret,
            ],
        ];

        // Request to transformer
        $response = $guzzleClient->request('GET', $action_url);

        // Evaluate response and handle errors.
        try {
//             return $response->getBody();
             return $response;

        } catch (Exception $exception) {

            $error       = $exception->getCode() . ': ' . $exception->getMessage();
        }
    }

    private function makeApiRequest(string $action_uri, array $params) {

        // Prepare API call.
        $guzzleClient = new Client([
            'base_uri' => $this->api_url,
            'timeout'  => 4,
        ]);

        $multipart = [
            'multipart' => [
                [
                    'name'      => 'key',
                    'contents'  => $this->secret,
                ]
            ]
        ];

        // add $params to the multipart array
        foreach($params as $key => $value) {
            $multipart['multipart'][] = ['name' => $key, 'contents' => $value];
        }

        // Request to transformer
        $response = $guzzleClient->request('POST', $action_uri, $multipart);

        // Evaluate response and handle errors.
        // We can't only check for 200 here. Will receive 200, 201, 400, 404 from transformer

        try {

            return json_decode($response->getBody(), true);

        } catch (Exception $exception) {

            return $exception->getCode() . ': ' . $exception->getMessage();
        }
    }
}