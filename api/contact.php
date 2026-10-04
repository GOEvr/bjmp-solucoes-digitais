<?php

declare(strict_types=1);

/**
 * BJMP Soluções Digitais
 * Endpoint seguro para formulário de contato
 *
 * PHP 8.1+
 *
 * Controles:
 * - somente POST
 * - somente JSON
 * - validação Origin/Referer
 * - HTTPS + domínio autorizado
 * - limite de payload
 * - validação dos campos
 * - honeypot
 * - limpeza de caracteres de controle
 * - proteção contra header injection
 * - rate limiting com flock()
 * - fail-closed no rate limiting
 * - respostas genéricas
 * - nenhum segredo exposto
 */


/* =========================================================
   CONFIGURAÇÃO
========================================================= */

const SITE_HOST = 'www.bjmpsolucoes.com.br';

const MAIL_TO = 'contato@bjmpsolucoes.com.br';

const MAIL_FROM = 'contato@bjmpsolucoes.com.br';


/* =========================================================
   RATE LIMIT
========================================================= */

/*
 * Máximo de 5 tentativas por IP
 * dentro de uma janela de 10 minutos.
 */

const RATE_LIMIT_MAX = 5;

const RATE_LIMIT_WINDOW = 600;


/* =========================================================
   LIMITES
========================================================= */

const MAX_BODY_BYTES = 12288;

const MAX_NAME_LENGTH = 100;

const MAX_EMAIL_LENGTH = 254;

const MAX_PHONE_LENGTH = 30;

const MAX_MESSAGE_LENGTH = 2000;

const MIN_MESSAGE_LENGTH = 10;


/* =========================================================
   RESPOSTA JSON
========================================================= */

function respond(
    int $status,
    bool $success,
    string $message
): never {

    http_response_code($status);

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

    header('Allow: POST');

    respond(
        405,
        false,
        'Método não permitido.'
    );

}


/* =========================================================
   CONTENT-TYPE
========================================================= */

$contentType =
    trim(
        (string)($_SERVER['CONTENT_TYPE'] ?? '')
    );


/*
 * Aceita:
 *
 * application/json
 * application/json; charset=UTF-8
 */

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
   HOST AUTORIZADO
========================================================= */

function allowedHost(string $host): bool
{
    $host =
        strtolower(
            rtrim(
                trim($host),
                '.'
            )
        );

    return (
        $host === 'www.bjmpsolucoes.com.br' ||
        $host === 'bjmpsolucoes.com.br'
    );
}


/* =========================================================
   ORIGIN / REFERER
========================================================= */

function validOrigin(): bool
{

    /*
     * Preferimos o cabeçalho Origin.
     */

    if (
        !empty($_SERVER['HTTP_ORIGIN'])
    ) {

        $origin =
            trim(
                (string)$_SERVER['HTTP_ORIGIN']
            );

        $parts =
            parse_url(
                $origin
            );

        if (
            !is_array($parts) ||
            empty($parts['host'])
        ) {

            return false;

        }

        $scheme =
            strtolower(
                (string)($parts['scheme'] ?? '')
            );

        $host =
            strtolower(
                rtrim(
                    (string)$parts['host'],
                    '.'
                )
            );

        /*
         * Não aceitamos credenciais dentro da URL.
         */

        if (
            isset($parts['user']) ||
            isset($parts['pass'])
        ) {

            return false;

        }

        return (
            $scheme === 'https' &&
            allowedHost($host)
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
                (string)$_SERVER['HTTP_REFERER']
            );

        $parts =
            parse_url(
                $referer
            );

        if (
            !is_array($parts) ||
            empty($parts['host'])
        ) {

            return false;

        }

        $scheme =
            strtolower(
                (string)($parts['scheme'] ?? '')
            );

        $host =
            strtolower(
                rtrim(
                    (string)$parts['host'],
                    '.'
                )
            );

        if (
            isset($parts['user']) ||
            isset($parts['pass'])
        ) {

            return false;

        }

        return (
            $scheme === 'https' &&
            allowedHost($host)
        );

    }


    /*
     * Sem Origin e sem Referer:
     * rejeitamos.
     */

    return false;
}


