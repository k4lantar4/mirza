<?php
session_start();
require_once '../config.php';
require_once '../jdf.php';
require_once '../function.php';

// فعال کردن نمایش خطاها
error_reporting(E_ALL);
ini_set('display_errors', 1);

// بررسی احراز هویت
if(!isset($_SESSION["user"])) {
    header('Location: login.php');
    exit;
}

$query = $pdo->prepare("SELECT * FROM admin WHERE username=:username");
$query->bindParam("username", $_SESSION["user"], PDO::PARAM_STR);
$query->execute();
$result = $query->fetch(PDO::FETCH_ASSOC);

if(!$result){
    header('Location: login.php');
    exit;
}

// پردازش درخواست بازنشانی
if(isset($_GET['action']) && $_GET['action'] == "reaset"){
    $default_keyboard = '{"keyboard":[[{"text":"text_sell"},{"text":"text_extend"}],[{"text":"text_usertest"},{"text":"text_wheel_luck"}],[{"text":"text_Purchased_services"},{"text":"accountwallet"}],[{"text":"text_affiliates"},{"text":"text_Tariff_list"}],[{"text":"text_support"},{"text":"text_help"}]]}';
    
    update("setting","keyboardmain",$default_keyboard,null,null);
    header('Location: keyboard.php');
    exit;
}

