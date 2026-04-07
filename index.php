<?php
/**
 * Project: Mini Cloud Notes
 * Architect: Khaled Shdad
 * Platform: Railway.app
 * Database: PostgreSQL
 */

// 1. إعدادات الاتصال عبر متغيرات البيئة (Environment Variables)
$host = getenv('PGHOST');
$user = getenv('PGUSER');
$pass = getenv('PGPASSWORD');
$db   = getenv('PGDATABASE');
$port = getenv('PGPORT') ?: '5432';

$notes = [];
$error = null;

try {
    // 2. الاتصال باستخدام محرك PDO لـ PostgreSQL
    $dsn = "pgsql:host=$host;port=$port;dbname=$db";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
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
        
        // إعادة التوجيه لمنع تكرار الإرسال عند تحديث الصفحة
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // 5. جلب الملاحظات من الأحدث للأقدم
    $stmt = $pdo->query("SELECT * FROM notes ORDER BY created_at DESC");
    $notes = $stmt->fetchAll();

} catch (PDOException $e) {
    // تخزين الخطأ لعرضه بشكل جميل في الواجهة
    $error = "عذراً، فشل الاتصال بالسحابة: " . $e->getMessage();
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
            font-family: 'Tajawal', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            min-height: 100vh;
        }

        /* ستايل الزجاج (Glassmorphism) */
        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
        }

        .input-glass {
            background: rgba(255, 255, 255, 0.07);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .input-glass:focus {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.3);
            outline: none;
        }

        /* أنيميشن بسيط لظهور الملاحظات */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .note-item {
            animation: fadeIn 0.5s ease forwards;
        }
    </style>
</head>
<body class="p-6 md:p-12 text-slate-100">

    <div class="max-w-2xl mx-auto">
        <header class="text-center mb-12">
            <h1 class="text-4xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-emerald-400 mb-2">
                مذكرتي السحابية 🚀
            </h1>
            <p class="text-slate-400">نظام تخزين سحابي بسيط يعتمد على PostgreSQL</p>
        </header>

        <?php if ($error): ?>
            <div class="bg-red-500/20 border border-red-500/50 p-4 rounded-xl mb-8 text-center text-red-200">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <section class="glass-card p-6 mb-12">
            <form method="POST" class="space-y-4">
                <textarea name="note" rows="3" 
                          class="w-full input-glass rounded-xl p-4 text-white placeholder-slate-500"
                          placeholder="ماذا يدور في ذهنك يا خالد؟" required></textarea>
                <button type="submit" 
                        class="w-full bg-blue-600 hover:bg-blue-500 transition-colors py-3 rounded-xl font-bold text-lg shadow-lg shadow-blue-900/20">
                    حفظ الملاحظة
                </button>
            </form>
        </section>

        <section class="space-y-6">
            <h2 class="text-xl font-bold border-r-4 border-emerald-400 pr-3 mb-6">الملاحظات الأخيرة</h2>
            
            <?php if (empty($notes)): ?>
                <div class="text-center py-10 text-slate-500 italic">
                    السحابة فارغة حالياً.. ابدأ بكتابة أول فكرة!
                </div>
            <?php else: ?>
                <?php foreach($notes as $row): ?>
                    <div class="glass-card p-6 note-item hover:border-emerald-500/30 transition-colors">
                        <p class="text-lg leading-relaxed text-slate-200 mb-4">
                            <?php echo htmlspecialchars($row['content']); ?>
                        </p>
                        <div class="flex justify-between items-center text-xs text-slate-500 font-mono">
                            <span class="bg-slate-800 px-2 py-1 rounded">ID: #<?php echo $row['id']; ?></span>
                            <span><?php echo date('Y/m/d - h:i A', strtotime($row['created_at'])); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <footer class="mt-20 text-center text-slate-600 text-sm">
            <p>© 2026 Developed by <span class="text-slate-400 font-bold">Khaled Shdad</span></p>
            <p class="mt-1">Built with PHP & PostgreSQL on Railway</p>
        </footer>
    </div>

</body>
</html>
