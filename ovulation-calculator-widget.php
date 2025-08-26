<?php
/*
 * Plugin Name: Ovulation Calculator Widget with AJAX
 * Description: Виджет калькулятора овуляции для WordPress с расчетом без перезагрузки страницы.
 * Plugin URI: https://github.com/RobertoBennett/ovulation-calculator
 * Version: 1.4
 * Author: Robert Bennett
 * Text Domain: Conception Date Calculator
*/

// Функция для отображения формы калькулятора
function ovulation_calculator_widget() {
    ob_start();
    ?>
    <div class="ovulation-calculator">
        <h2>Калькулятор овуляции</h2>
        <form id="ovulation-form">
            <label>Дата начала последней менструации:</label>
            <input type="date" name="startDate" required>

            <label>Средняя длина цикла (дней):</label>
            <input type="number" name="cycleLength" required min="21" max="35">
            
            <label>Ваш возраст:</label>
            <input type="number" name="age" required min="18" max="50">

            <button type="submit">Рассчитать</button>
        </form>
        <div id="result"></div>
    </div>
    <?php
    return ob_get_clean();
}

// Функция обработки AJAX-запроса и расчета данных
function ovulation_calculator_ajax_handler() {
    $startDate = $_POST['startDate'];
    $cycleLength = (int) $_POST['cycleLength'];
    $age = (int) $_POST['age'];

    // Определяем параметры расчета по возрасту
    if ($age < 35) {
        $ovulationOffset = 14;
        $fertilityStartOffset = 5;
        $fertilityEndOffset = 1;
    } elseif ($age >= 35 && $age <= 40) {
        $ovulationOffset = 12;
        $fertilityStartOffset = 4;
        $fertilityEndOffset = 1;
    } else {
        $ovulationOffset = 10;
        $fertilityStartOffset = 3;
        $fertilityEndOffset = 0;
    }

    // Рассчитываем даты
    $ovulationDate = date('d.m.Y', strtotime($startDate . ' + ' . ($cycleLength - $ovulationOffset) . ' days'));
    $fertilityStart = date('d.m.Y', strtotime($ovulationDate . ' - ' . $fertilityStartOffset . ' days'));
    $fertilityEnd = date('d.m.Y', strtotime($ovulationDate . ' + ' . $fertilityEndOffset . ' days'));

    // Формируем результат в HTML
    $result = "<p><strong>День овуляции:</strong> $ovulationDate<br><small>День цикла, когда ожидается овуляция.</small></p>";
    $result .= "<p><strong>Начало окна фертильности:</strong> $fertilityStart<br><small>Первый день, когда фертильность может увеличиться.</small></p>";
    $result .= "<p><strong>Окончание фертильности:</strong> $fertilityEnd<br><small>Последний день, когда рождаемость, вероятно, будет высокой.</small></p>";

    // Возвращаем результат в формате JSON
    wp_send_json_success($result);
}

// Регистрируем AJAX-действия для авторизованных и неавторизованных пользователей
add_action('wp_ajax_nopriv_ovulation_calculator', 'ovulation_calculator_ajax_handler');
add_action('wp_ajax_ovulation_calculator', 'ovulation_calculator_ajax_handler');

// Регистрируем шорткод и подключаем стили и скрипты
function register_ovulation_calculator_shortcode() {
    add_shortcode('ovulation_calculator', 'ovulation_calculator_widget');
    add_action('wp_head', 'ovulation_calculator_styles');
    add_action('wp_footer', 'ovulation_calculator_scripts');
}

// Подключаем стили
function ovulation_calculator_styles() {
    ?>
    <style>
        .ovulation-calculator {
            background: #f9f6fb;
            border: 2px solid #e0d7f1;
            padding: 20px;
            border-radius: 8px;
            max-width: 300px;
            margin: 0 auto;
            font-family: Arial, sans-serif;
            color: #333;
        }
        
        .ovulation-calculator h2 {
            color: #764ba2;
        }
        
        .ovulation-calculator label {
            font-weight: bold;
            margin-top: 10px;
            display: block;
        }
        
        .ovulation-calculator input {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .ovulation-calculator button {
            background: #764ba2;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
        }
        
        .ovulation-calculator button:hover {
            background: #5c3b8e;
        }
        
        #result {
            margin-top: 15px;
            font-weight: bold;
            color: #764ba2;
        }
        
        #result p {
            margin: 5px 0;
        }
        
        #result small {
            color: #666;
            display: block;
            margin-top: 2px;
            font-size: 90%;
        }
    </style>
    <?php
}

// Подключаем JavaScript для AJAX
function ovulation_calculator_scripts() {
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('ovulation-form');
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const formData = new FormData(form);

                fetch("<?php echo admin_url('admin-ajax.php'); ?>?action=ovulation_calculator", {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('result').innerHTML = data.data;
                    } else {
                        document.getElementById('result').innerHTML = '<p>Ошибка в расчетах. Пожалуйста, попробуйте еще раз.</p>';
                    }
                })
                .catch(error => {
                    document.getElementById('result').innerHTML = '<p>Ошибка сервера. Пожалуйста, попробуйте позже.</p>';
                    console.error('Ошибка:', error);
                });
            });
        });
    </script>
    <?php
}

// Инициализация плагина
add_action('init', 'register_ovulation_calculator_shortcode');

