<?php
// 1. تفعيل عرض الأخطاء (للتصحيح)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 2. جلب المتغيرات من Railway
$host = getenv('PGHOST');
$user = getenv('PGUSER');
$pass = getenv('PGPASSWORD');
$db   = getenv('PGDATABASE');
$port = getenv('PGPORT') ?: '5432';

try {
    // 3. الاتصال باستخدام PDO
    $dsn = "pgsql:host=$host;port=$port;dbname=$db";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // 4. إنشاء الجدول تلقائياً
    $pdo->exec("CREATE TABLE IF NOT EXISTS notes (
        id SERIAL PRIMARY KEY, 
        content TEXT NOT NULL, 
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 5. إضافة ملاحظة
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['note'])) {
        $stmt = $pdo->prepare("INSERT INTO notes (content) VALUES (?)");
        $stmt->execute([$_POST['note']]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // 6. جلب الملاحظات
    $stmt = $pdo->query("SELECT * FROM notes ORDER BY created_at DESC");
    $notes = $stmt->fetchAll();

} catch (PDOException $e) {
    // إذا فشل الاتصال، ستعرف السبب هنا بدلاً من رسالة Railway العامة
    die("<div style='direction:rtl; text-align:center; padding:50px; font-family:sans-serif;'>
            <h2>فشل الاتصال بالسحابة ☁️</h2>
            <p style='color:red;'>السبب: " . $e->getMessage() . "</p>
            <p>تأكد من إضافة ملف composer.json وتفعيل متغيرات البيئة.</p>
         </div>");
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>مذكرتي السحابية | خالد شداد</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: #0f172a; min-height: 100vh; color: white; font-family: sans-serif; }
        .glass { background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; }
    </style>
</head>
<body class="p-8">
    <div class="max-w-lg mx-auto">
        <h1 class="text-3xl font-bold mb-8 text-center text-blue-400">مذكرتي السحابية 🚀</h1>
        
        <form method="POST" class="glass p-6 mb-8">
            <textarea name="note" class="w-full bg-white/10 rounded-xl p-4 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="اكتب فكرتك هنا..." required></textarea>
            <button type="submit" class="w-full mt-4 bg-blue-600 hover:bg-blue-500 p-3 rounded-xl font-bold transition">حفظ في PostgreSQL</button>
        </form>

        <div class="space-y-4">
            <?php foreach($notes as $row): ?>
                <div class="glass p-5">
                    <p class="text-lg"><?php echo htmlspecialchars($row['content']); ?></p>
                    <small class="text-white/30"><?php echo $row['created_at']; ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
