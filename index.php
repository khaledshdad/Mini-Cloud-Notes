<?php
// جلب بيانات قاعدة البيانات من متغيرات بيئة Railway
$host = getenv('MYSQLHOST') ?: 'localhost';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';
$db   = getenv('MYSQLDATABASE') ?: 'test';
$port = getenv('MYSQLPORT') ?: '3306';

$conn = new mysqli($host, $user, $pass, $db, $port);

// إنشاء الجدول تلقائياً إذا لم يكن موجوداً (للتسهيل في أول تجربة)
$conn->query("CREATE TABLE IF NOT EXISTS notes (id INT AUTO_INCREMENT PRIMARY KEY, content TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

// إضافة ملاحظة جديدة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['note'])) {
    $note = $conn->real_escape_string($_POST['note']);
    $conn->query("INSERT INTO notes (content) VALUES ('$note')");
}

// جلب الملاحظات
$result = $conn->query("SELECT * FROM notes ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تجربة نُشر على Railway</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 15px;
        }
    </style>
</head>
<body class="p-8 text-white">
    <div class="max-w-lg mx-auto">
        <h1 class="text-3xl font-bold mb-6 text-center">مذكرتي السحابية 🚀</h1>
        
        <form method="POST" class="glass p-6 mb-8">
            <textarea name="note" class="w-full bg-transparent border border-white/30 rounded p-2 text-white placeholder-white/50 focus:outline-none" placeholder="اكتب شيئاً هنا..." required></textarea>
            <button type="submit" class="w-full mt-4 bg-white/20 hover:bg-white/30 transition p-2 rounded font-bold">إضافة الملاحظة</button>
        </form>

        <div class="space-y-4">
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="glass p-4 animate-fade-in">
                    <p><?php echo htmlspecialchars($row['content']); ?></p>
                    <small class="text-white/40"><?php echo $row['created_at']; ?></small>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html>
