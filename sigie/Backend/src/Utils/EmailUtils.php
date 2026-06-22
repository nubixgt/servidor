<?php
namespace App\Utils;

use Exception;

class EmailUtils
{
    /**
     * Enviar un correo electrónico (con fallback de logging local)
     *
     * @param string $to
     * @param string $subject
     * @param string $body
     * @return bool
     */
    public static function send(string $to, string $subject, string $body): bool
    {
        $logDir = __DIR__ . '/../../uploads';
        if (!file_exists($logDir)) {
            @mkdir($logDir, 0777, true);
        }
        $logFile = $logDir . '/email_logs.log';
        
        $date = date('Y-m-d H:i:s');
        $logEntry = "=========================================\n";
        $logEntry .= "FECHA: {$date}\n";
        $logEntry .= "PARA: {$to}\n";
        $logEntry .= "ASUNTO: {$subject}\n";
        $logEntry .= "CONTENIDO:\n{$body}\n";
        $logEntry .= "=========================================\n\n";
        
        // Escribir en el log siempre para depuración
        @file_put_contents($logFile, $logEntry, FILE_APPEND);

        // Intentar enviar correo real mediante la función mail() de PHP
        try {
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: SIGIE Notificaciones <no-reply@sigie.gob.gt>" . "\r\n";
            
            // mail() puede arrojar advertencias si no está configurado, las silenciamos
            return @mail($to, $subject, $body, $headers);
        } catch (Exception $e) {
            // Silencioso en caso de error de red o servidor de correo
            return false;
        }
    }
}
