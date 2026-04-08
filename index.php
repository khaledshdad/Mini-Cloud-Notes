<?php
/**
 * مذكرتي السحابية - تطوير خالد شداد
 * بيئة التشغيل: Railway + PostgreSQL
 */

// 1. إعدادات الأخطاء (تظهر فقط في سجلات المنصة لضمان الأمان)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 2. الاتصال بقاعدة البيانات باستخدام الرابط الشامل أو المتغيرات المنفصلة
$databaseUrl = getenv('DATABASE_URL');

try {
    if ($databaseUrl) {
        // إذا توفر DATABASE_URL، نقوم بتحليله
        $dbParts = parse_url($databaseUrl);
        $host = $dbParts['host'];
        $user = $dbParts['user'];
        $pass = $dbParts['pass'];
        $db   = ltrim($dbParts['path'], '/');
        $port = $dbParts['port'] ?: '5432';
    } else {
        // العودة للمتغيرات المنفصلة إذا لم يتوفر الرابط الشامل
        $host = getenv('PGHOST');
        $user = getenv('PGUSER');
        $pass = getenv('PGPASSWORD');
        $db   = getenv('PGDATABASE');
        $port = getenv('PGPORT') ?: '5432';
    }

    // بناء نص الاتصال (DSN) مع إضافة خيارات التوافق مع السحاب
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=prefer";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5
    ]);

    // 3. إنشاء الجدول تلقائياً إذا لم يكن موجوداً
    $pdo->exec("CREATE TABLE IF NOT EXISTS notes (
        id SERIAL PRIMARY KEY, 
        content TEXT NOT NULL, 
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 4. معالجة إضافة ملاحظة جديدة
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty(trim($_POST['note']))) {
        $stmt = $pdo->prepare("INSERT INTO notes (content) VALUES (?)");
        $stmt->execute([$_POST['note']]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // 5. جلب الملاحظات لعرضها
    $stmt = $pdo->query("SELECT * FROM notes ORDER BY created_at DESC");
    $notes = $stmt->fetchAll();

} catch (PDOException $e) {
    // تسجيل الخطأ تقنياً وإظهار رسالة واجهة مستخدم نظيفة
    error_log("Connection Failed: " . $e->getMessage());
    die("<div style='direction:rtl; text-align:center; padding:50px; background:#0f172a; color:white; min-height:100vh; font-family:sans-serif;'>
            <h2 style='color:#f87171;'>فشل الاتصال بالسحابة ☁️</h2>
            <p>تأكد من ربط قاعدة بيانات PostgreSQL في مشروعك على Railway.</p>
            <small style='color:#4b5563;'>الخطأ التقني: " . $e->getMessage() . "</small>
         </div>");
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مذكرتي السحابية | خالد شداد</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap');
        body { 
            background: radial-gradient(circle at top right, #1e293b, #0f172a); 
            min-height: 100vh; 
            color: white; 
            font-family: 'Tajawal', sans-serif; 
        }
        .glass { 
            background: rgba(255, 255, 255, 0.03); 
            backdrop-filter: blur(12px); 
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1); 
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }
        .note-card {
            transition: transform 0.2s ease;
        }
        .note-card:hover {
            transform: translateY(-2px);
            background: rgba(255, 255, 255, 0.06);
        }
    </style>
</head>
<body class="p-4 md:p-8">
    <div class="max-w-2xl mx-auto">
        <header class="text-center mb-12">
            <h1 class="text-4xl font-bold bg-clip-text text-transparent bg-gradient-to-l from-blue-400 to-emerald-400">
                مذكرتي السحابية 🚀
            </h1>
            <p class="text-gray-400 mt-2">نظام تخزين سحابي مدعوم بـ PostgreSQL</p>
        </header>

        <section class="glass p-6 rounded-3xl mb-10">
            <form method="POST" class="space-y-4">
                <textarea 
                    name="note" 
                    rows="3"
                    class="w-full bg-white/5 border border-white/10 rounded-2xl p-4 focus:outline-none focus:ring-2 focus:ring-blue-500/50 transition placeholder-gray-500" 
                    placeholder="ما الذي يدور في ذهنك يا خالد؟" 
                    required></textarea>
                <button 
                    type="submit" 
                    class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 px-6 rounded-2xl shadow-lg shadow-blue-900/20 transition-all active:scale-95">
                    حفظ في السحابة
                </button>
            </form>
        </section>

        <div class="space-y-4">
            <h2 class="text-xl font-semibold mb-4 text-gray-300 flex items-center gap-2">
                <span class="w-2 h-2 bg-emerald-400 rounded-full"></span>
                آخر الملاحظات
            </h2>
            
            <?php if (empty($notes)): ?>
                <div class="text-center py-10 opacity-40">
                    <p>لا توجد ملاحظات حالياً.. ابدأ بالكتابة!</p>
                </div>
            <?php endif; ?>

            <?php foreach($notes as $row): ?>
                <div class="glass p-6 rounded-2xl note-card">
                    <p class="text-gray-100 leading-relaxed mb-3">
                        <?php echo nl2br(htmlspecialchars($row['content'])); ?>
                    </p>
                    <div class="flex justify-between items-center border-t border-white/5 pt-3">
                        <span class="text-xs text-blue-400 font-medium tracking-wide uppercase">Stored @ Cloud</span>
                        <time class="text-xs text-gray-500 italic">
                            <?php echo date('Y/m/d - H:i', strtotime($row['created_at'])); ?>
                        </time>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <footer class="mt-16 text-center text-gray-600 text-sm">
            <p>تم التطوير بواسطة <b>خالد شداد</b> &copy; 2026</p>
        </footer>
    </div>
</body>
</html>
