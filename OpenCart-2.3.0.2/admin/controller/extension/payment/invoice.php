<?php
class ControllerExtensionPaymentInvoice extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/payment/invoice');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');
     
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('invoice', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=payment', true));
        }
       
        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_enabled']                  = $this->language->get('text_enabled');
		$data['text_disabled']                 = $this->language->get('text_disabled');
        $data['button_save']                   = $this->language->get('button_save');
		$data['button_cancel']                 = $this->language->get   ('button_cancel');

        $data['entry_invoice_key'] = $this->language->get('entry_invoice_key');
        $data['entry_invoice_login'] = $this->language->get('entry_invoice_login');
        $data['entry_invoice_terminal'] = $this->language->get('entry_invoice_terminal');
        $data['entry_status'] = $this->language->get('entry_status');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');



        
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_payment'),
            'href' => $this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=payment', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/invoice', 'token=' . $this->session->data['token'], true)
        );

        $data['action'] = $this->url->link('extension/payment/invoice', 'token=' . $this->session->data['token'], true);
        $data['cancel'] = $this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=payment', true);

        // $data = array_merge($data, array(
        //     'payment_invoice_key' => $this->getRequestParam('payment_invoice_key'),
        //     'payment_invoice_login' => $this->getRequestParam('payment_invoice_login'),
        //     'payment_invoice_status' => $this->getRequestParam('payment_invoice_status'),
        // ));

        if (isset($this->request->post['invoice_key'])) {
			$data['invoice_key'] = $this->request->post['invoice_key'];
		} else {
			$data['invoice_key'] = $this->config->get('invoice_key');
		}

        if (isset($this->request->post['invoice_login'])) {
			$data['invoice_login'] = $this->request->post['invoice_login'];
		} else {
			$data['invoice_login'] = $this->config->get('invoice_login');
		}
        
		if (isset($this->request->post['invoice_status'])) {
			$data['invoice_status'] = $this->request->post['invoice_status'];
		} else {
			$data['invoice_status'] = $this->config->get('invoice_status');
		}



        $this->response->setOutput($this->load->view('extension/payment/invoice', $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/payment/invoice')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    private function getRequestParam($name)
    {
        if (isset($this->request->post[$name])) {
            return $this->request->post[$name];
        } else {
            return $this->config->get($name);
        }
    }
}