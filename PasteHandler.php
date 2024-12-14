<?php
class PasteHandler {
    private $db;

    public function __construct() {
        $storage_dir = dirname(DB_PATH);
        if (!file_exists($storage_dir)) {
            mkdir($storage_dir, 0777, true);
        }

        $this->db = new SQLite3(DB_PATH);
        $this->initDatabase();
    }

    private function initDatabase() {
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS notes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT UNIQUE NOT NULL,
                content TEXT NOT NULL,
                created_at INTEGER NOT NULL,
                expires_at INTEGER NOT NULL,
                max_views INTEGER NOT NULL DEFAULT 0,
                current_views INTEGER NOT NULL DEFAULT 0,
                is_encrypted INTEGER NOT NULL DEFAULT 0,
                is_markdown INTEGER NOT NULL DEFAULT 0,
                file_extension TEXT
            )
        ');
    }

    public function create($content, $options = []) {
        $uuid = $this->generateUuid();
        
        if (strlen($content) > MAX_PASTE_SIZE) {
            $content = substr($content, 0, MAX_PASTE_SIZE);
            $status = 206; // Partial
        } else {
            $status = 201; // Created
        }

        $stmt = $this->db->prepare('
            INSERT INTO notes (
                uuid, content, created_at, expires_at, 
                max_views, is_encrypted, is_markdown, file_extension
            ) VALUES (
                :uuid, :content, :created_at, :expires_at, 
                :max_views, :is_encrypted, :is_markdown, :file_extension
            )
        ');

        $now = time();
        $expires_at = isset($options['expires_at']) ? $options['expires_at'] : $now + DEFAULT_EXPIRY;
        
        $stmt->bindValue(':uuid', $uuid);
        $stmt->bindValue(':content', $content);
        $stmt->bindValue(':created_at', $now);
        $stmt->bindValue(':expires_at', $expires_at);
        $stmt->bindValue(':max_views', $options['max_views'] ?? 0);
        $stmt->bindValue(':is_encrypted', $options['is_encrypted'] ?? 0);
        $stmt->bindValue(':is_markdown', $options['is_markdown'] ?? 0);
        $stmt->bindValue(':file_extension', $options['file_extension'] ?? '');
        
        $stmt->execute();

        return ['uuid' => $uuid, 'status' => $status];
    }

    public function read($uuid) {
        // Remove file extension if present
        $uuid = pathinfo($uuid, PATHINFO_FILENAME);
        
        $stmt = $this->db->prepare('
            SELECT * FROM notes 
            WHERE uuid = :uuid 
            AND (expires_at > :now)
            AND (max_views = 0 OR current_views < max_views)
        ');
        
        $stmt->bindValue(':uuid', $uuid);
        $stmt->bindValue(':now', time());
        
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($row) {
            if ($row['max_views'] > 0) {
                $this->incrementViews($uuid);
            }
            return $row;
        }
        
        return null;
    }

    private function incrementViews($uuid) {
        $stmt = $this->db->prepare('
            UPDATE notes 
            SET current_views = current_views + 1 
            WHERE uuid = :uuid
        ');
        $stmt->bindValue(':uuid', $uuid);
        $stmt->execute();
    }

    public function update($uuid, $content, $options = []) {
        if (strlen($content) > MAX_PASTE_SIZE) {
            $content = substr($content, 0, MAX_PASTE_SIZE);
        }

        $stmt = $this->db->prepare('
            UPDATE notes 
            SET content = :content, 
                file_extension = :file_extension 
            WHERE uuid = :uuid
        ');
        
        $stmt->bindValue(':content', $content);
        $stmt->bindValue(':file_extension', $options['file_extension'] ?? '');
        $stmt->bindValue(':uuid', $uuid);
        $stmt->execute();
        
        return $this->db->changes() > 0;
    }

    public function delete($id) {
        $stmt = $this->db->prepare('DELETE FROM pastes WHERE id = :id');
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        return $this->db->changes() > 0;
    }

    private function generateUuid() {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $uuid = '';
        for ($i = 0; $i < 6; $i++) {
            $uuid .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $uuid;
    }
}
