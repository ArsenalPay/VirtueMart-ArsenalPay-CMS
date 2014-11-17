# ArsenalPay for VirtueMart of Joomla! CMS

[Arsenal Pay processing center](https://arsenalpay.ru/)

## Version
1.0.0

Basic feature list:

 * Module allows seamlessly integrate unified payment frame into your Joomla! with VirtueMart site 
 * New payment method will appear to pay for your products and services
 * Allows to pay using mobile commerce and bank aquiring. More about to come.
 
##How to install
1. Download the latest version  of plugin version using
`git clone https://github.com/...`
2. Go to Joomla! administrator panel to install the plugin using extension manager.
3. After plugin successfully installed go to **Components->VirtueMart->Payment Methods**
4. Create new payment method. 
5. In the **Payment Method Information** tab:
	In the **Payment Name** field enter the name of your payment
	Set the **Published** radio button to **Yes**
	In dropdown **Payment Method** select  the payment method **ArsenalPay**
	In the top right toolbar, click Save. This step will load the configuration parameters of the payment method you just created.
	Go on the Configuration tab, and configure the payment method.

## Settings
1. Choose in **Components->VirtueMart->Payment Methods** choose created ArsenalPay payment method and go into it.
3. Click on **Configuration**. 
4. Make proper settings and save.
Callback URL-address:
`[ваш-адрес-сайта]/index.php?option=com_virtuemart&view=pluginresponse&task=pluginnotification&tmpl=component&pm=arsenalpay`

## How to uninstall
2. Delete created ArsenalPay method from **Components->VirtueMart->Payment Methods**.
3. To delete files from your server unistall ArsenalPay using Joomla! extension manager.
4. Delete all tables from your database created by ArsenalPay.

##Usage
After successful installation and proper settings new choice of payment method with ArsenalPay will appear on your site. To make payment for an order you will need:

1. Choose goods from the shop catalog.
2. Go into the order page.
3. Choose the ArsenalPay payment method.
4. Check the order detailes and confirm the order.
5. After filling out the information depending on your payment type you will receive SMS about payment confirmation or will be redirected to the page with the result of your payment.
6. Your online shop can receive callbacks about processed payments if needed. The callbacks will be received for fixing payment statuses onto the address assigned in the field **Callback URL** of the payment module settings.
7. If it is needed to make checking of payer order number before the payment processing you should fill out the field of **Check URL** in the module settings with address to which ArsenalPay will be sending requests with check parameters. By default the address is the same with **Callback URL**. 

------------------
### О МОДУЛЕ
* Модуль платежной системы ArsenalPay под VirtueMart позволяет легко встроить платежную страницу на Ваш сайт.
* После установки модуля у Вас появится новый вариант оплаты товаров и услуг через платежную систему ArsenalPay.
* Платежная система ArsenalPay позволяет совершать оплату с различных источников списания средств: мобильных номеров (МТС/Мегафон/Билайн/TELE2), пластиковых карт (VISA/MasterCard/Maestro). Перечень доступных источников средств постоянно пополняется. Следите за обновлениями.

За более подробной информацией о платежной системе ArsenalPay обращайтесь по адресу [arsenalpay.ru](http://arsenalpay.ru)

### УСТАНОВКА
1. Скачайте zip архив с плагином.
2. Зайдите в административную панель Joomla! и установите плагин через **Менеджер расширений**.
3. После успешной установки плагина зайдите в **Компоненты->VirtueMart->Методы оплаты**.
4. Там создайте новый метод оплаты, указав название для данного метода (например ArsenalPay) и изменив **Опубликовано** на **Да**;
5. После нажатия на **Сохранить**, вам станут доступны настройки плагина.

### НАСТРОЙКИ
1. В **Компоненты->VirtueMart->Методы оплаты** выберите созданный во время установки метод оплаты через ArsenalPay;
2. Выберите закладку с настройками.
3. Заполните поля **Уникальный токен**, **Ключ**. 
6. Заполните необходимые настройки и нажмите сохранить.

Адрес колбэка
`[ваш-адрес-сайта]/index.php?option=com_virtuemart&view=pluginresponse&task=pluginnotification&tmpl=component&pm=arsenalpay`
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
5. После ввода данных об источнике платежа в зависимости от его типа, Вам либо придет СМС о подтверждении платежа, либо Вы будуете перенаправлены на страницу с результатом платежа.
6. При необходимости, предприятие может получать уведомления о совершенных платежах: на адрес, указанный в поле "Url колбэка", от ArsenalPay поступит запрос с результатом платежа для фиксирования его в системе предприятия.
7. При необходимости осуществления проверки номера получателя перед совершением платежа, Вы должны заполнить поле "Url проверки номера получателя", на который от ArsenalPay поступит запрос на проверку.
 



 
