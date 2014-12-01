# ArsenalPay Plugin for VirtueMart of Joomla! CMS

[Arsenal Media LLC](http://www.arsenalmedia.ru/index.php/en)

[Arsenal Pay processing center](https://arsenalpay.ru/)

## Version
1.0.0

*Compatible with VirtueMart 2.6 for Joomla 2.5*

## Source
[Official integration guide page]( https://arsenalpay.ru/site/integration/ )

Basic feature list:

 * Allows seamlessly integrate unified payment frame into your site.
 * New payment method ArsenalPay will appear to pay for your products and services.
 * Allows to pay using mobile commerce and bank aquiring. More methods are about to become available. Please check for updates.
 * Supports two languages (Russian, English).
 
##How to install
1. Download as zip archive from 
`https://github.com/...`
2. Go to Joomla! administrator panel to install the plugin using extension manager.
3. After plugin successfully installed go to **Components->VirtueMart->Payment Methods**.
4. Create new payment method. 
5. In the **Payment Method Information** tab:
 - In the **Payment Name** field enter the name of your payment;
 - Set the **Published** radio button to **Yes**;
 - In dropdown **Payment Method** select  the payment method **ArsenalPay**;
 - In the top right toolbar, click **Save**. This step loads the configuration parameters of the payment method you have just created.
 - Go on the **Configuration** tab, and configure the payment method.

## Settings
1. Choose in **Components->VirtueMart->Payment Methods** created ArsenalPay payment method and go into it.
3. Click on **Configuration**. 
 - Fill out **Unique token**, **Sign key** fields with your received token and key.
 - Set **Frame URL** as `https://arsenalpay.ru/payframe/pay.php`
 - Set **Payment type** as `card` to activate payments with bank cards or `mk` to activate payments from mobile phone accounts.
 - **css parameter**. You can specify CSS file to apply it to the view of payment frame by inserting its url.
 - You can specify ip address only from which it will be allowed to receive callback requests about payments onto your site in **Allowed IP address** field.
 - Set **Callback URL** as `http(s)://[your-site-address]/index.php?option=com_virtuemart&view=pluginresponse&task=pluginnotification&tmpl=component&pm=arsenalpay`. Your online shop will be receiving callback requests about processed payments for automatically change of order status. The callbacks will be sent onto this address.
 - If it is needed to check a payer order number before payment processing you should fill out the field of **Check URL** in the module settings with url-address to which ArsenalPay will be sending requests with check parameters. By default the address is the same with **Callback URL**. 
 - Set order statuses for successful, pending and failed transactions.
 - You can display payment page inside frame at your site, or to redirect a payer directly to the payment page url.
 - You can adjust **width**, **height**, **frameborder** and **scrolling** of ArsenalPay payment frame by setting iframe parameters.

## How to uninstall
2. Delete created ArsenalPay method from **Components->VirtueMart->Payment Methods**.
3. To delete files from your server unistall ArsenalPay using Joomla! extension manager.
4. Delete all tables from your database created by ArsenalPay if needed.

## Usage
After successful installation and proper settings new choice of payment method with ArsenalPay will appear on your site. To make payment for an order a payer will need to:

1. Choose goods from the shop catalog.
2. Go into the order page.
3. Choose the ArsenalPay payment method.
4. Check the order detailes and confirm the order.
5. After filling out the information depending on the payment type he will receive SMS about payment confirmation or will be redirected to the page with the result of his payment.

------------------
### О МОДУЛЕ
* Модуль платежной системы ArsenalPay под Joomshopping позволяет легко встроить платежную страницу на Ваш сайт.
* После установки модуля у Вас появится новый вариант оплаты товаров и услуг через платежную систему ArsenalPay.
* Платежная система ArsenalPay позволяет совершать оплату с различных источников списания средств: мобильных номеров (МТС/Мегафон/Билайн/TELE2), пластиковых карт (VISA/MasterCard/Maestro). Перечень доступных источников средств постоянно пополняется. Следите за обновлениями.
* Модуль поддерживает русский и английский языки.

За более подробной информацией о платежной системе ArsenalPay обращайтесь по адресу [arsenalpay.ru](http://arsenalpay.ru)

### УСТАНОВКА
1. Скачайте  zip архив с платежным плагином ArsenalPay на `http://guthub` .
2. Зайдите в административную панель Joomla! и установите плагин через **Менеджер расширений**.
3. После успешной установки плагина зайдите в **Компоненты->VirtueMart->Методы оплаты**.
4. Там создайте новый метод оплаты, указав название для данного метода (например ArsenalPay) и изменив **Опубликовано** на **Да**;
5. После нажатия на **Сохранить**, Вам станут доступны настройки плагина.

### НАСТРОЙКИ
1. В **Компоненты->VirtueMart->Методы оплаты** выберите созданный во время установки метод оплаты через ArsenalPay;
2. Выберите закладку с настройками.
  - Заполните поля **Уникальный токен** и **Ключ (key) **, присвоенными Вам токеном и ключом для подписи.
 - Установите **URL-адрес фрейма** как `https://arsenalpay.ru/payframe/pay.php`
 - Установите **Тип оплаты** как `card` для активации платежей с пластиковых карт или  как `mk` — платежей с аккаунтов мобильных телефонов.
 - Вы можете задать **Параметр css** для применения к отображению платежного фрейма, указав url css-файла.
 - Вы можете задать ip-адрес, только с которого будут разрешены обратные запросы о совершаемых платежах, в поле **Разрешенный IP-адрес**.
 - Для **URL для обратного запроса** задайте значение `http(s)://[ваш-адрес-сайта]/index.php?option=com_virtuemart&view=pluginresponse&task=pluginnotification&tmpl=component&pm=arsenalpay`. Ваш интернет-магазин будет получать уведомления о совершенных платежах: на адрес, указанный в этом поле, от ArsenalPay будет поступать запрос с результатом платежа для фиксирования статусов заказа в системе предприятия. 
 - При необходимости осуществления проверки номера заказа перед проведением платежа, Вы должны заполнить поле **URL для проверки**, на который от ArsenalPay будет поступать запрос на проверку. По умолчанию значение совпадает с **URL для обратного запроса**.
 - Установите статус для успешных, ожидаемых и неудавшихся платежей.
 - Вы можете отображать платежную страницу внутри фрейма на Вашем сайте, либо перенаправлять пользователя напрямую по адресу платежной страницы.
 - Вы можете подгонять ширину, высоту, границу и прокрутку платежного фрейма, задавая соответствующие значения параметров iframe.

### УДАЛЕНИЕ
1. Удалите метод оплаты ArsenalPay из методов оплат VirtueMart по пути **Компоненты->VirtueMart->Методы оплаты**
2. Чтобы удалить файлы с сервера, деинсталлируйте ArsenalPay через менеджер расширений Joomla!
3. Также при необходимости удалите созданные ArsenalPay таблицы в базе данных.

### ИСПОЛЬЗОВАНИЕ
После успешной установки и настройки модуля на сайте появится возможность выбора платежной системы ArsenalPay.
Для оплаты заказа с помощью платежной системы ArsenalPay нужно:

1. Выбрать из каталога товар, который нужно купить.
2. Перейти на страницу оформления заказа (покупки).
3. В разделе "Платежные системы" выбрать платежную систему ArsenalPay.
4. Перейти на страницу подтверждения введенных данных и ввода источника списания средств (мобильный номер, пластиковая карта и т.д.).
5. После ввода данных об источнике платежа, в зависимости от его типа, либо придет СМС о подтверждении платежа, либо покупатель будет перенаправлен на страницу с результатом платежа.



 
