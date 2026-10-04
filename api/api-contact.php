<?php

declare(strict_types=1);

/**
 * BJMP Soluções Digitais
 * Endpoint de contato — PHP 8.1+
 *
 * Controles:
 * - POST + JSON
 * - limite de payload
 * - Origin/Referer same-origin
 * - honeypot
 * - validação/sanitização
 * - proteção CRLF/header injection
 * - rate limit com flock
 * - respostas genéricas
 * - nenhum segredo no frontend
 */

const SITE_HOST = 'www.bjmpsolucoes.com.br';
const MAIL_TO = 'contato@bjmpsolucoes.com.br';
const MAIL_FROM = 'contato@bjmpsolucoes.com.br';

const RATE_LIMIT_MAX = 5;
const RATE_LIMIT_WINDOW = 600;

const MAX_BODY_BYTES = 12288;
const MAX_NAME_LENGTH = 100;
const MAX_EMAIL_LENGTH = 254;
const MAX_PHONE_LENGTH = 30;
const MAX_MESSAGE_LENGTH = 2000;
const MIN_MESSAGE_LENGTH = 10;

function respond(int $status, bool $success, string $message): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');

    echo json_encode(
        ['success' => $success, 'message' => $message],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    respond(405, false, 'Método não permitido.');
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== 0) {
    respond(415, false, 'Formato de requisição não suportado.');
}

function allowedHost(string $host): bool
{
    return strtolower($host) === SITE_HOST
        || strtolower($host) === 'bjmpsolucoes.com.br';
}

function validOrigin(): bool
{
    if (!empty($_SERVER['HTTP_ORIGIN'])) {
        $parts = parse_url(trim($_SERVER['HTTP_ORIGIN']));

        if (!is_array($parts) || empty($parts['host'])) {
            return false;
        }

        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        $host = strtolower((string)$parts['host']);

        return $scheme === 'https' && allowedHost($host);
    }

    if (!empty($_SERVER['HTTP_REFERER'])) {
        $parts = parse_url(trim($_SERVER['HTTP_REFERER']));

        if (!is_array($parts) || empty($parts['host'])) {
            return false;
        }

        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        $host = strtolower((string)$parts['host']);

        return $scheme === 'https' && allowedHost($host);
    }

    return false;
}

if (!validOrigin()) {
    respond(403, false, 'Origem não autorizada.');
}

$raw = file_get_contents('php://input');

if ($raw === false || strlen($raw) > MAX_BODY_BYTES) {
    respond(413, false, 'Requisição muito grande.');
}

$data = json_decode($raw, true);

if (!is_array($data)) {
    respond(400, false, 'Dados inválidos.');
}

function cleanText(mixed $value, int $maxLength): string
{
    if (!is_string($value)) {
        return '';
    }

    $value = trim($value);

    // Remove caracteres de controle, preservando UTF-8.
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';

    return mb_substr($value, 0, $maxLength);
}

$name = cleanText($data['nome'] ?? '', MAX_NAME_LENGTH);
$email = cleanText($data['email'] ?? '', MAX_EMAIL_LENGTH);
$phone = cleanText($data['telefone'] ?? '', MAX_PHONE_LENGTH);
$message = cleanText($data['mensagem'] ?? '', MAX_MESSAGE_LENGTH);
$honeypot = cleanText($data['website'] ?? '', 100);

if ($honeypot !== '') {
    respond(200, true, 'Mensagem recebida.');
}

if ($name === '' || mb_strlen($name) < 2) {
    respond(422, false, 'Informe seu nome.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(422, false, 'Informe um e-mail válido.');
}

if ($message === '' || mb_strlen($message) < MIN_MESSAGE_LENGTH) {
    respond(422, false, 'Escreva uma mensagem com mais detalhes.');
}

if (
    preg_match('/[\r\n]/', $email) ||
    preg_match('/[\r\n]/', $name)
) {
    respond(422, false, 'Dados inválidos.');
}

function clientIp(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    return filter_var($ip, FILTER_VALIDATE_IP)
        ? $ip
        : '0.0.0.0';
}

function rateLimited(): bool
{
    $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bjmp_contact_rl';

    if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
        // Fail closed: se o mecanismo de rate limit não puder ser criado,
        // não processamos o envio.
        return true;
    }

    $key = hash('sha256', clientIp() . '|bjmp-contact');
    $file = $dir . DIRECTORY_SEPARATOR . $key . '.json';

    $fp = @fopen($file, 'c+');

    if ($fp === false) {
        return true;
    }

    try {
        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            return true;
        }

        $contents = stream_get_contents($fp);
        $record = is_string($contents) ? json_decode($contents, true) : null;

        if (!is_array($record)) {
            $record = ['start' => time(), 'count' => 0];
        }

        $now = time();

        if (($now - (int)($record['start'] ?? 0)) >= RATE_LIMIT_WINDOW) {
            $record = ['start' => $now, 'count' => 0];
        }

        $record['count'] = (int)$record['count'] + 1;

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($record));
        fflush($fp);

        flock($fp, LOCK_UN);
        fclose($fp);

        return $record['count'] > RATE_LIMIT_MAX;
    } catch (Throwable) {
        @flock($fp, LOCK_UN);
        @fclose($fp);
        return true;
    }
}

if (rateLimited()) {
    respond(429, false, 'Muitas tentativas. Aguarde alguns minutos e tente novamente.');
}

$subject = 'Novo contato — BJMP Soluções Digitais';

$body =
    "Novo contato pelo site BJMP Soluções Digitais\n\n" .
    "Nome: {$name}\n" .
    "E-mail: {$email}\n" .
    "Telefone: {$phone}\n\n" .
    "Mensagem:\n{$message}\n";

$headers = [
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'From: BJMP Soluções Digitais <' . MAIL_FROM . '>',
    'Reply-To: ' . $email,
    'X-Mailer: BJMP Contact Endpoint'
];

$sent = @mail(
    MAIL_TO,
    $subject,
    $body,
    implode("\r\n", $headers)
);

if (!$sent) {
    // Não expõe detalhes internos ao visitante.
    error_log('BJMP contact: PHP mail() failed.');
    respond(500, false, 'Não foi possível enviar sua mensagem agora.');
}

respond(200, true, 'Mensagem enviada com sucesso.');
