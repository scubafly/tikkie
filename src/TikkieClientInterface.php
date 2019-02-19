<?php

namespace Drupal\tikkie;

/**
 * Interface TikkieClientInterface.
 *
 * @package Drupal\tikkie
 */
interface TikkieClientInterface {

  /**
   * Initialize the Tikkie Client.
   *
   * @return \PHPTikkie\PHPTikkie
   *   The PHPTikkie class.
   */
  public function initializeTikkie();

  /**
   * The PHPTikkie class.
   *
   * @return \PHPTikkie\PHPTikkie
   *   The tikkie object.
   */
  public function getTikkie();

  /**
   * Returns recent payments.
   *
   * @param string $platformToken
   *   The platform token.
   * @param string $userToken
   *   The user token.
   * @param int $period
   *   Period in hours.
   * @param int $offset
   *   Offset number of items. Max 100 items will be returned.
   *
   * @return \PHPTikkie\Entities\PaymentRequest[]
   *   Payment request entities.
   */
  public function getRecentPaymentRequests($platformToken, $userToken, $period = 24, $offset = 0);

  /**
   * Logs tikkie exceptions.
   *
   * @param \Exception $exception
   *   The exception to log.
   */
  public function logTikkieException(\Exception $exception);

}
