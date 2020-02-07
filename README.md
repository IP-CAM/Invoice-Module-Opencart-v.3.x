<h1>Invoice Woocomerce plugin</h1>

<h3>Установка</h3>

1. [Скачайте плагин](https://github.com/Invoice-LLC/Invoice.Module.OpenCart/archive/master.zip) и скопируйте содержимое архива в корень сайта
2. Перейдите во вкладку Extensions->Extensions и выберите тип Payment
![Imgur](https://imgur.com/uhxNrVm.png)
3. Найдите плагин Invoice и нажмите установить
![Imgur](https://imgur.com/jwwaBuv.png)
4. Перейдите в управление плагином
![Imgur](https://imgur.com/Byr2Xx9.png)
5. Введите API ключ, логин от личного кабинета и включите плагин, затем сохраните настройки
![Imgur](https://imgur.com/d2Dgn5J.png)
(Все данные можно получить в [личном кабинете Invoice](https://lk.invoice.su/))
6. Добавьте уведомление в личном кабинете Invoice(Вкладка Настройки->Уведомления->Добавить)
с типом **WebHook** и адресом: **%URL сайта%/index.php?route=extension/payment/invoice/callback**<br>
![Imgur](https://imgur.com/lMmKhj1.png)
