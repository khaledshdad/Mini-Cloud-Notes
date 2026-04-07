<?php
/**
 * Developed by: Khaled Shdad
 * Project: Mini Cloud Notes (Railway Test)
 * Database: PostgreSQL
 */

// عرض الأخطاء مؤقتًا لتصحيح المشاكل
ini_set('display_errors', 1);
error_reporting(E_ALL);

// جلب بيانات Postgres من متغيرات بيئة Railway
$host = $_ENV['PGHOST'] ?? getenv('PGHOST');
$user = $_ENV['PGUSER'] ?? getenv('PGUSER');
$pass = $_ENV['PGPASSWORD'] ?? getenv('PGPASSWORD');
$db   = $_ENV['PGDATABASE'] ?? getenv('PGDATABASE');
$port = $_ENV['PGPORT'] ?? getenv('PGPORT') ?? '5432';

$notes = [];

try {
    // الاتصال باستخدام PDO المخصص لـ PostgreSQL
    $dsn = "pgsql:host=$host;port=$port;dbname=$db";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // إنشاء الجدول تلقائياً بصيغة Postgres
    $pdo->exec("CREATE TABLE IF NOT EXISTS notes (
        id SERIAL PRIMARY KEY, 
        content TEXT, 
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // إضافة ملاحظة جديدة
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['note'])) {
        $stmt = $pdo->prepare("INSERT INTO notes (content) VALUES (?)");
        $stmt->execute([$_POST['note']]);
        
        // إعادة التوجيه لمنع تكرار الإرسال عند تحديث الصفحة
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // جلب الملاحظات
    $stmt = $pdo->query("SELECT * FROM notes ORDER BY created_at DESC");
    $notes = $stmt->fetchAll();

} catch (PDOException $e) {
    $error_message = "خطأ في الاتصال: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تجربة نُشر على Railway</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(135deg, #1a2a6c 0%, #b21f1f 50%, #fdbb2d 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }
    </style>
</head>
<body class="p-4 md:p-8 text-white">
    <div class="max-w-lg mx-auto">
        <header class="text-center mb-10">
            <h1 class="text-4xl font-extrabold mb-2">مذكرتي السحابية 🚀</h1>
            <p class="text-white/70">مشروع تجريبي على Railway باستخدام PostgreSQL</p>
        </header>
        
        <?php if (isset($error_message)): ?>
            <div class="bg-red-500/50 border border-red-500 p-4 rounded-lg mb-6 text-center">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="glass p-6 mb-8 transform transition hover:scale-[1.02]">
            <textarea name="note" 
                      class="w-full bg-white/10 border border-white/20 rounded-xl p-4 text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-white/30 resize-none" 
                      rows="3" 
                      placeholder="اكتب ملاحظة جديدة..." 
                      required></textarea>
            <button type="submit" 
                    class="w-full mt-4 bg-white/20 hover:bg-white/40 transition-all duration-300 py-3 rounded-xl font-bold uppercase tracking-wider">
                حفظ في السحابة
            </button>
        </form>

        <div class="space-y-4">
            <?php if (empty($notes)): ?>
                <p class="text-center text-white/50 italic">لا توجد مذكرات بعد.. ابدأ بالكتابة!</p>
            <?php else: ?>
                <?php foreach($notes as $row): ?>
                    <div class="glass p-5 transition-all hover:bg-white/15">
                        <p class="text-lg leading-relaxed mb-2"><?php echo htmlspecialchars($row['content']); ?></p>
                        <div class="flex justify-between items-center text-xs text-white/40">
                            <span>ID: #<?php echo $row['id']; ?></span>
                            <span><?php echo date('Y-m-d H:i', strtotime($row['created_at'])); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <footer class="mt-12 text-center text-white/30 text-sm">
            Architect: Khaled Shdad 2026
        </footer>
    </div>
</body>
</html>
