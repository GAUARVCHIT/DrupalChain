<?php
    namespace Drupal\drupalchain\Form;
    use Drupal\Core\Form\FormBase;
    use Drupal\Core\Form\FormStateInterface;
    use Drupal\Core\Form\drupal_set_message;
    use Drupal\Tests\drupalmoduleupgrader\Unit\Plugin\DMU\Converter\Functions\DrupalSetMessageTest;
    use phpDocumentor\Reflection\PseudoTypes\True_;

class SendAssetForm extends FormBase{

        public function getFormId(){
            return 'send_asset';
        }

        public function buildForm(array $form, FormStateInterface $form_state){
            $form['from_address'] = [
                '#type' => 'textfield',
                '#title' => t('From Address'),
                '#default_value' => ''
            ];
            $form['asset_name'] = [
                '#type' => 'textfield',
                '#title' => t('Asset Name'),
                '#default_value' => ''
            ];
            $form['to_address'] = [
                '#type' => 'textfield',
                '#title' => t('To Address'),
                '#default_value' => ''
            ];
            $form['quantity'] = [
                '#type' => 'textfield', 
                // '#unsigned' => TRUE, 
                // '#precision' => 5, 
                // '#scale' => 2,
                '#title' => t('Quantity'),
            ];
            $form['save'] = [
                '#type' => 'submit',
                '#value' => t('Send Asset'),
                '#button_type' => 'primary'
            ];

            return $form;
        }

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

        public function submitForm(array &$form, FormStateInterface $form_state){

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
            $sendtxid = $this->multichain('sendassetfrom',$postData['from_address'], $postData['to_address'], $postData['asset_name'], floatval($postData['quantity']));
            drupal_set_message(t('Transection Executed Sucessufully Txid:: %sendtxid',['%sendtxid' => $sendtxid['result']]),'status',True);
            // echo "<pre>";
            // print_r($config);
            // print_r($response);
            // print_r($postData['from_address']);
            // echo "<pre>";
            // exit;
        }
    }
