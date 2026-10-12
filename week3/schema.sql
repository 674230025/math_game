-- SQLite schema สำหรับ Math Game (api.php จะสร้างตารางให้อัตโนมัติ)
CREATE TABLE IF NOT EXISTS levels (
  id INTEGER PRIMARY KEY,
  category TEXT NOT NULL,
  text TEXT NOT NULL,
  answers TEXT NOT NULL, -- JSON array เช่น ["15","5"]
  choices TEXT NOT NULL  -- JSON nested array ของตัวเลือก
);

CREATE TABLE IF NOT EXISTS scores (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  student_name TEXT NOT NULL,
  score INTEGER NOT NULL DEFAULT 0,
  total_levels INTEGER NOT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
