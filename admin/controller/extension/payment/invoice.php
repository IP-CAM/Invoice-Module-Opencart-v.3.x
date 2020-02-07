<?php

class ControllerExtensionPaymentInvoice extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/payment/invoice');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        $data['heading_title'] = $this->language->get('heading_title');
        $data['entry_invoice_key'] = $this->language->get('entry_invoice_key');
        $data['entry_invoice_login'] = $this->language->get('entry_invoice_login');
        $data['entry_invoice_terminal'] = $this->language->get('entry_invoice_terminal');
        $data['entry_status'] = $this->language->get('entry_status');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_invoice', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_payment'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/invoice', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/payment/invoice', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

        $data = array_merge($data, array(
            'payment_invoice_key' => $this->getRequestParam('payment_invoice_key'),
            'payment_invoice_login' => $this->getRequestParam('payment_invoice_login'),
            'payment_invoice_status' => $this->getRequestParam('payment_invoice_status'),
        ));

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

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