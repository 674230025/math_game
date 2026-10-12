CREATE TABLE IF NOT EXISTS levels (
    id INTEGER PRIMARY KEY,           -- ลำดับด่าน (1-20)
    category TEXT NOT NULL,           -- หมวดหมู่โจทย์
    text TEXT NOT NULL,               -- เนื้อหาโจทย์ปริศนา (มีตัวคั่น {0}, {1})
    answers TEXT NOT NULL,            -- คำตอบที่ถูกต้อง (จัดเก็บเป็น JSON Array)
    choices TEXT NOT NULL             -- ตัวเลือกทั้งหมด (จัดเก็บเป็น JSON Nested Array)
);