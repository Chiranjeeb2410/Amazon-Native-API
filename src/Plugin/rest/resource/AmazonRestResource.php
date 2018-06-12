<?php

namespace Drupal\affiliates_connect_amazon\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Drupal\node\Entity\Node;

/**
 * Provides resource to get all required Product Advertising API operations
 * through entities.
 *
 * @RestResource(
 *   id = "amazon_rest_resource",
 *   label = @Translation("Get all Product Advertising API operations"),
 *   uri_paths = {
 *     "canonical" = "/affiliates_connect_amazon/amazon_rest_resource"
 *   }
 * )
 */
class AmazonRestResource extends ResourceBase {

  /**
   *  A curent user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user instance.
   * @param Symfony\Component\HttpFoundation\Request $current_request
   *   The current request
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user, Request $current_request) {
     parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
     $this->currentUser = $current_user;
     $this->currentRequest = $current_request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('affiliates_connect_amazon'),
      $container->get('current_user'),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * Responds to GET requests.
   *
   * Returns details of a course node.
   *
   * @return \Drupal\rest\ResourceResponse
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get($nid = NULL) {
    // If the request has the parameter 'values=all' in it then store the string
    // response and return the response if validated.
    if ($this->currentRequest->query->get('values') == 'all') {
      global $api_access_id;
      $api_access_id = 'https://www.amazon.com/gp/product/';
      $api_secret_key = \Drupal::config('AffiliatesAmazonSettingsForm')->get('native_affiliate_secret_key');
      $uri = "amazon_rest_resource?api_secret_key=";
      $absolute_url = $api_url.$uri.$api_secret_key;
      $response = \Drupal::httpClient()
          ->get($absolute_url , [
              'auth' => ['basic', 'passwd'],
          ]);
     $response = ['message' => 'Generating Rest service response'];
     return new ResourceResponse($response);
    }

  }

}
