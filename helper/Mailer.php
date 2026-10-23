<?php
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
class Mailer
{
private function cargarConfig(){
    $ruta = __DIR__ . '/../config/mail.ini';

    if (!file_exists($ruta)) {
        Log::error("Falta config/mail.ini");
        return null;
    }

    return parse_ini_file($ruta);
}

    public function enviar($destinatario, $nombre, $asunto, $cuerpoHtml){
        $config = $this->cargarConfig();
        if ($config === null) {
            return false;
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $config["host"];
            $mail->SMTPAuth = true;
            $mail->Username = $config["usuario"];
            $mail->Password = $config["clave"];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = (int)$config["port"];
            $mail->CharSet = 'UTF-8';

            $mail->setFrom($config["remitente"], $config["nombre_remitente"]);
            $mail->addAddress($destinatario, $nombre);

            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body    = $cuerpoHtml;

            $mail->send();
            return true;

        } catch (Exception $e) {
            Log::error("Error al enviar mail: " . $mail->ErrorInfo);
            return false;
        }
    }

}