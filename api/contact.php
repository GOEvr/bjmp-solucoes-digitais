<?php

declare(strict_types=1);

/**
 * BJMP Soluções Digitais
 * Endpoint seguro para formulário de contato
 *
 * PHP 8.1+
 *
 * Segurança:
 * - somente POST
 * - Content-Type JSON
 * - validação Origin/Referer
 * - validação JSON
 * - limite de payload
 * - validação dos campos
 * - honeypot
 * - rate limiting
 * - proteção contra header injection
 * - e-mail em texto simples
 * - respostas genéricas ao cliente
 */


/* =========================================================
   CONFIGURAÇÃO
========================================================= */

/*
 * IMPORTANTE:
 *
 * Substitua os três valores abaixo pelos dados reais
 * do novo domínio/e-mail da BJMP.
 */

const SITE_HOST = 'SEU-DOMINIO-AQUI.COM';

const MAIL_TO = 'SEU-EMAIL-AQUI@DOMINIO.COM';

const MAIL_FROM = 'SEU-EMAIL-AQUI@DOMINIO.COM';


/* =========================================================
   RATE LIMIT
========================================================= */

const RATE_LIMIT_MAX = 5;

const RATE_LIMIT_WINDOW = 600;


/* =========================================================
   LIMITES
========================================================= */

const MAX_NAME_LENGTH = 100;

const MAX_EMAIL_LENGTH = 254;

const MAX_PHONE_LENGTH = 30;

