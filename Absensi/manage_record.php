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

    error_log("Action received: " . $action);

    if ($action == 'edit') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $user_id = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_STRING);
        $datetime = filter_input(INPUT_POST, 'datetime', FILTER_SANITIZE_STRING);
        $check_type = filter_input(INPUT_POST, 'check_type', FILTER_VALIDATE_INT);
        $attendance_status = filter_input(INPUT_POST, 'attendance_status', FILTER_SANITIZE_STRING);

        if ($id && $user_id && $attendance_status !== '') {
            try {
                if ($check_type === 0) {
                    // Update check-in time
                    $stmt = $pdo->prepare("UPDATE attendance_records SET user_id = :user_id, datetime = :datetime, attendance_status = :attendance_status WHERE id = :id");
                } elseif ($check_type === 1) {
                    // Update check-out time
                    $stmt = $pdo->prepare("UPDATE attendance_records SET datetime = :datetime, attendance_status = :attendance_status WHERE id = :id");
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Invalid check type']);
                    exit();
                }
                
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':datetime', $datetime);
                $stmt->bindParam(':attendance_status', $attendance_status);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                
                echo json_encode(['status' => 'success', 'message' => 'Record updated successfully']);
                
            } catch (PDOException $e) {
                error_log("Update failed: " . $e->getMessage());
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
                error_log("Deletion failed: " . $e->getMessage());
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
            error_log("Deletion failed: " . $e->getMessage());
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
            error_log("Deletion failed: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Deletion failed: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
    }

    exit();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
