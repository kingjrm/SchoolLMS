<?php
/**
 * Code Generator for Course Join Codes
 * Generates unique, alphanumeric codes for course enrollment
 */
class CodeGenerator {
    /**
     * Generate a random course join code
     * Format: 6 alphanumeric characters (e.g., ABC123)
     * 
     * @param int $length Code length (default 6)
     * @return string Random alphanumeric code
     */
    public static function generateJoinCode($length = 6) {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $code;
    }

    /**
     * Generate unique code that doesn't exist in database
     * 
     * @param PDO $pdo Database connection
     * @param int $length Code length
     * @return string Unique join code
     */
    public static function generateUniqueJoinCode($pdo, $length = 6) {
        do {
            $code = self::generateJoinCode($length);
            
            $stmt = $pdo->prepare("SELECT id FROM courses WHERE join_code = ?");
            $stmt->execute([$code]);
        } while ($stmt->rowCount() > 0);
        
        return $code;
    }
}
?>
