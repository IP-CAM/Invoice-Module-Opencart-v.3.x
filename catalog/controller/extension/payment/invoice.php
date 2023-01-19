<?php
require_once "InvoiceSDK/RestClient.php";
require_once "InvoiceSDK/CREATE_TERMINAL.php";
require_once "InvoiceSDK/CREATE_PAYMENT.php";
require_once "InvoiceSDK/common/SETTINGS.php";
require_once "InvoiceSDK/common/ORDER.php";
require_once "InvoiceSDK/common/ITEM.php";
require_once "InvoiceSDK/GET_TERMINAL.php";

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

            if($terminal == null or !isset($terminal->id)) {
                $this->log("ERROR". json_encode($terminal) . "\n");
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
            $this->log("ERROR". "id not found" . "\n");
            $this->session->data['error'] = "Ошибка при создании заказа: Неверный ID заказа!";
            $this->response->redirect($this->url->link("checkout/checkout"));
            return;
        }
        $order_info = $this->model_checkout_order->getOrder($order_id);

        $link = $this->createPayment($order_info);
        if($link == null) {
            $this->session->data['error'] = "Ошибка при создании заказа: Не удалось создать платеж!";
            $this->response->redirect($this->url->link("checkout/checkout"));
            return;
        } else {
            $this->model_checkout_order->addOrderHistory($order_id, 1, "Ожидает оплату <a href='$link'>оплатить</a>", true);
        }
        header("Location: ".$link);
        $this->cart->clear();
    }

    public function callback() {
        $postData = file_get_contents('php://input');
        $notification = json_decode($postData, true);

        $this->log("CALLBACK". $postData . "\n");

       if(!isset($notification["id"])) {
           echo "ID not found";
           return;
       }
       $tranId = $notification['id'];
       $key = $this->config->get("payment_invoice_key");
       $id = strstr($notification["order"]["id"], "-", true);
       $amount = $notification["order"]["amount"];
       $status = $notification["status"];
       $signature = $notification["signature"];

       if($signature != $this->getSignature($key,$status,$tranId)) {
           echo "Wrong signature";
           return;
       }
        $this->load->model('checkout/order');

        switch ($notification["notification_type"]) {
            case "pay" :
                switch ($status) {
                    case "successful":
                        $this->pay($id);
                        break;
                    case "error":
                        $this->error($id);
                        break;
                }
                break;
            case "refund" :
                $this->refund($id, $notification["amount"]);
                break;
        }
        echo "OK";
        return;
    }

    private function pay($id)
    {
        $this->model_checkout_order->addOrderHistory($id, 5, 'Оплачено через Invoice', true);
    }

    private function error($id)
    {
        $this->model_checkout_order->addOrderHistory($id, 10, 'Ошибка при оплате', false);
    }

    private function refund($id, $amount) {
        $this->model_checkout_order->addOrderHistory($id, 11, 'Оформлен частичный возврат возврат на сумму '.$amount."р", false);
    }

    /**
     * @return CREATE_PAYMENT
     */

    private function createPayment($order_info) {
        $this->setInvoiceClient();
        $this->checkOrCreateTerminal($order_info);
       
        $this->load->model('account/order');
       
        $rur_order_total = $this->currency->convert($order_info['total'], $order_info['currency_code'], "RUB");
        $data['out_summ'] = $this->currency->format($rur_order_total, "RUB", $order_info['currency_value'], FALSE);
        $data['out_summ'] = number_format($data['out_summ'], 2, '.', '');

        $request = new CREATE_PAYMENT();
        $request->order = $this->getOrder($order_info["order_id"], $data["out_summ"]);
        $request->settings = $this->getSettings($order_info);
        $request->receipt = $this->getReceipt($order_info);
        
        $info = $this->invoiceClient->CreatePayment($request);

        if($info == null or $info->error != null) {
            return "/";
        } else {
            return $info->payment_url;
        }
    }

    /**
     * @return ORDER
     */

    private function getOrder($id, $sum) {
        $order = new ORDER();
        $order->amount = $sum;
        $order->id = "$id" . "-" . bin2hex(random_bytes(5));
        $order->currency = "RUB";

        return $order;
    }

    /**
     * @return SETTINGS
     */

    private function getSettings($order_info) {
        $settings = new SETTINGS();
        $settings->terminal_id = $this->config->get("payment_invoice_terminal");
        $settings->success_url = $order_info["store_url"];
        $settings->fail_url = $order_info["store_url"];

        return $settings;
    }

    /**
     * @return ITEM
     */

    private function getReceipt($order_info) {
        $receipt = array();
        $basket = $this->model_account_order->getOrderProducts($order_info["order_id"]);

        foreach ($basket as $basketItem) {
            $item = new ITEM();
            $item->name = $basketItem["name"];
            $item->price = $basketItem["price"];
            $item->resultPrice = $basketItem["total"];
            $item->quantity = $basketItem["quantity"];

            array_push($receipt, $item);
        }

        return $receipt;
    }

    public function checkOrCreateTerminal($order_info) {
        $id = $this->getTerminal();
        if($id == null or empty($id)) {
            return $this->createTerminal($order_info);
        } else {
            return $id;
        }
    }

    /**
     * @return GET_TERMINAL
     */

    public function getTerminal() {
        $terminal = new GET_TERMINAL();
        $terminal->alias = $this->config->get("payment_invoice_terminal");;
        $info = $this->invoiceClient->GetTerminal($terminal);

        if(isset($info->error)){
            return null;
        }
        if($info->id == null || $info->id != $terminal->alias){
            return null;
        } else {
            return $info->id;
        }
    }

    /**
     * @return CREATE_TERMINAL
     */

    private function createTerminal($order_info) {
        $name = $order_info["store_name"];

        $create_terminal = new CREATE_TERMINAL();
        $create_terminal->name = $name;
        $create_terminal->description = $this->config->get("config_meta_description");
        $create_terminal->defaultPrice = 10;
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

    private function log($log) {
        $fp = fopen('invoice_payment.log', 'a+');
        fwrite($fp, $log);
        fclose($fp);
    }
}