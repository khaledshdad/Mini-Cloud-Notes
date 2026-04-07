<?php
// 1. تفعيل عرض الأخطاء فوراً لمعرفة المشكلة
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 2. جلب متغيرات البيئة
$host = getenv('PGHOST');
$user = getenv('PGUSER');
$pass = getenv('PGPASSWORD');
$db   = getenv('PGDATABASE');
$port = getenv('PGPORT') ?: '5432';

// فحص أولي: هل المتغيرات فارغة؟
if (!$host) {
    die("خطأ: لم يتم العثور على بيانات قاعدة البيانات (PGHOST). تأكد من تبويب Variables.");
}

try {
    // 3. الاتصال باستخدام PDO
    $dsn = "pgsql:host=$host;port=$port;dbname=$db";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5 // مهلة 5 ثوانٍ للاتصال
    ]);

    // إنشاء الجدول
    $pdo->exec("CREATE TABLE IF NOT EXISTS notes (id SERIAL PRIMARY KEY, content TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

    // معالجة الإرسال
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['note'])) {
        $stmt = $pdo->prepare("INSERT INTO notes (content) VALUES (?)");
        $stmt->execute([$_POST['note']]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // جلب البيانات
    $stmt = $pdo->query("SELECT * FROM notes ORDER BY created_at DESC");
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // سيظهر لك هذا السطر في المتصفح ليخبرك بالسبب الحقيقي للانهيار
    die("فشل التطبيق في الاتصال بـ Postgres: " . $e->getMessage());
}
?>