if (!validOrigin()) {

    respond(
        403,
        false,
        'Requisição não autorizada.'
    );

}


/* =========================================================
   IP DO CLIENTE
========================================================= */

function clientIp(): string
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

        return '0.0.0.0';

    }

    return $ip;
}


/* =========================================================
   RATE LIMITING
========================================================= */

function rateLimited(): bool
{

    $directory =
        sys_get_temp_dir() .
        DIRECTORY_SEPARATOR .
        'bjmp_contact_rl';


    /*
     * Criamos um diretório privado.
     */

    if (
        !is_dir($directory) &&
        !@mkdir(
            $directory,
            0700,
            true
        ) &&
        !is_dir($directory)
    ) {

        /*
         * Fail-closed:
         * se não conseguimos controlar
         * as tentativas, bloqueamos.
         */

        return true;

    }


    /*
     * Hash do IP.
     *
     * O IP real não aparece no nome
     * do arquivo.
     */

    $key =
        hash(
            'sha256',
            clientIp() . '|bjmp-contact'
        );


    $file =
        $directory .
        DIRECTORY_SEPARATOR .
        $key .
        '.json';


    /*
     * c+ cria o arquivo se necessário.
     */

    $fp =
        @fopen(
            $file,
            'c+'
        );


    if (
        $fp === false
    ) {

        return true;

    }


    try {

        /*
         * Bloqueio exclusivo durante
         * leitura e escrita.
         */

        if (
            !flock(
                $fp,
                LOCK_EX
            )
        ) {

            fclose($fp);

            return true;

        }


        rewind($fp);


        $contents =
            stream_get_contents(
                $fp
            );


        $record =
            is_string($contents)
                ? json_decode(
                    $contents,
                    true
                )
                : null;


        if (
            !is_array($record)
        ) {

            $record = [
                'start' => time(),
                'count' => 0
            ];

        }


        $now =
            time();


        $start =
            (int)(
                $record['start'] ?? 0
            );


        $count =
            (int)(
                $record['count'] ?? 0
            );


        /*
         * Nova janela de tempo.
         */

        if (
            $start <= 0 ||
            ($now - $start) >= RATE_LIMIT_WINDOW
        ) {

            $record = [
                'start' => $now,
                'count' => 0
            ];

        }


        /*
         * Conta a tentativa atual.
         */

        $record['count'] =
            (int)$record['count'] + 1;


        /*
         * Reescreve o arquivo protegido
         * pelo flock.
         */

        ftruncate(
            $fp,
            0
        );

        rewind($fp);


        $encoded =
            json_encode(
                $record,
                JSON_UNESCAPED_SLASHES
            );


        if (
            $encoded === false ||
            fwrite(
                $fp,
                $encoded
            ) === false
        ) {

            flock(
                $fp,
                LOCK_UN
            );

            fclose($fp);

            return true;

        }


        fflush($fp);


        flock(
            $fp,
            LOCK_UN
        );

        fclose($fp);


        return (
            (int)$record['count'] >
            RATE_LIMIT_MAX
        );

    } catch (
        Throwable
    ) {

        @flock(
            $fp,
            LOCK_UN
        );

        @fclose($fp);

        /*
         * Fail-closed.
         */

        return true;

    }

}


