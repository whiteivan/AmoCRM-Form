<?php



require __DIR__ . '/../vendor/autoload.php';
use AmoCRM\Client\AmoCRMApiClient; 
use AmoCRM\Exceptions\AmoCRMApiException; 
use AmoCRM\Models\ContactModel;
use AmoCRM\Collections\ContactsCollection;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultitextCustomFieldValueModel;
use AmoCRM\Models\LeadModel;
use AmoCRM\Collections\LinksCollection;
use AmoCRM\Models\CustomFieldsValues\NumericCustomFieldValuesModel; 
use AmoCRM\Models\CustomFieldsValues\ValueCollections\NumericCustomFieldValueCollection; 
use AmoCRM\Models\CustomFieldsValues\ValueModels\NumericCustomFieldValueModel; 
use AmoCRM\Models\CustomFieldsValues\TextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\TextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\TextCustomFieldValueModel;
use AmoCRM\Filters\ContactsFilter; 
use League\OAuth2\Client\Token\AccessTokenInterface;

session_start();
include_once __DIR__ . '\vault\vault.php';

const BASE_DOMAIN = 'beloivanenkodanil.amocrm.ru'; 

const LOG_FILE = __DIR__ . '/log.txt';

function logMessage($message)
{
    file_put_contents(LOG_FILE, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

logMessage('Начало обработки формы');

function getToken()
{
    $token_path = __DIR__ . '/vault/token_info.json';
    logMessage('Попытка загрузить токен из файла: ' . $token_path);

    if (!file_exists($token_path)) {
        logMessage('Ошибка: файл токена не найден!');
        die('Файл с токеном не найден!');
    }

    $token_data = json_decode(file_get_contents($token_path), true);
    logMessage('Токен успешно загружен');
    return new \League\OAuth2\Client\Token\AccessToken($token_data);
}


$name = $_POST['name'] ?? null;
$email = $_POST['email'] ?? null;
$phone = $_POST['phone'] ?? null;
$price = $_POST['message'] ?? null;
$spent_time = $_POST['spent_time'] ?? '0';

logMessage('Получены данные из формы: ' . json_encode(['name' => $name, 'email' => $email, 'phone' => $phone, 'price' => $price]));


if (!$name || !$email || !$phone || !$price) {
    logMessage('Ошибка: не все поля заполнены');
    die('Все поля формы обязательны для заполнения.');
}

try {
    $api_client = new AmoCRMApiClient(INTEGRATION_ID, SECRET, REDIRECT_URI);
    $access_token = getToken();
    $api_client->setAccessToken($access_token)
        ->setAccountBaseDomain(BASE_DOMAIN);
    $contact = new ContactModel();
    $contact->setName($name);

    $custom_fields = $contact->getCustomFieldsValues();
    if ($custom_fields === null) {
        $custom_fields = new \AmoCRM\Collections\CustomFieldsValuesCollection();
        $contact->setCustomFieldsValues($custom_fields);
    }
    $phone_field = $custom_fields->getBy('fieldCode', 'PHONE');
    if (empty($phone_field)) {
        $phone_field = (new MultitextCustomFieldValuesModel())->setFieldCode('PHONE');
        $custom_fields->add($phone_field);
    }
    $phone_field->setValues(
        (new MultitextCustomFieldValueCollection())
            ->add(
                (new MultitextCustomFieldValueModel())
                    ->setEnum('WORKDD')
                    ->setValue($phone)
            )
    );

    $email_field = $custom_fields->getBy('fieldCode', 'EMAIL');
    if (empty($email_field)) {
        $email_field = (new MultitextCustomFieldValuesModel())->setFieldCode('EMAIL');
        $custom_fields->add($email_field);
    }

    $email_field->setValues(
        (new MultitextCustomFieldValueCollection())
            ->add(
                (new MultitextCustomFieldValueModel())
                    ->setEnum('WORK')
                    ->setValue($email)
            )
    );

    $is_30 = $custom_fields->getBy('fieldId', 1271621);
    if (!$is_30) {
        $is_30 = (new TextCustomFieldValuesModel())->setFieldId(1271621);
        $custom_fields->add($is_30);
    }

    $text_value_collection = new TextCustomFieldValueCollection();
    $text_value_collection->add(
        (new TextCustomFieldValueModel())->setValue($spent_time)
    );

    $is_30->setValues($text_value_collection);
    try {
        $contact_model = $api_client->contacts()->addOne($contact);
    } catch (AmoCRMApiException $e) {
        if ($e instanceof \AmoCRM\Exceptions\AmoCRMApiErrorResponseException) {
            $validationErrors = $e->getValidationErrors();
            echo 'Детали ошибки валидации:' . PHP_EOL;
            print_r($validationErrors);
        }
        die;
    }

    $lead = new \AmoCRM\Models\LeadModel();
    $lead->setName('Сделка для ' . $name)
         ->setPrice(5000); 

    // Обработка числового поля с ID 1273933
    $lead_custom_fields = $lead->getCustomFieldsValues();
    if ($lead_custom_fields === null) {
        $lead_custom_fields = new \AmoCRM\Collections\CustomFieldsValuesCollection();
        $lead->setCustomFieldsValues($lead_custom_fields);
    }
   $price_field = $lead_custom_fields->getBy('fieldId', 1273933);
    if (!$price_field ) {
       $price_field = (new \AmoCRM\Models\CustomFieldsValues\NumericCustomFieldValuesModel())
            ->setFieldId(1273933);
        $lead_custom_fields->add($price_field );
    }
   $price_field->setValues(
        (new NumericCustomFieldValueCollection())
            ->add(
                (new NumericCustomFieldValueModel())->setValue($price)
            )
    );

    $lead->setContacts(
        (new \AmoCRM\Collections\ContactsCollection())->add($contact_model)
    );

    try {
       $lead_model = $api_client->leads()->addOne($lead);
    } catch (AmoCRMApiException $e) {
        echo 'Ошибка при создании сделки: ' . $e->getMessage();
        die;
    }
} catch (Exception $e) {
    $errorMessage = 'Общая ошибка: ' . $e->getMessage();
    logMessage($errorMessage);
    echo $errorMessage;
    die();
}
