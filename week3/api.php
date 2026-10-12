<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(array $data, int $code = 200): never {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    // เก็บฐานข้อมูลไว้ในโฟลเดอร์เดียวกับไฟล์ API
    $pdo = new PDO('sqlite:' . __DIR__ . '/math_game.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec("CREATE TABLE IF NOT EXISTS levels (
        id INTEGER PRIMARY KEY, category TEXT NOT NULL, text TEXT NOT NULL,
        answers TEXT NOT NULL, choices TEXT NOT NULL
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS scores (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        student_name TEXT NOT NULL,
        score INTEGER NOT NULL DEFAULT 0,
        total_levels INTEGER NOT NULL,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");

    // นำเข้าโจทย์จากไฟล์ JSON เฉพาะเมื่อฐานข้อมูลยังไม่มีข้อมูล
    $count = (int)$pdo->query('SELECT COUNT(*) FROM levels')->fetchColumn();
    if ($count === 0) {
        $jsonPath = __DIR__ . '/questions.json';
        if (!is_file($jsonPath)) throw new RuntimeException('ไม่พบไฟล์ questions.json');
        $questions = json_decode((string)file_get_contents($jsonPath), true, 512, JSON_THROW_ON_ERROR);
        $stmt = $pdo->prepare('INSERT INTO levels (id, category, text, answers, choices) VALUES (:id,:category,:text,:answers,:choices)');
        foreach ($questions as $q) {
            $stmt->execute([
                ':id' => (int)$q['id'], ':category' => $q['category'], ':text' => $q['text'],
                ':answers' => json_encode($q['answers'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                ':choices' => json_encode($q['choices'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
            ]);
        }
    }

    $action = $_GET['action'] ?? 'levels';
    if ($action === 'levels' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $rows = $pdo->query('SELECT id, category, text, answers, choices FROM levels ORDER BY id ASC')->fetchAll();
        foreach ($rows as &$row) {
            $row['id'] = (int)$row['id'];
            $row['answers'] = json_decode($row['answers'], true, 512, JSON_THROW_ON_ERROR);
            $row['choices'] = json_decode($row['choices'], true, 512, JSON_THROW_ON_ERROR);
        }
        unset($row);
        respond(['status' => 'success', 'count' => count($rows), 'data' => $rows]);
    }

    if ($action === 'save-score' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode((string)file_get_contents('php://input'), true);
        if (!is_array($input)) respond(['status'=>'error','message'=>'ข้อมูลที่ส่งมาไม่ถูกต้อง'], 400);
        $name = trim((string)($input['student_name'] ?? 'ผู้เล่น'));
        $score = filter_var($input['score'] ?? null, FILTER_VALIDATE_INT);
        $total = filter_var($input['total_levels'] ?? null, FILTER_VALIDATE_INT);
        if ($name === '') $name = 'ผู้เล่น';
        if ($score === false || $score < 0 || $total === false || $total < 1) respond(['status'=>'error','message'=>'คะแนนหรือจำนวนด่านไม่ถูกต้อง'], 422);
        $stmt = $pdo->prepare('INSERT INTO scores (student_name, score, total_levels) VALUES (:name,:score,:total)');
        $stmt->execute([':name'=>mb_substr($name, 0, 120), ':score'=>$score, ':total'=>$total]);
        respond(['status'=>'success','message'=>'บันทึกคะแนนแล้ว','id'=>(int)$pdo->lastInsertId()], 201);
    }

    if ($action === 'scores' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $rows = $pdo->query('SELECT id, student_name, score, total_levels, created_at FROM scores ORDER BY id DESC LIMIT 100')->fetchAll();
        respond(['status'=>'success','data'=>$rows]);
    }
    respond(['status'=>'error','message'=>'ไม่พบ action ที่ร้องขอ'], 404);
} catch (Throwable $e) {
    error_log('Math Game API: ' . $e->getMessage());
    respond(['status'=>'error','message'=>'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล กรุณาตรวจสอบ PDO_SQLITE และสิทธิ์เขียนไฟล์'], 500);
}