// پردازش آپدیت از طریق AJAX
if($_SERVER['REQUEST_METHOD'] == "POST"){
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if($data && is_array($data)){
        $keyboard_data = json_encode(['keyboard' => $data]);
        $result = update("setting","keyboardmain",$keyboard_data,null,null);
        
        // بررسی اینکه آپدیت موفق بوده
        if($result) {
            error_log("Keyboard updated successfully");
        } else {
            error_log("Keyboard update failed");
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
}

// دریافت داده کیبورد از دیتابیس - با دیباگ بیشتر
$keyboard_data = '{"keyboard":[[{"text":"text_sell"},{"text":"text_extend"}],[{"text":"text_usertest"},{"text":"text_wheel_luck"}],[{"text":"text_Purchased_services"},{"text":"accountwallet"}],[{"text":"text_affiliates"},{"text":"text_Tariff_list"}],[{"text":"text_support"},{"text":"text_help"}]]}';

try {
    $stmt = $pdo->prepare("SELECT value FROM setting WHERE name = 'keyboardmain'");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($row && !empty($row['value'])) {
        $keyboard_data = $row['value'];
        error_log("Loaded from DB: " . $keyboard_data);
    } else {
        error_log("No data in DB, using default");
    }
} catch(Exception $e) {
    error_log("DB Error: " . $e->getMessage());
}

// نام‌های فارسی
$button_names = [
    'text_sell' => 'فروش سرویس',
    'text_extend' => 'تمدید سرویس',
    'text_usertest' => 'تست کاربری',
    'text_wheel_luck' => 'چرخ شانس',
    'text_Purchased_services' => 'سرویس های خریداری شده',
    'accountwallet' => 'کیف پول و حساب کاربری',
    'text_affiliates' => 'همکاری در فروش',
    'text_Tariff_list' => 'لیست تعرفه ها',
    'text_support' => 'پشتیبانی',
    'text_help' => 'راهنما'
];
?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت کیبورد</title>
    <style>
        body { 
            font-family: Tahoma; 
            margin: 20px; 
            direction: rtl; 
            background: #f5f5f5;
        }
        .header { 
            background: white; 
            padding: 20px; 
            border-radius: 10px; 
            margin-bottom: 20px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
        }
        .btn { 
            padding: 10px 15px; 
            background: #007bff; 
            color: white; 
            text-decoration: none; 
            border-radius: 5px; 
            margin-left: 10px;
            border: none;
            cursor: pointer;
        }
        .btn-reset { background: #dc3545; }
        .keyboard-container { 
            background: white; 
            padding: 20px; 
            border-radius: 10px; 
            margin-bottom: 20px;
        }
        .keyboard-row { 
            background: #f8f9fa; 
            padding: 15px; 
            margin: 10px 0; 
            border-radius: 8px; 
            display: flex; 
            gap: 10px;
            min-height: 80px;
            border: 2px dashed #dee2e6;
            position: relative;
        }
        .keyboard-button { 
            padding: 15px; 
            background: #28a745; 
            color: white; 
            border-radius: 6px; 
            flex: 1; 
            text-align: center;
            cursor: move;
            position: relative;
            user-select: none;
        }
        .keyboard-button:hover {
            background: #218838;
        }
        .delete-btn {
            position: absolute;
            top: -8px;
            left: -8px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            font-size: 12px;
            cursor: pointer;
            display: none;
        }
        .keyboard-button:hover .delete-btn {
            display: block;
        }
        .delete-row-btn {
            position: absolute;
            top: -10px;
            left: -10px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            font-size: 14px;
            cursor: pointer;
            display: none;
        }
        .keyboard-row:hover .delete-row-btn {
            display: block;
        }
        .available-buttons {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        .available-button {
            padding: 12px 16px;
            background: #17a2b8;
            color: white;
            border-radius: 6px;
            margin: 5px;
            display: inline-block;
            cursor: move;
            user-select: none;
        }
        .available-button:hover {
            background: #138496;
        }
        .add-row-btn {
            background: #6f42c1;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 15px;
        }
        .drop-zone {
            border: 2px dashed #6c757d;
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #6c757d;
            margin: 10px 0;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>مدیریت صفحه کلید ربات</h1>
        <div>
            <a href="keyboard.php?action=reaset" class="btn btn-reset">بازنشانی به پیشفرض</a>
            <a href="index.php" class="btn">بازگشت به پنل</a>
        </div>
    </div>

    <div class="keyboard-container">
        <h2>صفحه کلید فعلی</h2>
        <button class="add-row-btn" onclick="addNewRow()">➕ افزودن ردیف جدید</button>
        <div id="keyboardRows"></div>
        <div class="drop-zone" id="emptyRow">
            ➕ برای ایجاد ردیف جدید، دکمه‌ها را به اینجا بکشید
        </div>
    </div>

    <div class="available-buttons">
        <h2>دکمه‌های موجود برای اضافه کردن</h2>
        <div id="availableButtons"></div>
    </div>

    <script>
        // داده‌های کیبورد از PHP
        const keyboardData = <?php echo $keyboard_data; ?>;
        console.log('Keyboard data loaded:', keyboardData);
        
        const buttonNames = {
            'text_sell': 'فروش سرویس',
            'text_extend': 'تمدید سرویس', 
            'text_usertest': 'تست کاربری',
            'text_wheel_luck': 'چرخ شانس',
            'text_Purchased_services': 'سرویس های خریداری شده',
            'accountwallet': 'کیف پول و حساب کاربری',
            'text_affiliates': 'همکاری در فروش',
            'text_Tariff_list': 'لیست تعرفه ها',
            'text_support': 'پشتیبانی',
            'text_help': 'راهنما'
        };

        // نمایش کیبورد
        function renderKeyboard() {
            console.log('Rendering keyboard...');
            const container = document.getElementById('keyboardRows');
            
            if (!keyboardData || !keyboardData.keyboard) {
                container.innerHTML = '<p>خطا در بارگذاری داده‌ها</p>';
                return;
            }

            let html = '';
            
            keyboardData.keyboard.forEach((row, rowIndex) => {
                if (row && row.length > 0) {
                    html += `
                        <div class="keyboard-row" data-row="${rowIndex}">
                            <button class="delete-row-btn" onclick="deleteRow(${rowIndex})">×</button>
                    `;
                    
                    row.forEach((button, btnIndex) => {
                        if (button && button.text) {
                            const text = buttonNames[button.text] || button.text;
                            html += `
                                <div class="keyboard-button" draggable="true" data-key="${button.text}">
                                    ${text}
                                    <button class="delete-btn" onclick="deleteButton('${button.text}', this)">×</button>
                                </div>
                            `;
                        }
                    });
                    
                    html += '</div>';
                }
            });

            container.innerHTML = html || '<p>هیچ دکمه‌ای وجود ندارد</p>';
            renderAvailableButtons();
            setupDragAndDrop();
        }

        // نمایش دکمه‌های موجود
        function renderAvailableButtons() {
            const container = document.getElementById('availableButtons');
            const usedButtons = getAllUsedButtons();
            const allButtons = Object.keys(buttonNames);
            const available = allButtons.filter(btn => !usedButtons.includes(btn));
            
            console.log('Used buttons:', usedButtons);
            console.log('Available buttons:', available);
            
            let html = '';
            available.forEach(btn => {
                html += `<div class="available-button" draggable="true" data-key="${btn}">${buttonNames[btn]}</div>`;
            });
            
            container.innerHTML = html || '<p>همه دکمه‌ها استفاده شده‌اند</p>';
            
            // اضافه کردن event listeners برای دکمه‌های موجود
            document.querySelectorAll('.available-button').forEach(btn => {
                btn.addEventListener('dragstart', handleDragStart);
            });
        }

        // دریافت تمام دکمه‌های استفاده شده
        function getAllUsedButtons() {
            const used = [];
            document.querySelectorAll('.keyboard-button').forEach(btn => {
                const key = btn.getAttribute('data-key');
                if (key) used.push(key);
            });
            return used;
        }

        // حذف دکمه
        function deleteButton(btnKey, element) {
            if(confirm('آیا از حذف این دکمه اطمینان دارید؟')) {
                const button = element.parentElement;
                button.remove();
                saveLayout();
                renderAvailableButtons();
            }
        }

        // حذف ردیف
        function deleteRow(rowIndex) {
            if(confirm('آیا از حذف کل این ردیف اطمینان دارید؟')) {
                const rows = document.querySelectorAll('.keyboard-row');
                if (rows[rowIndex]) {
                    rows[rowIndex].remove();
                    saveLayout();
                    renderAvailableButtons();
                }
            }
        }

        // افزودن ردیف جدید
        function addNewRow() {
            const container = document.getElementById('keyboardRows');
            const emptyRow = document.getElementById('emptyRow');
            
            const newRow = document.createElement('div');
            newRow.className = 'keyboard-row';
            newRow.setAttribute('data-row', Date.now()); // استفاده از timestamp برای شناسه منحصر بفرد
            newRow.innerHTML = `
                <button class="delete-row-btn" onclick="deleteRowFromElement(this.parentElement)">×</button>
                <div style="flex:1; display:flex; align-items:center; justify-content:center; color:#666;">
                    ردیف جدید - دکمه‌ها را به اینجا بکشید
                </div>
            `;
            
            container.appendChild(newRow);
            setupDragAndDropForRow(newRow);
        }

        // حذف ردیف از المنت
        function deleteRowFromElement(rowElement) {
            if(confirm('آیا از حذف این ردیف اطمینان دارید؟')) {
                rowElement.remove();
                saveLayout();
                renderAvailableButtons();
            }
        }

        // Drag and Drop
        let draggedItem = null;

        function handleDragStart(e) {
            draggedItem = this;
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', this.getAttribute('data-key'));
        }

        function setupDragAndDrop() {
            const rows = document.querySelectorAll('.keyboard-row');
            const emptyRow = document.getElementById('emptyRow');
            
            rows.forEach(row => {
                setupDragAndDropForRow(row);
            });

            // برای ردیف خالی
            emptyRow.addEventListener('dragover', handleDragOver);
            emptyRow.addEventListener('drop', handleDrop);
        }

        function setupDragAndDropForRow(row) {
            row.addEventListener('dragover', handleDragOver);
            row.addEventListener('drop', handleDrop);
            
            // برای دکمه‌های داخل ردیف
            row.querySelectorAll('.keyboard-button').forEach(btn => {
                btn.addEventListener('dragstart', handleDragStart);
            });
        }

        function handleDragOver(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        }

        function handleDrop(e) {
            e.preventDefault();
            const buttonKey = e.dataTransfer.getData('text/plain');
            
            if (!buttonKey) return;

            // اگر در ردیف خالی رها شده
            if (this.id === 'emptyRow') {
                addNewRow();
                const rows = document.querySelectorAll('.keyboard-row');
                const lastRow = rows[rows.length - 1];
                addButtonToRow(lastRow, buttonKey);
            } 
            // اگر در یک ردیف رها شده
            else if (this.classList.contains('keyboard-row')) {
                addButtonToRow(this, buttonKey);
            }

            // اگر از دکمه‌های موجود کشیده شده، از لیست حذفش کن
            if (draggedItem && draggedItem.classList.contains('available-button')) {
                draggedItem.remove();
            }

            saveLayout();
            renderAvailableButtons();
        }

        // اضافه کردن دکمه به ردیف
        function addButtonToRow(row, buttonKey) {
            const text = buttonNames[buttonKey];
            const button = document.createElement('div');
            button.className = 'keyboard-button';
            button.setAttribute('draggable', 'true');
            button.setAttribute('data-key', buttonKey);
            button.innerHTML = `
                ${text}
                <button class="delete-btn" onclick="deleteButton('${buttonKey}', this)">×</button>
            `;
            
            button.addEventListener('dragstart', handleDragStart);
            row.appendChild(button);
        }

        // ذخیره layout
        function saveLayout() {
            const rows = document.querySelectorAll('.keyboard-row');
            const layout = [];
            
            rows.forEach(row => {
                const rowButtons = [];
                row.querySelectorAll('.keyboard-button').forEach(btn => {
                    const key = btn.getAttribute('data-key');
                    if (key) {
                        rowButtons.push({ text: key });
                    }
                });
                if (rowButtons.length > 0) {
                    layout.push(rowButtons);
                }
            });

            console.log('Saving layout:', layout);

            fetch('keyboard.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(layout)
            })
            .then(response => {
                if (!response.ok) throw new Error('Network error');
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    console.log('Layout saved successfully');
                }
            })
            .catch(error => {
                console.error('Save error:', error);
                alert('خطا در ذخیره تغییرات');
            });
        }

        // شروع
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing...');
            renderKeyboard();
        });
    </script>
</body>
</html>
