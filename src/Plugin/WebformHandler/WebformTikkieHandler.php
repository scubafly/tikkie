<?php

namespace Drupal\tikkie\Plugin\WebformHandler;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\tikkie\TikkieClient;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\WebformSubmissionInterface;
use PHPTikkie\Exceptions\PHPTikkieException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Webform Tikkie handler.
 *
 * @WebformHandler(
 *   id = "tikkie",
 *   label = @Translation("Tikkie"),
 *   category = @Translation("Tikkie"),
 *   description = @Translation("Tikkie webform submission handler."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class WebformTikkieHandler extends WebformHandlerBase {

  /**
   * The Tikkie Client.
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
   * Constructs a WebformHandlerBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\webform\WebformSubmissionConditionsValidatorInterface $conditions_validator
   *   The webform submission conditions (#states) validator.
   * @param \Drupal\tikkie\TikkieClient $tikkieClient
   *   The Tikkie Client.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   *
   * @see \Drupal\webform\Entity\Webform::getHandlers
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, WebformSubmissionConditionsValidatorInterface $conditions_validator, TikkieClient $tikkieClient, Messenger $messenger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger_factory, $config_factory, $entity_type_manager, $conditions_validator);
    $this->tikkieClient = $tikkieClient;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('webform_submission.conditions_validator'),
      $container->get('tikkie.client'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'message' => 'This is a custom message.',
      'debug' => FALSE,
      'platform_token' => '',
      'user_token' => '',
      'bank_token' => '',
      'payment_description' => '',
      'custom_amount_url' => '',
      'payment_amount_field' => '',
      'payment_id_field' => '',
      'payment_token_field' => '',
      'payment_time_field' => '',
      'payment_status_field' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['tikkie'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Tikkie'),
    ];
    $form['tikkie']['platform_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Platform token'),
      '#default_value' => $this->configuration['platform_token'],
      '#required' => TRUE,
    ];
    $form['tikkie']['user_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User token'),
      '#default_value' => $this->configuration['user_token'],
      '#required' => TRUE,
    ];
    $form['tikkie']['bank_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bank account token'),
      '#default_value' => $this->configuration['bank_token'],
      '#required' => TRUE,
    ];

    $form['payment'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Payment'),
    ];
    $form['payment']['payment_description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Payment description'),
      '#default_value' => $this->configuration['payment_description'],
      '#description' => $this->t('The description shown with the payment.'),
    ];
    $form['payment']['custom_amount_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom amount URL'),
      '#default_value' => $this->configuration['custom_amount_url'],
      '#description' => $this->t('The Tikkie URL where a user can pay a custom amount.'),
      '#required' => TRUE,
    ];
    $form['payment']['payment_amount_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Payment amount'),
      '#options' => $this->getElementsSelectOptions(),
      '#default_value' => $this->configuration['payment_amount_field'],
      '#description' => $this->t('The field that contains the amount to be payed. When zero, the above URL will be used.'),
      '#required' => TRUE,
    ];
    $form['payment']['payment_id_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Payment ID field'),
      '#options' => $this->getElementsSelectOptions(['textfield', 'value']),
      '#default_value' => $this->configuration['payment_id_field'],
      '#description' => $this->t('The payment ID will be stored in this field.'),
      '#required' => TRUE,
    ];
    $form['payment']['payment_token_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Payment token field'),
      '#options' => $this->getElementsSelectOptions(['textfield', 'value']),
      '#default_value' => $this->configuration['payment_token_field'],
      '#description' => $this->t('The payment request token will be stored in this field.'),
      '#required' => TRUE,
    ];
    $form['payment']['payment_time_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Payment time field'),
      '#options' => $this->getElementsSelectOptions(['textfield', 'value']),
      '#default_value' => $this->configuration['payment_time_field'],
      '#description' => $this->t('The payment date/time will be stored in this field.'),
      '#required' => TRUE,
    ];
    $form['payment']['payment_status_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Payment status field'),
      '#options' => $this->getElementsSelectOptions(['textfield', 'value']),
      '#default_value' => $this->configuration['payment_status_field'],
      '#description' => $this->t('The payment status will be stored in this field.'),
      '#required' => TRUE,
    ];

    // Development.
    $form['development'] = [
      '#type' => 'details',
      '#title' => $this->t('Development settings'),
    ];
    $form['development']['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debugging'),
      '#description' => $this->t('If checked, every handler method invoked will be displayed on-screen to all users.'),
      '#return_value' => TRUE,
      '#default_value' => $this->configuration['debug'],
    ];

    return $this->setSettingsParents($form);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['message'] = $form_state->getValue('message');
    $this->configuration['platform_token'] = $form_state->getValue('platform_token');
    $this->configuration['user_token'] = $form_state->getValue('user_token');
    $this->configuration['bank_token'] = $form_state->getValue('bank_token');
    $this->configuration['payment_description'] = $form_state->getValue('payment_description');
    $this->configuration['custom_amount_url'] = $form_state->getValue('custom_amount_url');
    $this->configuration['payment_amount_field'] = $form_state->getValue('payment_amount_field');
    $this->configuration['payment_id_field'] = $form_state->getValue('payment_id_field');
    $this->configuration['payment_token_field'] = $form_state->getValue('payment_token_field');
    $this->configuration['payment_time_field'] = $form_state->getValue('payment_time_field');
    $this->configuration['payment_status_field'] = $form_state->getValue('payment_status_field');
    $this->configuration['debug'] = (bool) $form_state->getValue('debug');
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $build = [];

    if ($this->debugEnabled()) {
      $build['debug'] = ['#markup' => '<strong>' . $this->t('Debugging is enabled') . '</strong>'];
    }
    $items[] = $this->t('Description: @config', ['@config' => $this->configuration['payment_description']]);
    $build['config'] = [
      '#theme' => 'item_list',
      '#items' => $items,
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    if ($update) {
      return;
    }

    // Set and store payment ID.
    $data = $webform_submission->getData();
    $data[$this->configuration['payment_id_field']] = sprintf('tikkie-%04d', $webform_submission->id());
    $webform_submission->setData($data);
    $webform_submission->resave();

    $amount = $webform_submission->getElementData($this->configuration['payment_amount_field']);

    // When '0' amount was set, redirect to custom checkout URL. The API does
    // not yet support this scenario.
    if (empty($amount)) {
      $redirectUrl = $this->configuration['custom_amount_url'];
      $this->redirectToCheckout($redirectUrl);
    }

    try {
      $description = empty($this->configuration['payment_description']) ? $webform_submission->getWebform()
        ->label() : $this->configuration['payment_description'];

      $paymentRequest = $this->tikkieClient->getTikkie()
        ->newPaymentRequest($this->configuration['platform_token'], $this->configuration['user_token'], $this->configuration['bank_token'], [
          'amountInCents' => $amount,
          'currency' => 'EUR',
          'description' => $description,
          'externalId' => $webform_submission->getElementData($this->configuration['payment_id_field']),
        ])
        ->save();

      if ($this->debugEnabled()) {
        debug($paymentRequest);
      }

      $data[$this->configuration['payment_token_field']] = $paymentRequest->paymentRequestToken;
      $webform_submission->setData($data);
      $webform_submission->resave();

      $redirectUrl = $paymentRequest->paymentRequestUrl;
      $this->redirectToCheckout($redirectUrl);
    }
    catch (\Exception $exception) {
      if ($exception instanceof PHPTikkieException) {
        $this->tikkieClient->logTikkieException($exception);
        $this->messenger->addError($this->t('Something went wrong with your Tikkie payment. Please try again later.'));
      }
      else {
        $this->tikkieClient->logTikkieException($exception);
        $this->messenger->addError($this->t('Something went wrong. Please contact the site administrator.'));
      }
    }
  }

  /**
   * Determines if debugging is enabled.
   *
   * @return bool
   *   True if enabled.
   */
  private function debugEnabled() {
    return (bool) $this->configuration['debug'];
  }

  /**
   * Get webform elements selectors as options.
   *
   * @param array $types
   *   List of types to filter.
   *   - Leave empty skip filtering of types.
   *
   * @see \Drupal\webform\Entity\Webform::getElementsSelectorOptions()
   *
   * @return array
   *   Webform elements selectors as options.
   */
  private function getElementsSelectOptions(array $types = []) {
    $options = [];
    $elements = $this->getWebform()->getElementsInitializedAndFlattened();
    foreach ($elements as $key => $element) {
      // Skip element if not in given 'types' array.
      if ($types && !in_array($element['#type'], $types)) {
        continue;
      }

      $options[$key] = $element['#title'];
    }
    return $options;
  }

  /**
   * Redirect to the Tikkie checkout.
   *
   * @param string $url
   *   The Tikkie payment URL to redirect to.
   */
  private function redirectToCheckout($url) {
    $response = new RedirectResponse($url);

    $response->send();
    exit();
  }

}
