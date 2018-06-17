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
use Drupal\affiliates_connect\Entity\AffiliatesProduct;

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

    $response = $this->get($url, ['results' => $results,]);
    $contents = new SimpleXMLElement($response->getBody()->getContents());
    $json = json_encode($contents);
    $body = json_decode($json, true);

    $categories = [];
    foreach ($body['apiGroups']['affiliate']['apiLists'] as $key => $value) {
      $categories[$key] = $value['availabletypes']['v1.1.0']['get'];
    }
    return $categories;
  }

  /**
   * Collect product data.
   *
   * @param string $item_url
   *   The API endpoint through which the request is to be made.
   *
   */
  public function products($item_url) {

    $associate_id = $this->config('affiliates_connect_amazon.settings')->get('amazon_associate_id');
    $access_key = $this->config('affiliates_connect_amazon.settings')->get('amazon_associate_id');
    $secret_key = $this->config('affiliates_connect_amazon.settings')->get('amazon_secret_key');

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
