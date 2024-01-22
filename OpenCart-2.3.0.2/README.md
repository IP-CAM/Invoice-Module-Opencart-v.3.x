<h1>Invoice OpenCart plugin</h1>

<h3>Установка</h3>

1. [Скачайте плагин](https://github.com/Invoice-LLC/Invoice.Module.OpenCart/archive/master.zip) и скопируйте содержимое архива в корень сайта
2. Перейдите во вкладку Extensions->Extensions и выберите тип Payment
![Imgur](https://imgur.com/uhxNrVm.png)
3. Найдите плагин Invoice и нажмите установить
![Imgur](https://imgur.com/jwwaBuv.png)
4. Перейдите в управление плагином
![Imgur](https://imgur.com/Byr2Xx9.png)
5. Введите API ключ, Id компании от личного кабинета и включите плагин, затем сохраните настройки
![Imgur](https://imgur.com/d2Dgn5J.png)
(Все данные можно получить в [личном кабинете Invoice](https://lk.invoice.su/))

<br>Api ключи и Merchant Id:<br>
![image](https://user-images.githubusercontent.com/91345275/196218699-a8f8c00e-7f28-451e-9750-cfa1f43f15d8.png)
![image](https://user-images.githubusercontent.com/91345275/196218722-9c6bb0ae-6e65-4bc4-89b2-d7cb22866865.png)<br>
<br>Terminal Id:<br>
![image](https://user-images.githubusercontent.com/91345275/196218998-b17ea8f1-3a59-434b-a854-4e8cd3392824.png)
![image](https://user-images.githubusercontent.com/91345275/196219014-45793474-6dfa-41e3-945d-fc669c916aca.png)<br>

6. Добавьте уведомление в личном кабинете Invoice(Вкладка Настройки->Уведомления->Добавить)
с типом **WebHook** и адресом: **%URL сайта%/index.php?route=extension/payment/invoice/callback**<br>
![Imgur](https://imgur.com/lMmKhj1.png)
