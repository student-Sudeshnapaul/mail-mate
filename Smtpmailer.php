<?php
/**
 * ============================================================
 *  SmtpMailer — minimal SMTP client, no external libraries
 * ============================================================
 * Speaks raw SMTP over a socket, upgrades to TLS via STARTTLS,
 * authenticates with AUTH LOGIN, and sends a message.
 * Written for Gmail SMTP (smtp.gmail.com:587) but works with
 * any standard SMTP+STARTTLS server.
 */

class SmtpMailer
{
    private $host;
    private $port;
    private $username;
    private $password;
    private $fromName;
    private $socket;
    private $debug = [];

    public function __construct($host, $port, $username, $password, $fromName = '')
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->fromName = $fromName;
    }

    public function getDebugLog()
    {
        return implode("\n", $this->debug);
    }

    /**
     * Send an email.
     * @param string $toEmail
     * @param string $toName
     * @param string $subject
     * @param string $bodyHtml  HTML body (we send as text/html)
     * @return array ['success' => bool, 'message' => string]
     */
    public function send($toEmail, $toName, $subject, $bodyHtml)
    {
        try {
            $this->connect();
            $this->command("EHLO localhost", [250]);
            $this->command("STARTTLS", [220]);

            if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception("Failed to enable TLS encryption");
            }

            $this->command("EHLO localhost", [250]);
            $this->command("AUTH LOGIN", [334]);
            $this->command(base64_encode($this->username), [334]);
            $this->command(base64_encode($this->password), [235]);

            $this->command("MAIL FROM:<{$this->username}>", [250]);
            $this->command("RCPT TO:<{$toEmail}>", [250, 251]);
            $this->command("DATA", [354]);

            $headers = $this->buildHeaders($toEmail, $toName, $subject);
            $message = $headers . "\r\n" . $bodyHtml . "\r\n.";
            $this->command($message, [250]);

            $this->command("QUIT", [221]);
            fclose($this->socket);

            return ['success' => true, 'message' => 'Email sent successfully'];
        } catch (Exception $e) {
            if ($this->socket) {
                @fclose($this->socket);
            }
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'debug' => $this->getDebugLog()
            ];
        }
    }

    private function connect()
    {
        $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, 15);
        if (!$this->socket) {
            throw new Exception("Could not connect to SMTP server: $errstr ($errno)");
        }
        $this->readResponse(); // read initial banner
    }

    private function command($cmd, $expectedCodes)
    {
        // Don't log the AUTH/password lines verbatim in debug
        $logCmd = (strlen($cmd) > 60) ? substr($cmd, 0, 60) . '...[truncated]' : $cmd;
        $this->debug[] = "-> " . $logCmd;

        fwrite($this->socket, $cmd . "\r\n");
        $response = $this->readResponse();
        $code = (int) substr($response, 0, 3);

        if (!in_array($code, $expectedCodes)) {
            throw new Exception("SMTP error (expected " . implode('/', $expectedCodes) . ", got $code): $response");
        }

        return $response;
    }

    private function readResponse()
    {
        $data = '';
        while ($line = fgets($this->socket, 515)) {
            $data .= $line;
            // Multiline responses have a '-' after the 3-digit code;
            // the final line has a space instead.
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        $this->debug[] = "<- " . trim($data);
        return $data;
    }

    private function buildHeaders($toEmail, $toName, $subject)
    {
        $fromHeader = $this->fromName
            ? "{$this->fromName} <{$this->username}>"
            : $this->username;

        $toHeader = $toName ? "{$toName} <{$toEmail}>" : $toEmail;

        $encodedSubject = "=?UTF-8?B?" . base64_encode($subject) . "?=";

        return implode("\r\n", [
            "From: {$fromHeader}",
            "To: {$toHeader}",
            "Subject: {$encodedSubject}",
            "MIME-Version: 1.0",
            "Content-Type: text/html; charset=UTF-8",
            "Date: " . date('r'),
        ]);
    }
}