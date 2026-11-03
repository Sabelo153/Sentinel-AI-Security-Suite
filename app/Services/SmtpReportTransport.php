<?php
declare(strict_types=1);
namespace Sentinel\Services;
use PHPMailer\PHPMailer\PHPMailer;
use Sentinel\Core\Config;
final class SmtpReportTransport implements ReportMailTransport
{
    public function __construct(private readonly Config $config) {}
    public function configured(): bool {return (string)$this->config->get('report_mail.host','')!==''&&filter_var($this->config->get('report_mail.from_address',''),FILTER_VALIDATE_EMAIL)!==false;}
    public function send(string $to,string $subject,string $body,string $messageKey): void {
        if(!$this->configured()||!filter_var($to,FILTER_VALIDATE_EMAIL))throw new \RuntimeException('Mail configuration or recipient is invalid.');
        $mail=new PHPMailer(true);$mail->isSMTP();$mail->SMTPDebug=0;$mail->Host=(string)$this->config->get('report_mail.host');$mail->Port=(int)$this->config->get('report_mail.port',587);$mail->Timeout=20;
        $mode=(string)$this->config->get('report_mail.encryption','tls');if(!in_array($mode,['tls','smtps'],true))throw new \RuntimeException('TLS SMTP is required.');$mail->SMTPSecure=$mode==='smtps'?PHPMailer::ENCRYPTION_SMTPS:PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Username=(string)$this->config->get('report_mail.username','');$mail->Password=(string)$this->config->get('report_mail.password','');$mail->SMTPAuth=$mail->Username!=='';$mail->CharSet='UTF-8';
        $from=(string)$this->config->get('report_mail.from_address');$mail->setFrom($from,(string)$this->config->get('report_mail.from_name','Sentinel Reports'));$mail->addAddress($to);$mail->Subject=$subject;$mail->Body=$body;$mail->isHTML(false);
        $domain=substr(strrchr($from,'@'),1);$mail->MessageID='<sentinel-'.hash('sha256',$messageKey).'@'.$domain.'>';$mail->send();
    }
}
