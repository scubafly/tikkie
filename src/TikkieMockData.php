<?php

namespace Drupal\tikkie;

use Drupal\Core\Config\ConfigFactoryInterface;
use PHPTikkie\Entities\Platform;
use PHPTikkie\Exceptions\PHPTikkieException;

/**
 * Creates mock Tikkie data via the Tikkie API.
 *
 * @package Drupal\tikkie
 */
class TikkieMockData {

  /**
   * The Tikkie Client service.
   *
   * @var \Drupal\tikkie\TikkieClient
   */
  protected $tikkieClient;

  /**
   * The system configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * TikkieMockData constructor.
   *
   * @param \Drupal\tikkie\TikkieClient $tikkieClient
   *   The Tikkie Client service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory.
   */
  public function __construct(TikkieClient $tikkieClient, ConfigFactoryInterface $config) {
    $this->tikkieClient = $tikkieClient;
    $this->systemConfig = $config->get('system.site');
  }

  /**
   * Creates a dummy platform.
   *
   * @return \PHPTikkie\Entities\Platform
   *   The created platform.
   */
  public function createDummyPlatform() {
    $platform = NULL;
    try {
      $platform = $this->tikkieClient->getTikkie()->newPlatform([
        'name' => 'Plaform ' . $this->systemConfig->get('name'),
        'phoneNumber' => '0612345678',
        'platformUsage' => Platform::USAGE_TYPE_MYSELF,
        'email' => $this->systemConfig->get('mail'),
      ])->save();
    }
    catch (\Exception $exception) {
      if ($exception instanceof PHPTikkieException) {
        $this->tikkieClient
          ->logTikkieException($exception);
      }
    }

    return $platform;
  }

  /**
   * Creates a dummy Tikkie user.
   *
   * @param string $platformToken
   *   The platform token.
   *
   * @return \PHPTikkie\Entities\User
   *   Tikkie user entity.
   */
  public function createDummyUser($platformToken) {
    $user = NULL;
    try {
      $siteName = $this->systemConfig->get('name');
      $user = $this->tikkieClient
        ->getTikkie()
        ->newUser($platformToken, [
          'name' => 'User ' . $siteName,
          'phoneNumber' => '0612345678',
          'iban' => 'NL00BANK123456789',
          'bankAccountLabel' => 'Account ' . $siteName
        ])
        ->save();
    }
    catch (\Exception $exception) {
      if ($exception instanceof PHPTikkieException) {
        $this->tikkieClient->logTikkieException($exception);
      }
    }

    return $user;
  }

  /**
   * Creates a dummy payment request.
   *
   * @param string $platformToken
   *   The platform token.
   * @param string $userToken
   *   The user token.
   * @param string $bankAccountToken
   *   Bank account token.
   *
   * @return \PHPTikkie\Entities\PaymentRequest
   *   Tikkie payment request entity.
   */
  public function createDummyPaymentRequest($platformToken, $userToken, $bankAccountToken) {
    $paymentRequest = [];
    try {
      $paymentRequest = $this->tikkieClient
        ->getTikkie()
        ->newPaymentRequest($platformToken, $userToken, $bankAccountToken, [
          'amountInCents' => '1250',
          'currency' => 'EUR',
          'description' => 'Thank you',
          'externalId' => 'Order 1234'
        ])
        ->save();
    }
    catch (\Exception $exception) {
      if ($exception instanceof PHPTikkieException) {
        $this->tikkieClient->logTikkieException($exception);
      }
    }

    return $paymentRequest;
  }

}
