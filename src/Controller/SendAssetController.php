<?php

namespace Drupal\drupalchain\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * An example controller.
 */
class SendAssetController extends ControllerBase {

  public $multichain_chain;
  public $config=array();

  public $multichain_labels=array();

  public function output_html_error($html)
	{
		echo '<div class="bg-danger" style="padding:1em;">Error: '.$html.'</div>';
	}

  public function array_get_column($array, $key) // see array_column() in recent versions of PHP
  {
        //   print_r($array);
        //   print_r($key);
  	$result=array();
    
  	    foreach ($array as $index => $element)
  	    	if (array_key_exists($key, $element)){
                $result[$index]=$element[$key];
            }
    // print_r($result);
  	return $result;
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
      
  //	echo '<PRE>'; print_r($payload); echo '</PRE>';
      
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
      
  //	echo '<PRE>'; print_r($response); echo '</PRE>';
      
      
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
      
      return $this->json_rpc_send($this->multichain_chain['rpchost'], $this->multichain_chain['rpcport'], $this->multichain_chain['rpcuser'],
          $this->multichain_chain['rpcpassword'], $method, array_slice($args, 1));
  }

  //diplaying transection function
  public function multichain_labels()
	{
		
        $items = $this->multichain('liststreampublishers', 'root', '*', true, 10000);

        foreach ($items as $item)
			$multichain_labels[$item['publisher']]=pack('H*', $item['last']['data']);
		
		return $multichain_labels;
	}


  public function read_config() {
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


    /**
     * multichain trying begins
     */
    $this->multichain_chain = $config['default'];
    $response = $this->multichain('getinfo');
    $result=$response['result'];

    //Displaying assets
    // $sendaddresses=array();
	// $usableaddresses=array();
	// $keymyaddresses=array();
	// $keyusableassets=array();
	// $haslocked=false;
	// $getinfo= $result;
	// $labels=array();

    // $getaddresses = $this->multichain('getaddresses', true);
    // // print_r($getaddresses);
    // $listpermissions = $this->multichain('listpermissions', 'send', implode(',', $this->array_get_column($getaddresses, 'address')));
    // // print_r($listpermissions);
    // $sendaddresses = $this->array_get_column($listpermissions, 'address');
    // // print_r($sendaddresses);
    // foreach ($getaddresses as $address)
	// 		if ($address['ismine'])
	// 			$keymyaddresses[$address['address']]=true;

    // $labels=$this->multichain_labels();

    // $listpermissions= $this->multichain('listpermissions', 'receive');
    // // print_r($listpermissions);
    // $receiveaddresses= $this->array_get_column($listpermissions, 'address');

    // print_r($receiveaddresses);


    //code completed for displaying assets


    /**
     * #form
     */
    $form = \Drupal::formBuilder()->getform('Drupal\drupalchain\Form\SendAssetForm');

    $build = [
        '#theme' => 'send',
        '#test_var' => $config['default']['rpcuser'],
        // '#protocolversion' => $result['protocolversion'],
        '#result' => $result,
        '#items' => $form
    ];
    
    return $build;
  }

}