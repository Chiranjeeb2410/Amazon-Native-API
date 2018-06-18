<?php

namespace Drupal\amazon;

use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AmazonAPIRequest
 *
 * @package Drupal\affiliates_connect_amazon
 */
class AmazonAPIRequest implements AmazonAPIRequestInterface {

  /**
   * Stores the options used in this request.
   *
   * @var array
   */
  protected $options = [];

  /**
   * Stores the results of this request when executed.
   *
   * @var array
   */
  protected $results = [];

  /**
   * The secret key for the AWS account authorized to use the Product
   * Advertising API.
   *
   * @var string
   */
  protected $secret_key;

  /**
   * The access key for the AWS account authorized to use the Product
   * Advertising API.
   *
   * @var string
   */
  protected $access_key;

  /**
   * The associate ID for the Product Advertising API account.
   *
   * @var string
   */
  protected $associate_id;

  /**
   * The endpoint for making Product Advertising API requests.
   *
   * @var string
   */
  protected $amazonRequestRoot = 'webservices.amazon.in';

  /**
   * The path to the endpoint for making Product Advertising API requests.
   *
   * @var string
   */
  protected $amazonRequestPath = '/onca/xml';

  /**
   * AmazonAPIRequest constructor.
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
  public function __construct($secret_key, $access_key = '', $associate_id = '') {
    $this->secret_key = $secret_key;
    if (!empty($access_key)) {
      $this->access_key = $access_key;
    }
    if (!empty($associate_id)) {
      $this->associate_id = $associate_id;
    }
  }

  /**
   * Prepares the request for execution.
   */
  protected function prepare() {
    if (empty($this->options['AWSAccessKeyId'])) {
      if (empty($this->access_key)) {
        throw new \InvalidArgumentException('Missing AWSAccessKeyId. Need to be passed as an option or set in the constructor.');
      }
      else {
        $this->setOption('AWSAccessKeyId', $this->access_key);
      }
    }

    if (empty($this->options['AssociateID'])) {
      if (empty($this->associate_id)) {
        throw new \InvalidArgumentException('Missing AssociateID. Need to be passed as an option or set in the constructor.');
      }
      else {
        $this->setOption('AssociateID', $this->associate_id);
      }
    }
  }

  /**
   * @inheritdoc
   */
  public function execute() {
    $endpoint = 'http://' . $this->amazonRequestRoot . $this->amazonRequestPath;
    $url = Url::fromUri($endpoint, ['query' => $this->options]);
    $data = \Drupal::httpClient()
      ->get($url->toString());

    if ($data->getStatusCode() == 200) {
      $xml = new \SimpleXMLElement($data->getBody());
      $json = json_encode($xml);
      $product_data = json_decode($json, true);
      foreach ($product_data->Items as $item) {
        $this->results[] = $item->Item;
      }
    }

    return $this;
  }

  /**
   * @inheritdoc
   */
  public function setOption($name, $value) {
    if (empty($name)) {
      throw new \InvalidArgumentException('Invalid option name: ' . $name);
    }
    if ($name == 'Timestamp') {
      // Timestamp automatically calculated, so we ignore it. However, signature
      // yet to be added.
      return $this;
    }

    $this->options[$name] = $value;
    return $this;
  }

  /**
   * @inheritdoc
   */
  public function setOptions(array $options) {
    foreach($options as $name => $value) {
      if (empty($name)) {
        throw new \InvalidArgumentException('Invalid option name: ' . $name);
      }
      $this->setOption($name, $value);
    }
    return $this;
  }

  /**
   * @inheritdoc
   */
  public function getResults() {
    return $this->results;
  }

}
