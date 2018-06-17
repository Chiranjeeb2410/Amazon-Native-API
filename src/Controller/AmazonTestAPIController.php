<?php

/**
 * @file
 * Contains \Drupal\test_api\Controller\TestAPIController.
 */

namespace Drupal\affiliates_connect_amazon\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Controller routines for test_api routes.
 */
class TestAPIController extends ControllerBase {

  /**
   * The Guzzle client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * Collect response from affiliate api url.
   *
   * @param string $url
   *   The API endpoint through which the request is to be made.
   *
   * @return \Guzzle\Http\Message\Response
   *   The Guzzle response.
   */
  public function get($url) {

    $client = new Client();

    try {
      $response = $client->get($url);
    }
    catch (RequestException $e) {
      $args = ['%site' => $url, '%error' => $e->getMessage()];
      throw new \RuntimeException($this->t('This %site seems to be broken because of error "%error".', $args));
    }

    return $response;
  }

  /**
   * Fetches product categories.
   *
   * @return array
   *   Collection of categories along with endpoint url.
   */
  public function categories() {

    $associate_id = $this->config('affiliates_connect_amazon.settings')->get('amazon_associate_id');
    $access_key = $this->config('affiliates_connect_amazon.settings')->get('amazon_associate_id');
    $secret_key = $this->config('affiliates_connect_amazon.settings')->get('amazon_secret_key');

    $url = 'http://webservices.amazon.com/onca/xml' . $associate_id . '.xml';

    $results = [];
    foreach(array_chunk($items, 10) as $asins) {
      $request = new AmazonRequest($this->$secret_key, $this->$access_key, $this->$associate_id);
      $request->setOptions([
        'Service' => 'AWSECommerceService',
        'ItemId' => implode(',', $asins),
        'ResponseGroup' => 'Small,Images',
        'Operation' => 'ItemLookup',
      ]);
      $results = array_merge($results, $request->execute()->getResults());
    }
    return $results;

    $response = $client->get($url, [
      'results' => $results,
    ]);
    $contents = new SimpleXMLElement($response->getBody()->getContents());
    $json = json_encode($contents);
    $body = json_decode($json, true);

    $categories = [];
    foreach ($body['apiGroups']['affiliate']['apiLists'] as $key => $value) {
      $categories[$key] = $value['availabletypes']['v1.1.0']['get'];
    }
    return $categories;
  }


#  /**
#   * Callback for `affiliates_connect_amazon/post.json` API method.
#   */
#  public function post_example( Request $request ) {
#    // This condition checks the `Content-type` and makes sure to
#    // decode JSON string from the request body into array.
#    if ( 0 === strpos( $request->headers->get( 'Content-Type' ), 'application/json' ) ) {
#      $data = json_decode( $request->getContent(), TRUE );
#      $request->request->replace( is_array( $data ) ? $data : [] );
#    }
#    $response['data'] = 'Return test data';
#    $response['method'] = 'POST';
#    return new JsonResponse( $response );
#  }

}
