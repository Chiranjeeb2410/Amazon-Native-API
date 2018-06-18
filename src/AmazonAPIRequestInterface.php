<?php

namespace Drupal\affiliates_connect_amazon;

/**
 * Provides mechanism to create requests and return product info from Amazon
 * Product Advertising API.
 *
 * @package Drupal\affiliates_connect_amazon
 */
interface AmazonRequestInterface {

  /**
   * Prepares an Amazon PA API request.
   *
   * @return AmazonAPIRequest
   */
  public function prepare();

  /**
   * Executes an Amazon PA API request.
   *
   * @return AmazonAPIRequest
   */
  public function execute();

  /**
   * Sets a single option in the request. Timestamp automatically calculated
   * and will be ignored, however Signature not yet added.
   *
   * @param string $name
   *   The name of the option to set.
   * @param string $value
   *   The value for that option.
   *
   * @return AmazonAPIRequest
   */
  public function setOption($name, $value);

  /**
   * Sets multiple options in the request. Timestamp automatically calculated
   * and will be ignored, however Signature not yet added.
   *
   * @param array $options
   *   Options in the form of (string) optionName => (string) optionValue.
   *
   * @return AmazonAPIRequest
   */
  public function setOptions(array $options);

  /**
   * Returns the result of an Amazon PA API request.
   *
   * @return array
   */
  public function getResults();

}
