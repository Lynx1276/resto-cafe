 <?php
    require_once __DIR__ . '/../includes/functions.php';
    require_admin();

    function get_all_customers_not_staff()
    {
        $conn = db_connect();
        $stmt = $conn->prepare("
            SELECT c.*
            FROM customers c
            JOIN user_roles ur ON c.user_id = ur.user_id
            JOIN roles r ON ur.role_id = r.role_id
            WHERE r.role_name = 'customer'
            AND c.user_id NOT IN (
                SELECT user_id FROM staff
            )
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $customers = [];
        while ($row = $result->fetch_assoc()) {
            $customers[] = $row;
        }
        return $customers;
    }

    function handle_convert_to_staff()
    {
        $conn = db_connect();

        try {
            $conn->begin_transaction();

            $user_id = $_POST['user_id'];
            $position = $_POST['position'];
            $employment_status = $_POST['employment_status'];

            // Insert into staff table
            $stmt = $conn->prepare("INSERT INTO staff (user_id, position, employment_status, hire_date) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iss", $user_id, $position, $employment_status);
            $stmt->execute();

            // Add 'staff' role (trigger should handle this, but let's ensure)
            $staff_role_id = $conn->query("SELECT role_id FROM roles WHERE role_name = 'staff'")->fetch_assoc()['role_id'];
            $stmt = $conn->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $user_id, $staff_role_id);
            $stmt->execute();

            // Add additional roles if selected
            $roles = $_POST['roles'] ?? [];
            foreach ($roles as $role_id) {
                $stmt = $conn->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $user_id, $role_id);
                $stmt->execute();
            }

            $conn->commit();
            set_flash_message('Customer successfully converted to staff', 'success');
        } catch (Exception $e) {
            $conn->rollback();
            set_flash_message('Error converting customer to staff: ' . $e->getMessage(), 'error');
        }

        header('Location: staff_management.php');
        exit();
    }

    function handle_update_staff()
    {
        $conn = db_connect();

        try {
            $conn->begin_transaction();

            $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, username = ?, phone = ? WHERE user_id = ?");
            $stmt->bind_param(
                "sssssi",
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['email'],
                $_POST['username'],
                $_POST['phone'],
                $_POST['user_id']
            );
            $stmt->execute();

            $stmt = $conn->prepare("UPDATE staff SET position = ?, employment_status = ? WHERE staff_id = ?");
            $stmt->bind_param(
                "ssi",
                $_POST['position'],
                $_POST['employment_status'],
                $_POST['staff_id']
            );
            $stmt->execute();

            // Keep 'staff' and 'customer' roles, update others
            $stmt = $conn->prepare("DELETE FROM user_roles WHERE user_id = ? AND role_id NOT IN (SELECT role_id FROM roles WHERE role_name IN ('staff', 'customer'))");
            $stmt->bind_param("i", $_POST['user_id']);
            $stmt->execute();

            $roles = $_POST['roles'] ?? [];
            foreach ($roles as $role_id) {
                $stmt = $conn->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $_POST['user_id'], $role_id);
                $stmt->execute();
            }

            $conn->commit();
            set_flash_message('Staff member updated successfully', 'success');
        } catch (Exception $e) {
            $conn->rollback();
            set_flash_message('Error updating staff: ' . $e->getMessage(), 'error');
        }

        header('Location: staff_management.php');
        exit();
    }

    function handle_delete_staff()
    {
        $conn = db_connect();

        try {
            $conn->begin_transaction();

            $stmt = $conn->prepare("SELECT user_id FROM staff WHERE staff_id = ?");
            $stmt->bind_param("i", $_POST['staff_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_id = $result->fetch_assoc()['user_id'];

            $stmt = $conn->prepare("DELETE FROM staff_schedules WHERE staff_id = ?");
            $stmt->bind_param("i", $_POST['staff_id']);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM user_roles WHERE user_id = ? AND role_id != (SELECT role_id FROM roles WHERE role_name = 'customer')");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM staff WHERE staff_id = ?");
            $stmt->bind_param("i", $_POST['staff_id']);
            $stmt->execute();

            $conn->commit();
            set_flash_message('Staff member deleted successfully', 'success');
        } catch (Exception $e) {
            $conn->rollback();
            set_flash_message('Error deleting staff: ' . $e->getMessage(), 'error');
        }

        header('Location: staff_management.php');
        exit();
    }

    function handle_update_schedule()
    {
        $conn = db_connect();

        try {
            $conn->begin_transaction();

            $stmt = $conn->prepare("DELETE FROM staff_schedules WHERE staff_id = ?");
            $stmt->bind_param("i", $_POST['staff_id']);
            $stmt->execute();

            $schedule = $_POST['schedule'] ?? [];
            foreach ($schedule as $day => $times) {
                if (!empty($times['start_time']) && !empty($times['end_time'])) {
                    $stmt = $conn->prepare("INSERT INTO staff_schedules (staff_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param(
                        "isss",
                        $_POST['staff_id'],
                        $times['day_of_week'],
                        $times['start_time'],
                        $times['end_time']
                    );
                    $stmt->execute();
                }
            }

            $conn->commit();
            set_flash_message('Schedule updated successfully', 'success');
        } catch (Exception $e) {
            $conn->rollback();
            set_flash_message('Error updating schedule: ' . $e->getMessage(), 'error');
        }

        header('Location: staff_management.php');
        exit();
    }
    ?>