if (
    rateLimited()
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

$raw =
    file_get_contents(
        'php://input'
    );


if (
    $raw === false
) {

    respond(
        400,
        false,
        'Dados inválidos.'
    );

}


/* =========================================================
   LIMITE DE PAYLOAD
========================================================= */

if (
    strlen($raw) >
    MAX_BODY_BYTES
) {

    respond(
        413,
        false,
        'Requisição muito grande.'
    );

}


/* =========================================================
   DECODIFICAÇÃO JSON
========================================================= */

$data =
    json_decode(
        $raw,
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

    /*
     * Não revelamos ao robô que ele
     * foi identificado.
     */

    respond(
        200,
        true,
        'Mensagem recebida.'
    );

}


/* =========================================================
   LIMPEZA DE TEXTO
========================================================= */

function cleanText(
    mixed $value,
    int $maxLength
): string {

    if (
        !is_string($value)
    ) {

        return '';

    }


    $value =
        trim($value);


    /*
     * Remove caracteres de controle,
     * preservando UTF-8.
     */

    $value =
        preg_replace(
            '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u',
            '',
            $value
        ) ?? '';


    return mb_substr(
        $value,
        0,
        $maxLength
    );
}


/* =========================================================
   CAMPOS DO FORMULÁRIO
========================================================= */

/*
 * Estes nomes correspondem exatamente
 * ao formulário atual do index.html:
 *
 * nome
 * email
 * telefone
 * mensagem
 * website
 */

$name =
    cleanText(
        $data['nome'] ?? '',
        MAX_NAME_LENGTH
    );


$email =
    cleanText(
        $data['email'] ?? '',
        MAX_EMAIL_LENGTH
    );


$phone =
    cleanText(
        $data['telefone'] ?? '',
        MAX_PHONE_LENGTH
    );


$message =
    cleanText(
        $data['mensagem'] ?? '',
        MAX_MESSAGE_LENGTH
    );


/* =========================================================
   VALIDAÇÃO DO NOME
========================================================= */

if (
    $name === '' ||
    mb_strlen($name) < 2
) {

    respond(
        422,
        false,
        'Informe seu nome.'
    );

}


/* =========================================================
   HEADER INJECTION
========================================================= */

if (
    preg_match(
        '/[\r\n]/',
        $name
    ) ||
    preg_match(
        '/[\r\n]/',
        $email
    )
) {

    respond(
        422,
        false,
        'Dados inválidos.'
    );

}


/* =========================================================
   VALIDAÇÃO DO E-MAIL
========================================================= */

if (
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


/* =========================================================
   VALIDAÇÃO DO TELEFONE
========================================================= */

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


/* =========================================================
   VALIDAÇÃO DA MENSAGEM
========================================================= */

if (
    $message === '' ||
    mb_strlen($message) <
    MIN_MESSAGE_LENGTH
) {

    respond(
        422,
        false,
        'Escreva uma mensagem com mais detalhes.'
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

$body =
    "Novo contato pelo site BJMP Soluções Digitais\n\n" .

    "Nome: " .
    $name .
    "\n" .

    "E-mail: " .
    $email .
    "\n" .

    "Telefone: " .
    (
        $phone !== ''
            ? $phone
            : 'Não informado'
    ) .
    "\n\n" .

    "Mensagem:\n" .
    $message .
    "\n";


/* =========================================================
   HEADERS DO E-MAIL
========================================================= */

/*
 * MAIL_FROM é fixo.
 *
 * O e-mail do visitante é utilizado
 * somente no Reply-To após validação.
 */

$headers = [

    'MIME-Version: 1.0',

    'Content-Type: text/plain; charset=UTF-8',

    'From: BJMP Soluções Digitais <' .
    MAIL_FROM .
    '>',

    'Reply-To: ' .
    $email,

    'X-Mailer: BJMP-Solucoes-Digitais'

];


/* =========================================================
   ENVIO
========================================================= */

$sent =
    @mail(
        MAIL_TO,
        $subject,
        $body,
        implode(
            "\r\n",
            $headers
        )
    );


/* =========================================================
   RESULTADO
========================================================= */

if (
    !$sent
) {

    /*
     * O visitante não recebe detalhes
     * internos do servidor.
     */

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
