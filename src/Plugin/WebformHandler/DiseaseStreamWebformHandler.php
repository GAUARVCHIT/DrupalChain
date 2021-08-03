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
 *   id = "disease_stream_handler",
 *   label = @Translation("Disease Stream Handler"),
 *   category = @Translation("Settings"),
 *   description = @Translation("Create Disease stream. Data lake for Patients information."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */

class DiseaseStreamWebformHandler extends WebformHandlerBase{

    use StringTranslationTrait;

    public $multichain_chain;
    
    public function output_html_error($html)
    {
        echo '<div class="bg-danger" style="padding:1em;">Error: '.$html.'</div>';
    }
    
    public function json_rpc_send($host, $port, $user, $password, $method, $params=array(), &$rawresponse=false)
    {
        if (!function_exists('curl_init')) {
            $this->output_html_error('This web demo requires the curl extension for PHP. Please contact your web hosting provider or system administrator for assistance.');
            exit;
        }
        
        $url='http://'.$host.':'.$port.'/';
                
        $payload=json_encode(array(
            'id' => time(),
            'method' => $method,
            'params' => $params,
        ));
        
        
        $ch=curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $user.':'.$password);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: '.strlen($payload)
        ));
        
        $response=curl_exec($ch);
        
        if ($rawresponse!==false)
            $rawresponse=$response;
    
        
        $result=json_decode($response, true);
        
        if (!is_array($result)) {
            $info=curl_getinfo($ch);
            $result=array('error' => array(
                'code' => 'HTTP '.$info['http_code'],
                'message' => strip_tags($response).' '.$url
            ));
        }
        
        return $result;
    }
    
    public function multichain($method) // other params read from func_get_args()
    {   
        $args=func_get_args();
        // print_r($this->multichain_chain);
        // print_r($method);
        return $this->json_rpc_send($this->multichain_chain['rpchost'], $this->multichain_chain['rpcport'], $this->multichain_chain['rpcuser'],
            $this->multichain_chain['rpcpassword'], $method, array_slice($args, 1));
    }



    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
        $postData=$form_state->getValues();

            
        $config=array();
        $contents=file_get_contents('C:\xampp\htdocs\drupal\modules\drupalchain\config.txt');
        $lines=explode("\n", $contents);
        
        foreach ($lines as $line) {
            $content=explode('#', $line);
            $fields=explode('=', trim($content[0]));
            if (count($fields)==2) {
                if (is_numeric(strpos($fields[0], '.'))) {
                    $parts=explode('.', $fields[0]);
                    $config[$parts[0]][$parts[1]]=$fields[1];
                } else {
                    $config[$fields[0]]=$fields[1];
                }
            }
        }

        $this->multichain_chain = $config['default'];
        $sendtxid = $this->multichain('createfrom',$postData['from_address'],'stream', $postData['disease_name'], true);
        drupal_set_message(t('Stream Created Sucessufully Txid:: %sendtxid',['%sendtxid' => $sendtxid['result']]),'status',True);
    }
}