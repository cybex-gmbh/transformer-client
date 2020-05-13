<?php


namespace Cybex\Transformer;

use Exception;
use GuzzleHttp\Client;

class Transformer
{
    protected string $secret;
    protected string $api_url;
    protected int    $api_timeout;
    protected string $delivery_url;
    protected int    $delivery_timeout;

    public function __construct(string $key, string $api_url, string $delivery_url, int $api_timeout = 30, int $delivery_timeout = 30)
    {
        if (empty($key)) {
            throw new \InvalidArgumentException('Transformer:: No secret key provided.');
        }

        $this->secret           = $key;
        $this->api_url          = $api_url;
        $this->api_timeout      = $api_timeout;
        $this->delivery_url     = $delivery_url;
        $this->delivery_timeout = $delivery_timeout;
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

    public function add($filename, string $identifier, string $folder)
    {
        return $this->makeApiRequest('add', ['filename' => fopen($filename, 'r'), 'identifier' => $identifier, 'folder' => $folder]);
    }

    public function update($filename, string $old_identifier, string $new_identifier)
    {
        return $this->makeApiRequest('update', ['filename' => fopen($filename, 'r'), 'old_identifier' => $old_identifier, 'new_identifier' => $new_identifier]);
    }

    public function block(int $mediaid)
    {
        return $this->makeApiRequest('block', ['mediaid' => $mediaid]);
    }

    public function delete(int $mediaid)
    {
        return $this->makeApiRequest('delete', ['mediaid' => $mediaid]);
    }

    public function versions(string $identifier)
    {
        return $this->makeApiRequest('versions', ['identifier' => $identifier]);
    }

    public function activate(int $mediaid)
    {
        return $this->makeApiRequest('activate', ['mediaid' => $mediaid]);
    }


    /*
     * Delivery calls
     */
    //    get: /{stash}/{foldername}/{media}/{transformations?}         url:  https://images.goodbaby.eu/cybex/360images/image01/   OR {{--                https://images.goodbaby.eu/dev/baidiefische/budda/w-150+h-100
    //    getid: key, mediaid                                           url:  https://images.goodbaby.eu/getid/1

    /**
     * Returns the compiled Url for requested Image by Stash, Folder, Identifier and optional Transformations.
     *
     * @param string $stash
     * @param string $folder
     * @param string $identifier
     * @param array  $transformations
     *
     * @return string
     */
    public function getUrl(string $stash, string $folder, string $identifier, array $transformations = []): string
    {
        $url = sprintf('%s/%s/%s/%s/%s', $this->delivery_url, $folder, $identifier, $this->getTransformationsAsString($transformations));

        // Remove possible double Slashes in the Url and strip trailing Slashes, when no Transformations are given.
        return rtrim(preg_replace('/\/{2,}/', '/', $url), '/');
    }

    /**
     * Uses the getUrl-Method to generate the desired Url and Requests it from the Transformer Delivery-Endpoints.
     *
     * @param string $stash
     * @param string $folder
     * @param string $identifier
     * @param array  $transformations
     *
     * @return \Psr\Http\Message\ResponseInterface|string
     */
    public function get(string $stash, string $folder, string $identifier, array $transformations = [])
    {
        return $this->makeDeliveryRequest(call_user_func_array([$this, 'getUrl'], func_get_args()));
    }

    /**
     * Returns an Image based on the given Media-Id.
     *
     * @param int $mediaid
     *
     * @return \Psr\Http\Message\ResponseInterface|string
     */
    public function getid(int $mediaid)
    {
        return $this->makeDeliveryRequest(sprintf('getid/%d', $mediaid));
    }

    /**
     * Uses Guzzle to call the Transformer Delivery-API, which usually return an Image.
     *
     * @param string $action_url
     *
     * @return \Psr\Http\Message\ResponseInterface|string
     */
    protected function makeDeliveryRequest(string $action_url)
    {

        // Prepare API call.
        $guzzleClient = new Client([
            'base_uri' => $this->delivery_url,
            'timeout'  => $this->delivery_timeout,
        ]);

        $options = [
            'form_params' => [
                'key' => $this->secret,
            ],
        ];

        // Request to transformer
        $response = $guzzleClient->request('GET', $action_url);

        // Evaluate response and handle errors.
        try {
//             return $response->getBody();
            return $response;

        } catch (Exception $exception) {

            return $exception->getCode() . ': ' . $exception->getMessage();
        }
    }

    /**
     * Uses Guzzle to call the Transformer-API, which requires to send Parameters as multi-part and expects a JSON-Response from the Server.
     *
     * @param string $action_uri
     * @param array  $params
     *
     * @return mixed|string
     */
    protected function makeApiRequest(string $action_uri, array $params)
    {

        // Prepare API call.
        $guzzleClient = new Client([
            'base_uri' => $this->api_url,
            'timeout'  => $this->api_timeout,
        ]);

        $multipart = [
            'multipart' => [
                [
                    'name'     => 'key',
                    'contents' => $this->secret,
                ],
            ],
        ];

        // add $params to the multipart array
        foreach ($params as $key => $value) {
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

    /**
     * Get the String-Representation of a Transformations-Array, while filtering invalid settings.
     *
     * @param array $transformations
     *
     * @return string
     */
    protected function getTransformationsAsString(array $transformations): string
    {
        $transformationStrings = [];

        foreach ($transformations as $transformation => $value) {
            switch ($transformation) {
                case 'width':
                    if ($value = $this->getValidatedInteger($value)) {
                        $transformationStrings[] = sprintf('w-%d', $value);
                    }
                    break;
                case 'height':
                    if ($value = $this->getValidatedInteger($value)) {
                        $transformationStrings[] = sprintf('h-%d', $value);
                    }
                    break;
                case 'format':
                    if (in_array($value, ['png', 'jpg', 'gif'], true)) {
                        $transformationStrings = sprintf('f-%s', $value);
                    }
                default:
                    // Skip Invalid Transformation.
            }
        }

        return implode('+', $transformationStrings);
    }

    /**
     * Get the filtered and validated Integer of the given value, or null if it is not an integer.
     *
     * @param $value
     *
     * @return int|null
     */
    protected function getValidatedInteger($value): ?int
    {
        $filteredInteger = filter_var($value, FILTER_VALIDATE_INT);

        return is_int($filteredInteger) ? $filteredInteger : null;
    }
}