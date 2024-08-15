<?php

require_once 'config.php'; 

header('Content-Type: application/json');

try {
    $pdo = new PDO("mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']}", $dbConfig['username'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);

    if ($action == 'edit') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $user_id = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_STRING);
        $check_in_time = filter_input(INPUT_POST, 'check_in_time', FILTER_SANITIZE_STRING);
        $check_out_time = filter_input(INPUT_POST, 'check_out_time', FILTER_SANITIZE_STRING);
        $attendance_status = filter_input(INPUT_POST, 'attendance_status', FILTER_SANITIZE_STRING);
        $is_late_in = filter_input(INPUT_POST, 'is_late_in', FILTER_VALIDATE_INT);
        $is_late_out = filter_input(INPUT_POST, 'is_late_out', FILTER_VALIDATE_INT);

        if ($id && $user_id && $attendance_status !== '') {
            try {
                $stmt = $pdo->prepare("UPDATE attendance_records SET user_id = :user_id, check_in_time = :check_in_time, check_out_time = :check_out_time, attendance_status = :attendance_status, is_late_in = :is_late_in, is_late_out = :is_late_out WHERE id = :id");
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':check_in_time', $check_in_time);
                $stmt->bindParam(':check_out_time', $check_out_time);
                $stmt->bindParam(':attendance_status', $attendance_status);
                $stmt->bindParam(':is_late_in', $is_late_in);
                $stmt->bindParam(':is_late_out', $is_late_out);
                $stmt->bindParam(':id', $id);
                $stmt->execute();

                echo json_encode(['status' => 'success', 'message' => 'Record updated successfully']);
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'message' => 'Update failed: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid data provided']);
        }

    } elseif ($action == 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        if ($id) {
            try {
                $stmt = $pdo->prepare("DELETE FROM attendance_records WHERE id = :id");
                $stmt->bindParam(':id', $id);
                $stmt->execute();

                echo json_encode(['status' => 'success', 'message' => 'Record deleted successfully']);
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'message' => 'Deletion failed: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
        }

    } elseif ($action == 'delete_all') {
        try {
            $stmt = $pdo->prepare("DELETE FROM attendance_records");
            $stmt->execute();

            echo json_encode(['status' => 'success', 'message' => 'All records deleted successfully']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Deletion failed: ' . $e->getMessage()]);
        }

    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }

    exit();

} elseif ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
    parse_str(file_get_contents("php://input"), $data);
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);

    if ($id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM attendance_records WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            echo json_encode(['status' => 'success', 'message' => 'Record deleted successfully']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Deletion failed: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
    }

    exit();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}

?>
