<?php
// กำหนดให้ส่งออกข้อมูลเป็น UTF-8 และ JSON ในกรณีที่ทำเป็น API
header('Content-Type: application/json; charset=utf-8');

class MathPuzzleDB {
    private $pdo;
    private $dbFile = 'math_game.sqlite';

    // [State/Constructor]: เริ่มต้นการเชื่อมต่อฐานข้อมูล
    public function __construct() {
        try {
            $this->pdo = new PDO("sqlite:" . $this->dbFile);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // สร้างตารางทันทีหากยังไม่มีในระบบ
            $this->initTable();
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => "Connection failed: " . $e->getMessage()]);
            exit();
        }
    }

    // [Logic]: ฟังก์ชันสร้างตารางภายใน SQLite
    private function initTable() {
        $sql = "CREATE TABLE IF NOT EXISTS levels (
            id INTEGER PRIMARY KEY,
            category TEXT NOT NULL,
            text TEXT NOT NULL,
            answers TEXT NOT NULL,
            choices TEXT NOT NULL
        )";
        $this->pdo->exec($sql);
    }

    // [Logic]: ฟังก์ชันสำหรับเพิ่มหรืออัปเดตข้อมูลด่าน (Upsert)
    public function saveLevel($id, $category, $text, array $answers, array $choices) {
        $sql = "INSERT OR REPLACE INTO levels (id, category, text, answers, choices) 
                VALUES (:id, :category, :text, :answers, :choices)";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':category' => $category,
            ':text' => $text,
            // แปลง Array ของ PHP เป็น JSON String ก่อนบันทึกลง SQLite
            ':answers' => json_encode($answers, JSON_UNESCAPED_UNICODE),
            ':choices' => json_encode($choices, JSON_UNESCAPED_UNICODE)
        ]);
    }

    // [Logic]: ฟังก์ชันดึงข้อมูลทุกด่าน และแปลง JSON สตริงกลับเป็น Array ของ PHP
    public function getAllLevels() {
        $sql = "SELECT * FROM levels ORDER BY id ASC";
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();

        // แปลงฟีลด์ที่เก็บเป็น JSON String ให้กลับมาเป็นโครงสร้าง Array ปกติ
        foreach ($rows as &$row) {
            $row['id'] = (int)$row['id'];
            $row['answers'] = json_decode($row['answers'], true);
            $row['choices'] = json_decode($row['choices'], true);
        }
        
        return $rows;
    }
}
?>