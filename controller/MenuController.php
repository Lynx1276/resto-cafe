<?php
require_once __DIR__ . '/../includes/functions.php';

function handle_image_upload($file, $prefix = 'menu')
{
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        set_flash_message('Error uploading file', 'error');
        return null;
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        set_flash_message('Invalid file type. Only JPEG, PNG, and GIF are allowed.', 'error');
        return null;
    }

    if ($file['size'] > 10 * 1024 * 1024) {
        set_flash_message('File size exceeds 10MB limit.', 'error');
        return null;
    }

    $upload_dir = __DIR__ . '/../assets/Uploads/menu/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $prefix . uniqid() . '.' . $extension;
    $destination = $upload_dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return '/../assets/Uploads/menu/' . $filename;
    } else {
        set_flash_message('Failed to move uploaded file.', 'error');
        return null;
    }
}

function handle_add_category()
{
    $conn = db_connect();
    $name = sanitize_input($_POST['name']);
    $description = sanitize_input($_POST['description'] ?? '');
    $image_url = handle_image_upload($_FILES['menu_image'], 'category');

    $sql = "INSERT INTO categories (name, description, image_url) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $name, $description, $image_url);

    if ($stmt->execute()) {
        set_flash_message('Category added successfully', 'success');
    } else {
        set_flash_message('Failed to add category', 'error');
    }

    $stmt->close();
    header('Location: menu.php');
    exit();
}

function handle_update_category()
{
    $conn = db_connect();
    $category_id = sanitize_input($_POST['category_id']);
    $name = sanitize_input($_POST['name']);
    $description = sanitize_input($_POST['description'] ?? '');
    $image_url = handle_image_upload($_FILES['menu_image'], 'category');

    if ($image_url === null) {
        $sql = "SELECT image_url FROM categories WHERE category_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $category_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $image_url = $row['image_url'];
        $stmt->close();
    }

    $sql = "UPDATE categories SET name = ?, description = ?, image_url = ? WHERE category_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssi', $name, $description, $image_url, $category_id);

    if ($stmt->execute()) {
        set_flash_message('Category updated successfully', 'success');
    } else {
        set_flash_message('Failed to update category', 'error');
    }

    $stmt->close();
    header('Location: menu.php');
    exit();
}

function handle_delete_category()
{
    $conn = db_connect();
    $category_id = sanitize_input($_POST['category_id']);

    $sql = "UPDATE items SET category_id = NULL WHERE category_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $category_id);
    $stmt->execute();
    $stmt->close();

    $sql = "DELETE FROM categories WHERE category_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $category_id);

    if ($stmt->execute()) {
        set_flash_message('Category deleted successfully', 'success');
    } else {
        set_flash_message('Failed to delete category', 'error');
    }

    $stmt->close();
    header('Location: menu.php');
    exit();
}

function handle_add_item()
{
    $conn = db_connect();
    $name = sanitize_input($_POST['name']);
    $description = sanitize_input($_POST['description'] ?? '');
    $category_id = !empty($_POST['category_id']) ? sanitize_input($_POST['category_id']) : null;
    $price = floatval($_POST['price']);
    $cost = floatval($_POST['cost']);
    $calories = !empty($_POST['calories']) ? intval($_POST['calories']) : null;
    $allergens = sanitize_input($_POST['allergens'] ?? '');
    $prep_time = !empty($_POST['prep_time']) ? intval($_POST['prep_time']) : null;
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    $image_url = handle_image_upload($_FILES['menu_image'], 'item');

    $sql = "INSERT INTO items (name, description, category_id, price, cost, calories, allergens, prep_time, is_available, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssidiiisds', $name, $description, $category_id, $price, $cost, $calories, $allergens, $prep_time, $is_available, $image_url);

    if ($stmt->execute()) {
        set_flash_message('Menu item added successfully', 'success');
    } else {
        set_flash_message('Failed to add menu item', 'error');
    }

    $stmt->close();
    header('Location: menu.php');
    exit();
}

function handle_update_item()
{
    $conn = db_connect();
    $item_id = sanitize_input($_POST['item_id']);
    $name = sanitize_input($_POST['name']);
    $description = sanitize_input($_POST['description'] ?? '');
    $category_id = !empty($_POST['category_id']) ? sanitize_input($_POST['category_id']) : null;
    $price = floatval($_POST['price']);
    $cost = floatval($_POST['cost']);
    $calories = !empty($_POST['calories']) ? intval($_POST['calories']) : null;
    $allergens = sanitize_input($_POST['allergens'] ?? '');
    $prep_time = !empty($_POST['prep_time']) ? intval($_POST['prep_time']) : null;
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    $image_url = handle_image_upload($_FILES['menu_image'], 'item');

    if ($image_url === null) {
        $sql = "SELECT image_url FROM items WHERE item_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $image_url = $row['image_url'];
        $stmt->close();
    }

    $sql = "UPDATE items SET name = ?, description = ?, category_id = ?, price = ?, cost = ?, calories = ?, allergens = ?, prep_time = ?, is_available = ?, image_url = ? WHERE item_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssidiiisisi', $name, $description, $category_id, $price, $cost, $calories, $allergens, $prep_time, $is_available, $image_url, $item_id);

    if ($stmt->execute()) {
        set_flash_message('Menu item updated successfully', 'success');
    } else {
        set_flash_message('Failed to update menu item', 'error');
    }

    $stmt->close();
    header('Location: menu.php');
    exit();
}

function handle_delete_item()
{
    $conn = db_connect();
    $item_id = sanitize_input($_POST['item_id']);

    $sql = "DELETE FROM items WHERE item_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $item_id);

    if ($stmt->execute()) {
        set_flash_message('Menu item deleted successfully', 'success');
    } else {
        set_flash_message('Failed to delete menu item', 'error');
    }

    $stmt->close();
    header('Location: menu.php');
    exit();
}

function handle_toggle_availability()
{
    $conn = db_connect();
    $item_id = sanitize_input($_POST['item_id']);
    $is_available = isset($_POST['is_available']) ? 1 : 0;

    $sql = "UPDATE items SET is_available = ? WHERE item_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $is_available, $item_id);

    if ($stmt->execute()) {
        set_flash_message('Availability updated successfully', 'success');
    } else {
        set_flash_message('Failed to update availability', 'error');
    }

    $stmt->close();
    header('Location: menu.php');
    exit();
}

function get_categories($with_item_count = false)
{
    $conn = db_connect();
    $sql = $with_item_count
        ? "SELECT c.*, COUNT(i.item_id) as item_count FROM categories c LEFT JOIN items i ON c.category_id = i.category_id GROUP BY c.category_id"
        : "SELECT * FROM categories";
    $result = $conn->query($sql);
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    return $categories;
}

function get_menu_items($category_id = null)
{
    $conn = db_connect();
    $sql = $category_id !== null
        ? "SELECT * FROM items WHERE category_id = ?"
        : "SELECT * FROM items WHERE category_id IS NULL";
    $stmt = $conn->prepare($sql);
    if ($category_id !== null) {
        $stmt->bind_param('i', $category_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    $stmt->close();
    return $items;
}
