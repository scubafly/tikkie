<?php

namespace Drupal\tikkie\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\tikkie\TikkieClient;
use PHPTikkie\Exceptions\PHPTikkieException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TikkieStatusController.
 */
class TikkieStatusController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The Tikkie Client service.
   *
   * @var \Drupal\tikkie\TikkieClient
   */
  protected $tikkieClient;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Creates a TikkieTestController.
   *
   * @param \Drupal\tikkie\TikkieClient $tikkieClient
   *   The Tikkie Client service.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   */
  public function __construct(TikkieClient $tikkieClient, Messenger $messenger) {
    $this->tikkieClient = $tikkieClient;
    $this->messenger = $messenger;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tikkie.client'),
      $container->get('messenger')
    );
  }

  /**
   * Returns the Tikkie status page.
   */
  public function status() {
    $variables = [
      'platforms' => [],
      'clients' => [],
      'payment_requests' => [],
    ];

    try {
      $platforms = $this->tikkieClient->getTikkie()->platforms();
      foreach ($platforms as $platform) {
        $variables['platforms'][] = [
          'name' => $platform->name,
          'token' => $platform->platformToken,
          'phone' => $platform->phoneNumber,
          'email' => $platform->email,
        ];
        $clients = $this->tikkieClient->getTikkie()
          ->users($platform->platformToken);
        foreach ($clients as $client) {
          // Note: This assumes only one bank account per user.
          $variables['clients'][] = [
            'name' => $client->name,
            'user_token' => $client->userToken,
            'phone' => $client->phoneNumber,
            'bank_account_token' => $client->bankAccounts[0]->bankAccountToken,
            'iban' => $client->bankAccounts[0]->iban,
            'label' => $client->bankAccounts[0]->bankAccountLabel,
          ];
          $paymentRequests = $this->tikkieClient->getRecentPaymentRequests($platform->platformToken, $client->userToken);
          foreach ($paymentRequests as $paymentRequest) {
            foreach ($paymentRequest->payments as $payment) {
              $variables['payment_requests'][] = [
                'request_created' => $paymentRequest->created->format('Y-m-d h:i:s'),
                'request_status' => $paymentRequest->status,
                'external_id' => $paymentRequest->externalId,
                'request_token' => $paymentRequest->paymentRequestToken,
                'payment_created' => $payment->created->format('Y-m-d h:i:s'),
                'payment_amount' => $payment->amountInCents,
                'payment_status' => $payment->onlinePaymentStatus,
                'payment_party' => $payment->counterPartyName,
                'payment_description' => $payment->description,
              ];
            }
          }
        }
      }
    }
    catch (\Exception $exception) {
      if ($exception instanceof PHPTikkieException) {
        $this->tikkieClient->logTikkieException($exception);
        $this->messenger->addError($this->t('An error occurred while fetching Tikkie data. Check the log for details.'));
      }
    }

    return [
      'platforms' => [
        '#theme' => 'table',
        '#header' => [
          $this->t('Platform'),
          $this->t('Platform token'),
          $this->t('Phone'),
          $this->t('Email'),
        ],
        '#rows' => $variables['platforms'],
        '#empty' => $this->t('No platforms available.'),
      ],
      'clients' => [
        '#theme' => 'table',
        '#header' => [
          $this->t('User'),
          $this->t('User token'),
          $this->t('phone'),
          $this->t('Bank account token'),
          $this->t('IBAN number'),
          $this->t('IBAN label'),
        ],
        '#rows' => $variables['clients'],
        '#empty' => $this->t('No clients available.'),
      ],
      'payments' => [
        '#theme' => 'table',
        '#header' => [
          $this->t('Request created'),
          $this->t('Request status'),
          $this->t('External ID'),
          $this->t('Request token'),
          $this->t('Payment created'),
          $this->t('Payment amount'),
          $this->t('Payment status'),
          $this->t('Payment party'),
          $this->t('Payment description'),
        ],
        '#rows' => $variables['payment_requests'],
        '#empty' => $this->t('No payments available.'),
      ],
    ];
  }

}
