<?php

declare(strict_types=1);

/**
 * NAF Soluções Digitais
 * Endpoint seguro para formulário de contato
 *
 * Ambiente esperado:
 * PHP 8.1+
 *
 * Responsabilidades:
 * - Aceitar somente POST
 * - Validar Content-Type
 * - Validar Origin/Referer
 * - Validar JSON
 * - Validar e limitar campos
 * - Honeypot
 * - Rate limiting
 * - Proteção básica contra abuso
 * - Evitar header injection
 * - Envio de e-mail em texto simples
 * - Não expor detalhes internos ao cliente
 */


/* =========================================================
   CONFIGURAÇÃO
========================================================= */

const SITE_HOST = 'nafsolucoes.com';

const MAIL_TO = 'contato@nafsolucoes.com';

/*
 * Recomenda-se criar esta conta na Hostinger.
 * Exemplo:
 * contato@nafsolucoes.com
 *
 * O endereço é usado apenas no servidor.
 */
const MAIL_FROM = 'contato@nafsolucoes.com';

const RATE_LIMIT_MAX = 5;
const RATE_LIMIT_WINDOW = 600; // 10 minutos

const MAX_NAME_LENGTH = 100;
const MAX_EMAIL_LENGTH = 254;
const MAX_PHONE_LENGTH = 30;
const MAX_MESSAGE_LENGTH = 3000;

const MIN_MESSAGE_LENGTH = 10;


/* =========================================================
   RESPOSTA JSON
========================================================= */

function respond(
    int $statusCode,
    bool $success,
    string $message
): never {

    http_response_code($statusCode);

    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');

    echo json_encode(
        [
            'success' => $success,
            'message' => $message
        ],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}


/* =========================================================
   CABEÇALHOS DE SEGURANÇA
========================================================= */

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');


/* =========================================================
   MÉTODO HTTP
========================================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(
        405,
        false,
        'Método não permitido.'
    );
}

header('Allow: POST');


/* =========================================================
   CONTENT-TYPE
========================================================= */

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (
    stripos(
        $contentType,
        'application/json'
    ) !== 0
) {
    respond(
        415,
        false,
        'Formato de requisição não suportado.'
    );
}


/* =========================================================
   PROTEÇÃO ORIGIN / REFERER
========================================================= */

function isAllowedOrigin(): bool
{
    $allowedHosts = [
        SITE_HOST,
        'www.' . SITE_HOST
    ];

    /*
     * Origin é o principal mecanismo.
     */
    if (!empty($_SERVER['HTTP_ORIGIN'])) {

        $origin = trim($_SERVER['HTTP_ORIGIN']);

        $originParts = parse_url($origin);

        if (
            !is_array($originParts) ||
            empty($originParts['host'])
        ) {
            return false;
        }

        $scheme = strtolower(
            $originParts['scheme'] ?? ''
        );

        $host = strtolower(
            $originParts['host']
        );

        if (
            $scheme !== 'https' ||
            !in_array($host, $allowedHosts, true)
        ) {
            return false;
        }

        return true;
    }

    /*
     * Alguns ambientes podem não enviar Origin.
     * Nesse caso verificamos Referer.
     */
    if (!empty($_SERVER['HTTP_REFERER'])) {

        $referer = trim(
            $_SERVER['HTTP_REFERER']
        );

        $refererParts = parse_url($referer);

        if (
            !is_array($refererParts) ||
            empty($refererParts['host'])
        ) {
            return false;
        }

        $scheme = strtolower(
            $refererParts['scheme'] ?? ''
        );

        $host = strtolower(
            $refererParts['host']
        );

        return (
            $scheme === 'https' &&
            in_array($host, $allowedHosts, true)
        );
    }

    /*
     * Sem Origin e sem Referer:
     * rejeitamos por segurança.
     */
    return false;
}

if (!isAllowedOrigin()) {

    respond(
        403,
        false,
        'Requisição não autorizada.'
    );
}


/* =========================================================
   RATE LIMITING
========================================================= */

function getClientIp(): string
{
    /*
     * NÃO usamos X-Forwarded-For cegamente.
     *
     * Esse cabeçalho pode ser falsificado.
     *
     * Quando a aplicação estiver atrás de um proxy confiável,
     * essa parte poderá ser adaptada especificamente para ele.
     */
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    if (
        !filter_var(
            $ip,
            FILTER_VALIDATE_IP
        )
    ) {
        return 'unknown';
    }

    return $ip;
}


function rateLimitExceeded(): bool
{
    $ip = getClientIp();

    $key = hash(
        'sha256',
        $ip
    );

    $directory = sys_get_temp_dir();

    $file = $directory .
        DIRECTORY_SEPARATOR .
        'naf_contact_' .
        $key .
        '.json';

    $now = time();

    $data = [
        'timestamps' => []
    ];

    if (is_file($file)) {

        $content = @file_get_contents($file);

        if ($content !== false) {

            $decoded = json_decode(
                $content,
                true
            );

            if (
                is_array($decoded) &&
                isset($decoded['timestamps']) &&
                is_array($decoded['timestamps'])
            ) {
                $data = $decoded;
            }
        }
    }

    $timestamps = [];

    foreach ($data['timestamps'] as $timestamp) {

        if (
            is_int($timestamp) &&
            ($now - $timestamp) < RATE_LIMIT_WINDOW
        ) {
            $timestamps[] = $timestamp;
        }
    }

    if (count($timestamps) >= RATE_LIMIT_MAX) {
        return true;
    }

    $timestamps[] = $now;

    $data['timestamps'] = $timestamps;

    @file_put_contents(
        $file,
        json_encode($data),
        LOCK_EX
    );

    return false;
}

if (rateLimitExceeded()) {

    respond(
        429,
        false,
        'Muitas tentativas. Aguarde alguns minutos e tente novamente.'
    );
}


/* =========================================================
   LEITURA DO JSON
========================================================= */

$rawInput = file_get_contents('php://input');

if ($rawInput === false) {

    respond(
        400,
        false,
        'Não foi possível processar a requisição.'
    );
}


/*
 * Limite absoluto do corpo.
 *
 * Evita receber payloads gigantes.
 */
if (strlen($rawInput) > 12000) {

    respond(
        413,
        false,
        'Dados enviados excedem o limite permitido.'
    );
}


$data = json_decode(
    $rawInput,
    true
);

if (
    !is_array($data) ||
    json_last_error() !== JSON_ERROR_NONE
) {

    respond(
        400,
        false,
        'Dados inválidos.'
    );
}


/* =========================================================
   HONEYPOT
========================================================= */

$honeypot = $data['website'] ?? '';

if (
    !is_string($honeypot)
) {

    respond(
        400,
        false,
        'Dados inválidos.'
    );
}


/*
 * Se o campo invisível foi preenchido,
 * tratamos como possível bot.
 *
 * Retornamos uma resposta genérica para não
 * ensinar o mecanismo ao atacante.
 */
if (trim($honeypot) !== '') {

    respond(
        200,
        true,
        'Mensagem recebida.'
    );
}


/* =========================================================
   EXTRAÇÃO DOS CAMPOS
========================================================= */

$name = $data['name'] ?? '';
$email = $data['email'] ?? '';
$phone = $data['phone'] ?? '';
$message = $data['message'] ?? '';


/* =========================================================
   TIPO DOS CAMPOS
========================================================= */

if (
    !is_string($name) ||
    !is_string($email) ||
    !is_string($phone) ||
    !is_string($message)
) {

    respond(
        400,
        false,
        'Dados inválidos.'
    );
}


/* =========================================================
   NORMALIZAÇÃO
========================================================= */

$name = trim($name);
$email = trim($email);
$phone = trim($phone);
$message = trim($message);


/* =========================================================
   LIMITES
========================================================= */

if (
    $name === '' ||
    mb_strlen($name) > MAX_NAME_LENGTH
) {

    respond(
        422,
        false,
        'Informe um nome válido.'
    );
}

if (
    $email === '' ||
    mb_strlen($email) > MAX_EMAIL_LENGTH ||
    !filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    )
) {

    respond(
        422,
        false,
        'Informe um e-mail válido.'
    );
}

