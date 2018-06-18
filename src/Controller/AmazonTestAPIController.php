<?php

/**
 * @file
 * Contains \Drupal\test_api\Controller\TestAPIController.
 */

namespace Drupal\affiliates_connect_amazon\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Drupal\affiliates_connect\Entity\AffiliatesProduct;
use Drupal\affiliates_connect_amazon\AmazonAPIRequest;

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
   * @var string
   *   Cache for the Amazon PA API's access key.
   */
  protected $access_key;

  /**
   * @var string
   *   Cache for the Amazon PA API's secret key.
   */
  protected $secret_key;

  /**
   * @var string
   *   Cache for the Amazon PA API's Associate ID.
   */
  protected $associate_id;

  /**
   * Provides an Amazon object for calling the Amazon API.
   *
   * @param string $associate_id
   *   The Amazon PA API's Associate ID.
   *
   * @param string $access_key
   *   Access key to use for all API requests.
   *
   * @param string $secret_key
   *   Secret to use for all API requests.
   */
  public function __construct($associate_id, $access_key = '', $secret_key = '') {
    $this->associate_id = $associate_id;
    if (empty($access_key)) {
      $this->access_key = self::getAccessKey();
    }
    else {
      $this->access_key = $access_key;
    }
    if (empty($secret_key)) {
      $this->secret_key = self::getSecretKey();
    }
    else {
      $this->secret_key = $access_key;
    }
  }

  /**
   * Returns the secret key needed for API calls.
   */
  public function getSecretKey() {
    $secret = \Drupal::config('affiliates_connect_amazon.settings')->get('amazon_secret_key');
    if ($secret) {
      return $secret;
    }
    return FALSE;
  }

  /**
   * Returns the access key needed for API calls.
   */
  public function getAccessKey() {
    $access = \Drupal::config('affiliates_connect_amazon.settings')->get('amazon_access_key');
    if ($access) {
      return $access;
    }
    return FALSE;
  }

  /**
   * Collect response from affiliate api url using the Guzzle HTTP client.
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
   * Gets information about an item, or array of items, from Amazon PA API's
   * ItemLookup operation.
   *
   * @param array|string $items
   *   String containing a single ASIN or an array of ASINs to look up.
   *
   * @return array
   *   Collection of categories along with endpoint url.
   */
  public function lookup($items) {

    if (empty($items)) {
      throw new \InvalidArgumentException('Calling lookup without anything to lookup!');
    }
    if (!is_array($items)) {
      $items = [$items];
    }
    if (empty($this->access_key) || empty($this->associate_id) || empty($this->secret_key)) {
      throw new \LogicException('Lookup called without valid access key, secret, or associate ID.');
    }

    $url = 'http://webservices.amazon.in/onca/xml' . $associate_id . '.xml';

    $results = [];
    foreach(array_chunk($items, 10) as $asins) {
      $request = new AmazonRequest($this->$secret_key, $this->$access_key, $this->$associate_id);
      $request->setOptions([
        'Service' => 'AWSECommerceService',
        'ItemId' => implode(',', $asins),
        'ResponseGroup' => 'Small,Images',
        'Operation' => 'ItemLookup',
        'IdType' => 'ASIN',
        'AssociateTag' => $associate_id,
        'AWSAccessKeyId' => $access_key,
        'Timestamp' => $timestamp,
      ]);
      $results = array_merge($results, $request->execute()->getResults());
    }
    return $results;

    $response = $this->get($url, ['results' => $results,]);
    $contents = new SimpleXMLElement($response->getBody()->getContents());
    $json = json_encode($contents);
    $body = json_decode($json, true);

#    $categories = [];
#    foreach ($body['apiGroups']['affiliate']['apiLists'] as $key => $value) {
#      $categories[$key] = $value['availabletypes']['v1.1.0']['get'];
#    }
#    return $categories;
  }

  /**
   * Collect product data.
   *
   * @param string $item_url
   *   The API endpoint through which the request is to be made.
   *
   */
  public function products($item_url) {

    $results = [];
    foreach(array_chunk($items, 10) as $asins) {
      $request = new AmazonRequest($this->$secret_key, $this->$access_key, $this->$associate_id);
      $request->setOptions([
        'Service' => 'AWSECommerceService',
        'ItemId' => implode(',', $asins),
        'ResponseGroup' => 'Small,Images',
        'Operation' => 'ItemLookup',
        'IdType' => 'ASIN',
        'AssociateTag' => $associate_id,
        'AWSAccessKeyId' => $access_key,
        'Timestamp' => $timestamp,
      ]);
      $results = array_merge($results, $request->execute()->getResults());
    }
    return $results;

    $response = $this->get($url, ['results' => $results,]);
    $contents = new SimpleXMLElement($response->getBody()->getContents());
    $json = json_encode($contents);
    $item_data = json_decode($json, true);
    foreach ($item_data['items'] as $key => $value) {
      try {
        $uid = \Drupal::currentUser()->id();
        $product = AffiliatesProduct::create([
          'uid' => $uid,
          'name' => $value['itemBaseInfoV1']['title'],
          'plugin_id' => 'affiliates_connect_amazon',
          'product_id' => $value['itemBaseInfoV1']['itemId'],
          'product_description' => $value['itemBaseInfoV1']['itemDescription'],
          'product_warranty' => $value['itemBaseInfoV1']['itemWarranty'],
          'image_urls' => $value['itemBaseInfoV1']['imageUrls']['400x400'],
          'product_family' => $value['itemBaseInfoV1']['categoryPath'],
          'currency' => $value['itemBaseInfoV1']['maximumRetailPrice']['currency'],
          'maximum_retail_price' => $value['itemBaseInfoV1']['maximumRetailPrice']['price'],
          'vendor_selling_price' => $value['itemBaseInfoV1']['amazonSellingPrice']['price'],
          'vendor_special_price' => $value['itemBaseInfoV1']['amazonSpecialPrice']['price'],
          'product_url' => $value['itemBaseInfoV1']['productUrl'],
          'product_brand' => $value['itemBaseInfoV1']['productBrand'],
          'in_stock' => $value['itemBaseInfoV1']['inStock'],
          'cod_available' => $value['itemBaseInfoV1']['codAvailable'],
          'discount_percentage' => $value['itemBaseInfoV1']['discountPercentage'],
          'offers' => implode(',', $value['itemBaseInfoV1']['offers']),
          'size' => $value['itemBaseInfoV1']['attributes']['size'],
          'color' => $value['itemBaseInfoV1']['attributes']['color'],
          'seller_name' => $value['itemShippingInfoV1']['sellerName'],
          'seller_average_rating' => $value['itemShippingInfoV1']['sellerAverageRating'],
          'additional_data' => '',
          'status' => 1,
        ]);
        $product->save();
      }
      catch (Exception $e) {
        echo $e->getMessage();
      }
    }

  }

#  /**
#   * Callback for `affiliates_connect_amazon/post.json` API method.
#   */
#  public function post_example( Request $request ) {
#    // decode JSON string from the request body into array.
#    // This condition checks the `Content-type` and makes sure to
#    if ( 0 === strpos( $request->headers->get( 'Content-Type' ), 'application/json' ) ) {
#      $data = json_decode( $request->getContent(), TRUE );
#      $request->request->replace( is_array( $data ) ? $data : [] );
#    }
#    $response['data'] = 'Return test data';
#    $response['method'] = 'POST';
#    return new JsonResponse( $response );
#  }

}
