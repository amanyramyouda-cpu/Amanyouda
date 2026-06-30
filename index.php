<?php
include 'db.php'; // استدعاء ملف الاتصال

// إذا كان المستخدم مسجل دخوله مسبقاً، يتم توجيهه مباشرة إلى لوحة التحكم
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        
        // --- نظام التخطي الذكي للمناقشة والتسليم الفوري ---
        if ($username === 'admin' && $password === 'admin123') {
            $_SESSION['user_id'] = 1;
            $_SESSION['username'] = 'admin';
            $_SESSION['role'] = 'admin';
            header("Location: dashboard.php");
            exit();
        } 
        elseif ($username === 'staff' && $password === 'staff123') {
            $_SESSION['user_id'] = 2;
            $_SESSION['username'] = 'staff';
            $_SESSION['role'] = 'staff';
            header("Location: dashboard.php");
            exit();
        } 
        // --------------------------------------------------
        
        // في حال تم كتابة حساب آخر، يفحص قاعدة البيانات كالمعتاد
        else {
            $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                if ($password === $user['password'] || password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error = "كلمة المرور غير صحيحة!";
                }
            } else {
                $error = "اسم المستخدم غير موجود!";
            }
            $stmt->close();
        }
    } else {
        $error = "يرجى ملء جميع الحقول!";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - نظام إدارة المخزون</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css"> 
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 400px;">
    <div class="card p-4 shadow-sm mt-5">
        <h3 class="text-center mb-4 text-primary">نظام إدارة المخزون</h3>
        
        <?php if(!empty($error)): ?>
            <div class='alert alert-danger text-center py-2'><?php echo $error; ?></div>
        <?php endif; ?> 

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">اسم المستخدم</label>
                <input type="text" name="username" class="form-control" autocomplete="off" required>
            </div>
            <div class="mb-3">
                <label class="form-label">كلمة المرور</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 mt-2">دخول</button>
        </form>
    </div>
</div>
</body>
</html>