<?php
include 'db.php';

// حماية لوحة التحكم
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php"); 
    exit();
}

$success = ''; 
$error = '';

// إضافة منتج (Admin فقط)
if (isset($_POST['add_product']) && $_SESSION['role'] == 'admin') {
    $name = sanitize($_POST['name']);
    $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
    $quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_INT);
    
    if ($name && $price !== false && $quantity !== false) {
        $target_file = "";
        if (!empty($_FILES["image"]["name"])) {
            $target_dir = "uploads/";
            if (!file_exists($target_dir)) { 
                mkdir($target_dir, 0755, true); 
            }
            $image_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
            $image_name = time() . "_" . bin2hex(random_bytes(4)) . "." . $image_extension;
            $target_file = $target_dir . $image_name;
            move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
        }

        $stmt = $conn->prepare("INSERT INTO products (name, price, quantity, image) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sdis", $name, $price, $quantity, $target_file);
        if ($stmt->execute()) {
            $success = "تم إضافة المنتج بنجاح!";
        } else {
            $error = "حدث خطأ أثناء حفظ البيانات بقاعدة البيانات.";
        }
        $stmt->close();
    } else {
        $error = "يرجى التحقق من صحة المدخلات.";
    }
}

// حذف منتج (Admin فقط)
if (isset($_GET['delete']) && $_SESSION['role'] == 'admin') {
    $id = filter_var($_GET['delete'], FILTER_VALIDATE_INT);
    if ($id) {
        $stmt_img = $conn->prepare("SELECT image FROM products WHERE id = ?");
        $stmt_img->bind_param("i", $id);
        $stmt_img->execute();
        $res = $stmt_img->get_result();
        if ($res->num_rows > 0) {
            $prod = $res->fetch_assoc();
            if(!empty($prod['image']) && file_exists($prod['image'])) {
                unlink($prod['image']); 
            }
        }
        $stmt_img->close();

        $stmt_del = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt_del->bind_param("i", $id);
        if ($stmt_del->execute()) {
            $success = "تم حذف المنتج بنجاح.";
        }
        $stmt_del->close();
    }
}

// قراءة المنتجات من قاعدة البيانات
$products = $conn->query("SELECT * FROM products ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم بالمخزون</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
</head>
<body class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <h2>مرحباً بك يا <?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?> <span class="fs-5 text-muted">(الصلاحية: <?php echo htmlspecialchars($_SESSION['role'], ENT_QUOTES, 'UTF-8'); ?>)</span></h2>
        <a href="logout.php" class="btn btn-danger">تسجيل الخروج</a>
    </div>

    <?php if(!empty($success)) echo "<div class='alert alert-success text-center'>$success</div>"; ?>
    <?php if(!empty($error)) echo "<div class='alert alert-danger text-center'>$error</div>"; ?>

    <?php if ($_SESSION['role'] == 'admin'): ?>
    <div class="card p-4 mb-4 shadow-sm">
        <h4 class="mb-3 text-success">إضافة منتج جديد للمخزن</h4>
        <form method="POST" enctype="multipart/form-data" class="row g-3">
            <div class="col-md-3">
                <input type="text" name="name" class="form-control" placeholder="اسم المنتج" required>
            </div>
            <div class="col-md-2">
                <input type="number" step="0.01" name="price" class="form-control" placeholder="السعر" min="0" required>
            </div>
            <div class="col-md-2">
                <input type="number" name="quantity" class="form-control" placeholder="الكمية" min="0" required>
            </div>
            <div class="col-md-3">
                <input type="file" name="image" class="form-control" accept="image/*">
            </div>
            <div class="col-md-2">
                <button type="submit" name="add_product" class="btn btn-success w-100">إضافة</button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <h4 class="mb-3 text-secondary">منتجات المخزن الحالية</h4>
    <div class="table-responsive shadow-sm rounded">
        <table class="table table-bordered table-striped align-middle m-0">
            <thead class="table-dark">
                <tr>
                    <th style="width: 100px;">صورة المنتج</th>
                    <th>اسم المنتج</th>
                    <th>السعر</th>
                    <th>الكمية</th>
                    <?php if ($_SESSION['role'] == 'admin'): ?><th style="width: 100px;">الإجراءات</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($products && $products->num_rows > 0): ?>
                    <?php while($row = $products->fetch_assoc()): ?>
                    <tr>
                        <td class="text-center">
                            <?php if(!empty($row['image']) && file_exists($row['image'])): ?>
                                <img src="<?php echo htmlspecialchars($row['image'], ENT_QUOTES, 'UTF-8'); ?>" width="60" height="60" class="rounded object-fit-cover">
                            <?php else: ?>
                                <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; font-size: 10px;">بلا صورة</div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="fw-bold">$<?php echo number_format($row['price'], 2); ?></td>
                        <td><span class="badge bg-primary"><?php echo htmlspecialchars($row['quantity'], ENT_QUOTES, 'UTF-8'); ?> قطع</span></td>
                        <?php if ($_SESSION['role'] == 'admin'): ?>
                        <td class="text-center">
                            <a href="dashboard.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من الحذف؟')">حذف</a>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?php echo $_SESSION['role'] == 'admin' ? 5 : 4; ?>" class="text-center py-4 text-muted">لا توجد منتجات مضافة في المخزن حالياً.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>