<?php
class ControllerPaymentEchecknetAim extends Controller {
	protected function index() {
		$this->language->load('payment/echecknet_aim');
		
		$this->data['text_bank_account'] = $this->language->get('text_bank_account');
		$this->data['text_wait'] = $this->language->get('text_wait');
		
		$this->data['entry_account_type'] = $this->language->get('entry_account_type');
		$this->data['entry_bank_name'] = $this->language->get('entry_bank_name');
		$this->data['entry_account_name'] = $this->language->get('entry_account_name');
		$this->data['entry_account_number'] = $this->language->get('entry_account_number');
		$this->data['entry_routing_number'] = $this->language->get('entry_routing_number');
		
		$this->data['option_checking'] = $this->language->get('option_checking');
		$this->data['option_business'] = $this->language->get('option_business');
		$this->data['option_savings'] = $this->language->get('option_savings');
		
		$this->data['button_confirm'] = $this->language->get('button_confirm');
		
		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/echecknet_aim.tpl')) {
			$this->template = $this->config->get('config_template') . '/template/payment/echecknet_aim.tpl';
		} else {
			$this->template = 'default/template/payment/echecknet_aim.tpl';
		}	
		
		$this->render();		
	}
	
	public function send() {
		if ($this->config->get('echecknet_aim_server') == 'live') {
    		$url = 'https://secure.authorize.net/gateway/transact.dll';
		} elseif ($this->config->get('echecknet_aim_server') == 'test') {
			$url = 'https://test.authorize.net/gateway/transact.dll';		
		}	
		
		//$url = 'https://secure.networkmerchants.com/gateway/transact.dll';	
		
		$this->load->model('checkout/order');
		
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		
        $data = array();

		$data['x_login'] = $this->config->get('echecknet_aim_login');
		$data['x_tran_key'] = $this->config->get('echecknet_aim_key');
		$data['x_version'] = '3.1';
		$data['x_delim_data'] = 'true';
		$data['x_delim_char'] = ',';
		$data['x_encap_char'] = '"';
		$data['x_relay_response'] = 'false';
		$data['x_first_name'] = html_entity_decode($order_info['payment_firstname'], ENT_QUOTES, 'UTF-8');
		$data['x_last_name'] = html_entity_decode($order_info['payment_lastname'], ENT_QUOTES, 'UTF-8');
		$data['x_company'] = html_entity_decode($order_info['payment_company'], ENT_QUOTES, 'UTF-8');
		$data['x_address'] = html_entity_decode($order_info['payment_address_1'], ENT_QUOTES, 'UTF-8');
		$data['x_city'] = html_entity_decode($order_info['payment_city'], ENT_QUOTES, 'UTF-8');
		$data['x_state'] = html_entity_decode($order_info['payment_zone'], ENT_QUOTES, 'UTF-8');
		$data['x_zip'] = html_entity_decode($order_info['payment_postcode'], ENT_QUOTES, 'UTF-8');
		$data['x_country'] = html_entity_decode($order_info['payment_country'], ENT_QUOTES, 'UTF-8');
		$data['x_phone'] = $order_info['telephone'];
		$data['x_customer_ip'] = $this->request->server['REMOTE_ADDR'];
		$data['x_email'] = $order_info['email'];
		$data['x_description'] = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');
		$data['x_amount'] = $this->currency->format($order_info['total'], $order_info['currency_code'], 1.00000, false);
		$data['x_currency_code'] = $this->currency->getCode();
		$data['x_invoice_num'] = $this->session->data['order_id'];
		$data['x_type'] = ($this->config->get('echecknet_aim_method') == 'capture') ? 'AUTH_CAPTURE' : 'AUTH_ONLY';
		$data['x_method'] = 'ECHECK';
		$data['x_bank_aba_code'] = $this->request->post['routing_number'];
		$data['x_bank_acct_num'] = $this->request->post['account_number'];
		$data['x_bank_acct_type'] = $this->request->post['account_type'];
		$data['x_bank_name'] = $this->request->post['bank_name'];
		$data['x_bank_acct_name'] = $this->request->post['account_name'];
		$data['x_echeck_type'] = 'WEB';
		$data['x_recurring_billing'] = false;
	
		if ($this->config->get('echecknet_aim_mode') == 'test') {
			$data['x_test_request'] = 'true';
		}	
				
		$curl = curl_init($url);

		curl_setopt($curl, CURLOPT_PORT, 443);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
 
		$response = curl_exec($curl);
		
		$json = array();
		
		if (curl_error($curl)) {
			$json['error'] = 'CURL ERROR: ' . curl_errno($curl) . '::' . curl_error($curl);
			
			$this->log->write('AUTHNET AIM CURL ERROR: ' . curl_errno($curl) . '::' . curl_error($curl));	
		} elseif ($response) {
			$i = 1;
			
			$response_data = array();
			
			$results = explode(',', $response);
			
			foreach ($results as $result) {
				$response_data[$i] = trim($result, '"');
				
				$i++;
			}
		
			if ($response_data[1] == '1') {
				if (strtoupper($response_data[38]) != strtoupper(md5($this->config->get('echecknet_aim_hash') . $this->config->get('echecknet_aim_login') . $response_data[6] . $this->currency->format($order_info['total'], $order_info['currency_code'], 1.00000, false)))) {
					$this->model_checkout_order->confirm($this->session->data['order_id'], $this->config->get('config_order_status_id'));
					
					$message = '';
					
					if (isset($response_data['5'])) {
						$message .= 'Authorization Code: ' . $response_data['5'] . "\n";
					}
					
					if (isset($response_data['6'])) {
						$message .= 'AVS Response: ' . $response_data['6'] . "\n";
					}
			
					if (isset($response_data['7'])) {
						$message .= 'Transaction ID: ' . $response_data['7'] . "\n";
					}			
	
					$this->model_checkout_order->update($this->session->data['order_id'], $this->config->get('echecknet_aim_order_status_id'), $message, false);				
				}
				
				$json['success'] = $this->url->link('checkout/success', '', 'SSL');
			} else {
				$json['error'] = $response_data[4];
			}
		} else {
			$json['error'] = 'Empty Gateway Response';
			
			$this->log->write('AUTHNET AIM CURL ERROR: Empty Gateway Response');
		}
		
		curl_close($curl);
		
		$this->response->setOutput(json_encode($json));
	}
}
?>