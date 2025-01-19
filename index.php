<?php

include_once __DIR__ . '\src\amocrm_auth.php';
include_once __DIR__ . '\src\vault\vault.php';
require 'vendor/autoload.php';


$result = handleAmoCRMAuth(CODE);
?>


<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Форма</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f5f5f5;
        }
        .form-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        .form-container h2 {
            margin-bottom: 20px;
            font-size: 24px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }
        .form-group input:focus {
            border-color: #007bff;
            outline: none;
        }
        .form-group button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            border: none;
            border-radius: 5px;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }
        .form-group button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Заполните форму</h2>
        <form action="src/form_sender.php" method="POST">
            <div class="form-group">
                <label for="name">Имя</label>
                <input type="text" id="name" name="name" placeholder="Введите ваше имя" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Введите ваш email" required>
            </div>
            <div class="form-group">
                <label for="phone">Телефон</label>
                <input type="tel" id="phone" name="phone" placeholder="Введите ваш номер телефона" required>
            </div>
            <div class="form-group">
                <label for="message">Цена</label>
                <input type="text" id="message" name="message" placeholder="Введите цену" required>
            </div>
            <input type="hidden" id="spent_time" name="spent_time" value="0">
            <div class="form-group">
                <button type="submit">Отправить</button>
            </div>
        </form>
    </div>
    <script>
        let timeSpent = 0;

        const timer = setInterval(() => {
            timeSpent++;
            if (timeSpent >= 30) {
                document.getElementById('spent_time').value = '1'; 
                clearInterval(timer);
            }
        }, 1000);
    </script>
</body>
</html>