const MAX_MESSAGE_LENGTH = 2000;

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

    header(
        'Content-Type: application/json; charset=UTF-8'
    );

    header(
        'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
    );

    header(
        'Pragma: no-cache'
    );

    echo json_encode(
        [
            'success' => $success,
            'message' => $message
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


/* =========================================================
   CABEÇALHOS DE SEGURANÇA
========================================================= */

header(
    'X-Content-Type-Options: nosniff'
);

header(
    'Referrer-Policy: strict-origin-when-cross-origin'
);

header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);

header(
    'Pragma: no-cache'
);


/* =========================================================
   MÉTODO HTTP
========================================================= */

if (
    ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST'
) {

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

$contentType =
    $_SERVER['CONTENT_TYPE'] ?? '';

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
   ORIGIN / REFERER
========================================================= */

function isAllowedOrigin(): bool
{

    $allowedHosts = [
        strtolower(SITE_HOST),
        'www.' . strtolower(SITE_HOST)
    ];


    /*
     * Preferimos Origin.
     */

    if (
        !empty($_SERVER['HTTP_ORIGIN'])
    ) {

        $origin =
            trim(
                $_SERVER['HTTP_ORIGIN']
            );

        $originParts =
            parse_url($origin);

        if (
            !is_array($originParts) ||
            empty($originParts['host'])
        ) {

            return false;

        }


        $scheme =
            strtolower(
                $originParts['scheme'] ?? ''
            );

        $host =
            strtolower(
                $originParts['host']
            );


        return (
            $scheme === 'https' &&
            in_array(
                $host,
                $allowedHosts,
                true
            )
        );

    }


    /*
     * Fallback para Referer.
     */

    if (
        !empty($_SERVER['HTTP_REFERER'])
    ) {

        $referer =
            trim(
                $_SERVER['HTTP_REFERER']
            );

        $refererParts =
            parse_url($referer);

        if (
            !is_array($refererParts) ||
            empty($refererParts['host'])
        ) {

            return false;

        }


        $scheme =
            strtolower(
                $refererParts['scheme'] ?? ''
            );

        $host =
            strtolower(
                $refererParts['host']
            );


        return (
            $scheme === 'https' &&
            in_array(
                $host,
                $allowedHosts,
                true
            )
        );

    }


    /*
     * Sem Origin e sem Referer:
     * rejeitamos.
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
   IP DO CLIENTE
========================================================= */

function getClientIp(): string
{

    /*
     * Não confiamos cegamente em
     * X-Forwarded-For.
     */

    $ip =
        $_SERVER['REMOTE_ADDR'] ?? '';


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


/* =========================================================
   RATE LIMITING
========================================================= */

function rateLimitExceeded(): bool
{

    $ip =
        getClientIp();


    $key =
        hash(
            'sha256',
            $ip
        );


    $directory =
        sys_get_temp_dir();


    $file =
        $directory .
        DIRECTORY_SEPARATOR .
        'bjmp_contact_' .
        $key .
        '.json';


    $now =
        time();


    $data = [
        'timestamps' => []
    ];


    if (
        is_file($file)
    ) {

        $content =
            @file_get_contents(
                $file
            );


        if (
            $content !== false
        ) {

            $decoded =
                json_decode(
                    $content,
                    true
                );


            if (
                is_array($decoded) &&
                isset(
                    $decoded['timestamps']
                ) &&
                is_array(
                    $decoded['timestamps']
                )
            ) {

                $data =
                    $decoded;

            }

        }

    }


    $timestamps = [];


    foreach (
        $data['timestamps']
        as $timestamp
    ) {

        if (
            is_int($timestamp) &&
            ($now - $timestamp) <
            RATE_LIMIT_WINDOW
        ) {

            $timestamps[] =
                $timestamp;

        }

    }


    if (
        count($timestamps) >=
        RATE_LIMIT_MAX
    ) {

        return true;

    }


    $timestamps[] =
        $now;


    $data['timestamps'] =
        $timestamps;


    @file_put_contents(
        $file,
        json_encode($data),
        LOCK_EX
    );


    return false;

}


if (
    rateLimitExceeded()
) {

    respond(
        429,
        false,
        'Muitas tentativas. Aguarde alguns minutos e tente novamente.'
    );

}


/* =========================================================
   LEITURA DO JSON
========================================================= */

$rawInput =
    file_get_contents(
        'php://input'
    );


if (
    $rawInput === false
) {

    respond(
        400,
        false,
        'Não foi possível processar a requisição.'
    );

}


/*
 * Limite absoluto do corpo.
 */

if (
    strlen($rawInput) > 12000
) {

    respond(
        413,
        false,
        'Dados enviados excedem o limite permitido.'
    );

}


$data =
    json_decode(
        $rawInput,
        true
    );


if (
    !is_array($data) ||
    json_last_error() !==
    JSON_ERROR_NONE
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

$honeypot =
    $data['website'] ?? '';


if (
    !is_string($honeypot)
) {

    respond(
        400,
        false,
        'Dados inválidos.'
    );

}


if (
    trim($honeypot) !== ''
) {

    respond(
        200,
        true,
        'Mensagem recebida.'
    );

}


/* =========================================================
   CAMPOS
========================================================= */

$name =
    $data['name'] ?? '';

$email =
    $data['email'] ?? '';

$phone =
    $data['phone'] ?? '';

$message =
    $data['message'] ?? '';


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

$name =
    trim($name);

$email =
    trim($email);

$phone =
    trim($phone);

$message =
    trim($message);


/* =========================================================
   LIMITES
========================================================= */

if (
    $name === '' ||
    mb_strlen($name) >
    MAX_NAME_LENGTH
) {

    respond(
        422,
        false,
        'Informe um nome válido.'
    );

}


if (
    $email === '' ||
    mb_strlen($email) >
    MAX_EMAIL_LENGTH ||
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
    mb_strlen($phone) >
    MAX_PHONE_LENGTH
) {

    respond(
        422,
        false,
        'Telefone inválido.'
    );

}


if (
    mb_strlen($message) <
    MIN_MESSAGE_LENGTH ||
    mb_strlen($message) >
    MAX_MESSAGE_LENGTH
) {

    respond(
        422,
        false,
        'A mensagem deve ter entre 10 e 2000 caracteres.'
    );

}


/* =========================================================
   HEADER INJECTION
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
   CONTROLE DE CARACTERES
========================================================= */

$name =
    preg_replace(
        '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u',
        '',
        $name
    );


$phone =
    preg_replace(
        '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u',
        '',
        $phone
    );


$message =
    preg_replace(
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
   ASSUNTO
========================================================= */

$subject =
    'Novo contato pelo site BJMP Soluções Digitais';


/* =========================================================
   CORPO DO E-MAIL
========================================================= */

$emailBody =
    "NOVO CONTATO - BJMP SOLUÇÕES DIGITAIS\n" .
    "=====================================\n\n" .

    "Nome:\n" .
    $name .
    "\n\n" .

    "E-mail:\n" .
    $email .
    "\n\n" .

    "Telefone:\n" .
    (
        $phone !== ''
            ? $phone
            : 'Não informado'
    ) .
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

$headers[] =
    'MIME-Version: 1.0';

$headers[] =
    'Content-Type: text/plain; charset=UTF-8';

$headers[] =
    'From: BJMP Soluções Digitais <' .
    MAIL_FROM .
    '>';

$headers[] =
    'Reply-To: ' .
    $email;

$headers[] =
    'X-Mailer: BJMP-Solucoes-Digitais';


/* =========================================================
   ENVIO
========================================================= */

$sent =
    @mail(
        MAIL_TO,
        $subject,
        $emailBody,
        implode(
            "\r\n",
            $headers
        )
    );


/* =========================================================
   RESULTADO
========================================================= */

if (!$sent) {

    error_log(
        'BJMP contact: falha no envio de e-mail.'
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
