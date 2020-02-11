<?php
require_once "InvoiceSDK/RestClient.php";
require_once "InvoiceSDK/CREATE_TERMINAL.php";
require_once "InvoiceSDK/CREATE_PAYMENT.php";
require_once "InvoiceSDK/common/SETTINGS.php";
require_once "InvoiceSDK/common/ORDER.php";
require_once "InvoiceSDK/common/ITEM.php";

class ControllerExtensionPaymentInvoice extends Controller
{
    /**
     * @var RestClient $invoiceClient
     */
    private $invoiceClient;

    public function index()
    {
        $data['payment_invoice_login'] = $this->config->get('payment_invoice_login');
        $data['payment_invoice_key'] = $this->config->get('payment_invoice_key');
        $data['payment_invoice_terminal'] = $this->config->get('payment_invoice_terminal');

        $this->setInvoiceClient();

        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        if($data['payment_invoice_terminal'] == null or $data['payment_invoice_terminal'] == "") {
            $terminal = $this->createTerminal($order_info);

            if($terminal == null) {
                return "<h3>Произошла ошибка! Попробуйте позже</h3>";
            }
            $data['payment_invoice_terminal'] = $terminal->id;
        }

        $data['button_confirm'] = $this->language->get('button_confirm');
        $data['button_back'] = $this->language->get('button_back');
        $data['text_loading'] = $this->language->get('text_loading');
        $data['confirm_url'] = "index.php?route=extension/payment/invoice/confirm";
        $data["order_id"] = $order_info["order_id"];

        $this->id = 'payment';

        return $this->load->view('extension/payment/invoice', $data);
    }

    public function confirm()
    {
        $this->load->model('checkout/order');
        $order_id = $this->request->post["order_id"];

        if(!isset($this->request->post["order_id"])) {
            $this->session->data['error'] = "Ошибка при создании заказа: Неверный ID заказа!";
            $this->response->redirect($this->url->link("checkout/checkout"));
            return;
        }
        $order_info = $this->model_checkout_order->getOrder($order_id);

        $link = $this->createPayment($order_info);
        if($link == null) {
            $this->model_checkout_order->addOrderHistory($order_id, 1, 'Invoice', true);
            $this->session->data['error'] = "Ошибка при создании заказа: Не удалось создать платеж!";
            $this->response->redirect($this->url->link("checkout/checkout"));
            return;
        }

        header("Location: ".$link);
        $this->cart->clear();
    }

    public function callback() {
       if(!isset($_POST["id"])) {
           return "Not found ID";
       }
       $key = $this->config->get("payment_invoice_key");
       $id = $_POST["order"]["id"];
       $amount = $_POST["order"]["amount"];
       $status = $_POST["status"];
       $signature = $_POST["signature"];

       if($signature != $this->getSignature($key,$status,$id)) {
           return "Wrong signature!";
       }
        $this->load->model('account/order');
        $this->load->model('checkout/order');
        $order_info = $this->model_account_order->getOrder($id);

        if($order_info == null) {
            return "Order not found";
        }

        $rur_order_total = $this->currency->convert($order_info['total'], $order_info['currency_code'], "RUB");
        $out_summ = $this->currency->format($rur_order_total, "RUB", $order_info['currency_value'], FALSE);
        $out_summ = number_format($out_summ, 2, '.', '');

        if($out_summ > $amount) {
            return "Wrong amount";
        }

        switch ($status) {
            case "successful":
                $this->pay($id);
                break;
            case "error":
                $this->error($id);
                break;
            case "refund":
                $this->refund($id);
                break;
        }
        return "OK";
    }

    private function pay($id)
    {
        $this->model_checkout_order->addOrderHistory($id, 5, 'Оплачено через Invoice', true);
    }

    private function error($id)
    {
        $this->model_checkout_order->addOrderHistory($id, 10, 'Ошибка при оплате', false);
    }

    private function refund($id) {
        $this->model_checkout_order->addOrderHistory($id, 11, 'Оформлен возврат', false);
    }

    private function createPayment($order_info) {
        $this->setInvoiceClient();

        $this->load->model('account/order');
        $items = $this->model_account_order->getOrderProducts($order_info["order_id"]);

        $rur_order_total = $this->currency->convert($order_info['total'], $order_info['currency_code'], "RUB");
        $data['out_summ'] = $this->currency->format($rur_order_total, "RUB", $order_info['currency_value'], FALSE);
        $data['out_summ'] = number_format($data['out_summ'], 2, '.', '');

        $create_payment = new CREATE_PAYMENT();

        $settings = new SETTINGS();
        $settings->terminal_id = $this->config->get("payment_invoice_terminal");
        $settings->success_url = $order_info["store_url"];
        $settings->fail_url = $order_info["store_url"];
        $create_payment->settings = $settings;

        $order = new ORDER();
        $order->id = $order_info["order_id"];
        $order->currency = "RUB";
        $order->amount = $data["out_summ"];
        $create_payment->order = $order;

        $receipt = array();

        foreach ($items as $item) {
            $invoice_item = new ITEM();
            $invoice_item->name = $item["name"];
            $invoice_item->quantity = $item["quantity"];
            $invoice_item->price = $item["price"];
            $invoice_item->resultPrice = $item["total"];

            array_push($receipt, $invoice_item);
        }

        $create_payment->receipt = $receipt;
        $paymentInfo = $this->invoiceClient->CreatePayment($create_payment);

        if($paymentInfo == null or $paymentInfo->error != null) {
            if($paymentInfo->error == 3) {
                $terminal = $this->createTerminal($order_info);
                if($terminal == null or $terminal->error != null) {
                    return null;
                }else {
                    return $this->createPayment($order_info);
                }
            }else {
                return null;
            }
        } else {
            return $paymentInfo->payment_url;
        }
    }

    private function createTerminal($order_info) {
        $name = $order_info["store_name"];

        $create_terminal = new CREATE_TERMINAL();
        $create_terminal->name = $name;
        $create_terminal->description = $this->config->get("config_meta_description");
        $create_terminal->defaultPrice = 0;
        $create_terminal->type = "dynamical";

        $terminal = $this->invoiceClient->CreateTerminal($create_terminal);
        if($terminal == null) {
            return null;
        }

        $this->load->model("extension/payment/invoice");
        $this->model_extension_payment_invoice->editSetting("payment_invoice",array(
            "payment_invoice_terminal" => $terminal->id,
            "payment_invoice_login" => $this->config->get('payment_invoice_login'),
            "payment_invoice_key" => $this->config->get('payment_invoice_key'),
            'payment_invoice_status' => $this->config->get('payment_invoice_status'),
        ));

        return $terminal;
    }

    private function setInvoiceClient() {
        $data['payment_invoice_login'] = $this->config->get('payment_invoice_login');
        $data['payment_invoice_key'] = $this->config->get('payment_invoice_key');
        $data['payment_invoice_terminal'] = $this->config->get('payment_invoice_terminal');

        $this->invoiceClient = new RestClient($data['payment_invoice_login'],$data['payment_invoice_key']);
    }

    private function getSignature($key,$status,$id) {
        return md5($id.$status.$key);
    }
}