if (
    mb_strlen($phone) > MAX_PHONE_LENGTH
) {

    respond(
        422,
        false,
        'Telefone inválido.'
    );
}

if (
    mb_strlen($message) < MIN_MESSAGE_LENGTH ||
    mb_strlen($message) > MAX_MESSAGE_LENGTH
) {

    respond(
        422,
        false,
        'A mensagem deve ter entre 10 e 3000 caracteres.'
    );
}


/* =========================================================
   PROTEÇÃO CONTRA HEADER INJECTION
========================================================= */

if (
    preg_match(
        "/[\r\n]/",
        $email
    )
) {

    respond(
        422,
        false,
        'E-mail inválido.'
    );
}

if (
    preg_match(
        "/[\r\n]/",
        $name
    )
) {

    respond(
        422,
        false,
        'Nome inválido.'
    );
}


/* =========================================================
   SANITIZAÇÃO DE CONTROLE
========================================================= */

/*
 * Não usamos strip_tags() para "proteger" o e-mail.
 *
 * O conteúdo será enviado como texto simples.
 *
 * Isso evita transformar a entrada do usuário
 * em HTML executável.
 */

$name = preg_replace(
    '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u',
    '',
    $name
);

$phone = preg_replace(
    '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u',
    '',
    $phone
);

$message = preg_replace(
    '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u',
    '',
    $message
);

if (
    $name === null ||
    $phone === null ||
    $message === null
) {

    respond(
        422,
        false,
        'Dados inválidos.'
    );
}


/* =========================================================
   ASSUNTO FIXO
========================================================= */

$subject = 'Novo contato pelo site NAF Soluções Digitais';


/* =========================================================
   CORPO DO E-MAIL
========================================================= */

$emailBody =
    "NOVO CONTATO - NAF SOLUÇÕES DIGITAIS\n" .
    "=====================================\n\n" .

    "Nome:\n" .
    $name .
    "\n\n" .

    "E-mail:\n" .
    $email .
    "\n\n" .

    "Telefone:\n" .
    ($phone !== '' ? $phone : 'Não informado') .
    "\n\n" .

    "Mensagem:\n" .
    $message .
    "\n\n" .

    "-------------------------------------\n" .
    "Mensagem enviada pelo formulário do site.\n";


/* =========================================================
   HEADERS DO E-MAIL
========================================================= */

$headers = [];

$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-Type: text/plain; charset=UTF-8';
$headers[] = 'From: NAF Soluções Digitais <' . MAIL_FROM . '>';
$headers[] = 'Reply-To: ' . $email;
$headers[] = 'X-Mailer: NAF-Solucoes-Digitais';


/* =========================================================
   ENVIO
========================================================= */

$sent = @mail(
    MAIL_TO,
    $subject,
    $emailBody,
    implode("\r\n", $headers)
);


/* =========================================================
   RESULTADO
========================================================= */

if (!$sent) {

    /*
     * Não revelar ao visitante detalhes do servidor,
     * SMTP, PHP ou configuração interna.
     */

    error_log(
        'NAF contact: falha no envio de e-mail.'
    );

    respond(
        500,
        false,
        'Não foi possível enviar sua mensagem neste momento.'
    );
}


/* =========================================================
   SUCESSO
========================================================= */

respond(
    200,
    true,
    'Mensagem enviada com sucesso. Entraremos em contato em breve.'
);