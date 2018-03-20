# ArsenalPay Plugin for VirtueMart of Joomla! CMS

*Arsenal Media LLC*  
[Arsenal Pay processing center](https://arsenalpay.ru/)

## Version
1.0.5

*Compatible with VirtueMart 2.6 & 3 for Joomla 2.5 & 3*

## Source
[Official integration guide page]( https://arsenalpay.ru/developers.html )

Basic feature list:

 * Allows seamlessly integrate unified payment widget into your site.
 * New payment method ArsenalPay will appear to pay for your products and services.
 * Allows to pay using mobile commerce and bank aquiring. More methods are about to become available. Please check for updates.
 * Supports two languages (Russian, English).
 
## How to install
1. Download the latest release of ArsenalPay payment plugin as zip archive from [releases](https://github.com/ArsenalPay/VirtueMart-ArsenalPay-CMS/releases)
2. Go to Joomla! administrator panel to install the plugin using extension manager.
3. After plugin successfully installed go to **Components->VirtueMart->Payment Methods**.
4. Create a new payment method. 
5. In the **Payment Method Information** tab:
 - In the **Payment Name** field enter the name of your payment;
 - Set the **Published** radio button to **Yes**;
 - In dropdown menu of **Payment Method** select  the payment method **ArsenalPay**;
 - In the top right toolbar, click **Save**. This step loads the configuration parameters of the payment method you have just created.
 - Go on the **Configuration** tab, and configure the payment method.

## Settings
1. Choose in **Components->VirtueMart->Payment Methods** created ArsenalPay payment method and go into it.
3. Click on **Configuration**. 
 - Fill out **widget**, **widgetKey**, **callbackKey** fields with your received widget, widget ket and key callbackKey.
 - Set **Callback URL** as `http(s)://[your-site-address]/index.php?option=com_virtuemart&view=pluginresponse&task=pluginnotification&tmpl=component&pm=arsenalpay`. Your online shop will be receiving callback requests about processed payments for automatically order status change. The callbacks will be sent onto this address.
 - If it is needed to check a payer order number before payment processing you should fill out the field of **Check URL** in the module settings with url-address to which ArsenalPay will be sending requests with check parameters. By default the address is the same with **Callback URL**. 
 - Set order statuses for pending, successful, holden, refunded, reversed and cancelled transactions.
 - You can specify ip address only from which it will be allowed to receive callback requests about payments onto your site in **Allowed IP address** field.

## How to uninstall
2. Delete created ArsenalPay method from **Components->VirtueMart->Payment Methods**.
3. Disable ArsenalPay plugin in Joomla! extension manager.
4. To delete files from your server unistall ArsenalPay using Joomla! extension manager.
4. Delete all tables from your database created by ArsenalPay if needed .

## Usage
After successful installation and proper settings new choice of payment method with ArsenalPay will appear on your site. To make payment for an order a payer will need to:

1. Choose goods from the shop catalog.
2. Go into the order page.
3. Choose the ArsenalPay payment method.
4. Check the order detailes and confirm the order.
5. After filling out the information depending on the payment type he will receive SMS about payment confirmation or will be redirected to the page with the result of his payment.

## Changelog
* 1.0.5 - Upgrade to move from frame to widget.
* 1.0.4 - Upgrade if you need to handle the amount in callback less that the total amount of the order. Fixed the cart emptying.

------------------
### О МОДУЛЕ
* Модуль платежной системы ArsenalPay под VirtueMart позволяет легко встроить платежную страницу на Ваш сайт.
* После установки модуля у Вас появится новый вариант оплаты товаров и услуг через платежную систему ArsenalPay.
* Платежная система ArsenalPay позволяет совершать оплату с различных источников списания средств: мобильных номеров (МТС/Мегафон/Билайн/TELE2), пластиковых карт (VISA/MasterCard/Maestro). Перечень доступных источников средств постоянно пополняется. Следите за обновлениями.
* Модуль поддерживает русский и английский языки.

За более подробной информацией о платежной системе ArsenalPay обращайтесь по адресу [arsenalpay.ru](http://arsenalpay.ru)

### УСТАНОВКА
1. Скачайте последний релиз платежного модуля ArsenalPay из [вкладки с релизами](https://github.com/ArsenalPay/VirtueMart-ArsenalPay-CMS/releases).
2. Зайдите в административную панель Joomla! и установите плагин через **Менеджер расширений**.
3. После успешной установки плагина зайдите в **Компоненты->VirtueMart->Способы оплаты**.
4. Там создайте новый метод оплаты, указав название для данного метода (например ArsenalPay) и изменив **Опубликовано** на **Да**;
5. В ниспадающем меню **Способ оплаты** выберите **ArsenalPay**;
6. После нажатия на **Сохранить**, Вам станут доступны настройки плагина.

### НАСТРОЙКИ
1. В **Компоненты->VirtueMart->Способы оплаты** выберите созданный во время установки метод оплаты через ArsenalPay;
2. Выберите закладку с настройками.
 - Заполните поля **widget**, **widgetKey** и **callbackKey**, присвоенными Вам номером виджета, ключом виджета и ключом для проверки подписи.
 - Ваш интернет-магазин будет получать уведомления о совершенных платежах: на адрес, указанный в поле **URL для обратного запроса**, от ArsenalPay будет поступать запрос с результатом платежа для фиксирования статусов заказа в системе предприятия.
 - Установите статусы заказов на время ожидания оплаты, после подтверждения платежа, неудавшегося платежа, полного возврата платежа, частичного возврата платежа, отказа от платежа, и случая, когда средства на карте были зарезервированы, но еще не списаны.
 - Вы можете задать ip-адрес, только с которого будут разрешены обратные запросы о совершаемых платежах, в поле **Разрешенный IP-адрес**.


### УДАЛЕНИЕ
1. Удалите метод оплаты ArsenalPay из методов оплат VirtueMart по пути **Компоненты->VirtueMart->Способы оплаты**
2. Деактивируйте ArsenalPay через менеджер расширений Joomla!
3. Чтобы удалить файлы с сервера, деинсталлируйте ArsenalPay через менеджер расширений Joomla!
4. Также при необходимости удалите созданные ArsenalPay таблицы в базе данных.

### ИСПОЛЬЗОВАНИЕ
После успешной установки и настройки модуля на сайте появится возможность выбора платежной системы ArsenalPay.
Для оплаты заказа с помощью платежной системы ArsenalPay нужно:

1. Выбрать из каталога товар, который нужно купить.
2. Перейти на страницу оформления заказа (покупки).
3. В разделе "Платежные системы" выбрать платежную систему ArsenalPay.
4. Перейти на страницу подтверждения введенных данных и ввода источника списания средств (мобильный номер, пластиковая карта и т.д.).
5. После ввода данных об источнике платежа, в зависимости от его типа, либо придет СМС о подтверждении платежа, либо покупатель будет перенаправлен на страницу с результатом платежа.

------------------
### ОПИСАНИЕ РЕШЕНИЯ
ArsenalPay – удобный и надежный платежный сервис для бизнеса любого размера. 

Используя платежный модуль от ArsenalPay, вы сможете принимать онлайн-платежи от клиентов по всему миру с помощью: 
пластиковых карт международных платёжных систем Visa и MasterCard, эмитированных в любом банке
баланса мобильного телефона операторов МТС, Мегафон, Билайн, Ростелеком и ТЕЛЕ2
различных электронных кошельков 

### Преимущества сервиса: 
 - [Самые низкие тарифы](https://arsenalpay.ru/tariffs.html)
 - Бесплатное подключение и обслуживание
 - Легкая интеграция
 - [Агентская схема: ежемесячные выплаты разработчикам](https://arsenalpay.ru/partnership.html)
 - Вывод средств на расчетный счет без комиссии
 - Сервис смс оповещений
 - Персональный личный кабинет
 - Круглосуточная сервисная поддержка клиентов 

А ещё мы можем взять на техническую поддержку ваш сайт и создать для вас мобильные приложения для Android и iOS. 

ArsenalPay – увеличить прибыль просто!  
Мы работаем 7 дней в неделю и 24 часа в сутки. А вместе с нами множество российских и зарубежных компаний. 

### Как подключиться: 
1. Вы скачали модуль и установили его у себя на сайте;
2. Отправьте нам письмом ссылку на Ваш сайт на pay@arsenalpay.ru либо оставьте заявку на [сайте](https://arsenalpay.ru/#registerModal) через кнопку "Подключиться";
3. Мы Вам вышлем коммерческие условия и технические настройки;
4. После Вашего согласия мы отправим Вам проект договора на рассмотрение.
5. Подписываем договор и приступаем к работе.

Всегда с радостью ждем ваших писем с предложениями. 

pay@arsenalpay.ru  
[arsenalpay.ru](https://arsenalpay.ru)



 
