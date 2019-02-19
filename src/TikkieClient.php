<?php

namespace Drupal\tikkie;

use DateInterval;
use DateTime;
use DateTimeZone;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use PHPTikkie\Environment;
use PHPTikkie\Exceptions\PHPTikkieException;
use PHPTikkie\PHPTikkie;

/**
 * Tikkie Client service.
 *
 * Provides a thin wrapper around the PHPTikkie class.
 *
 * @package Drupal\tikkie
 */
class TikkieClient implements TikkieClientInterface {

  /**
   * Tikkie settings configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  public $config;

  /**
   * The tikkie object.
   *
   * @var \PHPTikkie\PHPTikkie
   */
  protected $tikkie;

  /**
   * The Tikkie Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * TikkieClient constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   */
  public function __construct(ConfigFactoryInterface $config, LoggerChannelFactoryInterface $loggerFactory) {
    $this->config = $config->get('tikkie.settings');
    $this->logger = $loggerFactory->get('tikkie');
    $this->initializeTikkie();
  }

  /**
   * {@inheritdoc}
   */
  public function initializeTikkie() {
    if (!empty($this->tikkie)) {
      return $this->tikkie;
    }

    try {
      $environment = new Environment($this->getApiKey(), $this->getTestMode());
      $environment->loadPrivateKey($this->getPrivateKeyPath());

      $this->tikkie = new PHPTikkie($environment);
    }
    catch (\Exception $exception) {
      if ($exception instanceof PHPTikkieException) {
        $this->logTikkieException($exception);
      }
    }

    return $this->tikkie;
  }

  /**
   * {@inheritdoc}
   */
  public function getTikkie() {
    return $this->tikkie;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecentPaymentRequests($platformToken, $userToken, $period = 24, $offset = 0) {
    $paymentRequests = [];
    if (empty($platformToken) || empty($userToken)) {
      return [];
    }

    try {
      $timezone = new DateTimeZone(\Drupal::currentUser()->getTimeZone());
      $to = new DateTime('now', $timezone);
      $from = clone $to;
      $from = $from->sub(new DateInterval("PT{$period}H"));

      $paymentRequests = $this->tikkie->paymentRequests($platformToken, $userToken, $offset, 100, $from, $to);
    }
    catch (\Exception $exception) {
      if ($exception instanceof PHPTikkieException) {
        $this->logTikkieException($exception);
      }
    }

    return $paymentRequests;
  }

  /**
   * {@inheritdoc}
   */
  public function logTikkieException(\Exception $exception) {
    $this->logger->error($exception->getMessage());
  }

  /**
   * Returns the API consumer key.
   *
   * @return string
   *   The API key string.
   */
  protected function getApiKey() {
    return $this->config->get('consumer_key');
  }

  /**
   * Whether the sandbox or production endpoint will be used.
   *
   * @return bool
   *   True if the sandbox should be used.
   */
  protected function getTestMode() {
    return $this->config->get('test_mode');
  }

  /**
   * Returns the path to the private key file.
   *
   * @return string
   *   Path and file name, relative to the Drupal root.
   */
  protected function getPrivateKeyPath() {
    return $this->config->get('private_key_path');
  }

}
