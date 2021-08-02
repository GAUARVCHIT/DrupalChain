<?php
namespace Drupal\drupalchain\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\Component\Utility\Html;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Webform validate handler.
 *
 * @WebformHandler(
 *   id = "patient_stream_handler",
 *   label = @Translation("Patient Information Handler"),
 *   category = @Translation("Settings"),
 *   description = @Translation("Handler having general information about Patient."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */

class PatientInfoWebformHandler extends WebformHandlerBase{

    use StringTranslationTrait;

    public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
        $postData=$form_state->getValues();
        $obj = (object) [
            'name' => $postData['name'],
            'detailed address' => $postData['home_address']
        ];
        
        json_encode($obj);
        drupal_set_message(t('Stream Created Sucessufully Txid:: %sendtxid',['%sendtxid' => json_encode($obj)]),'status',True);
    }
}