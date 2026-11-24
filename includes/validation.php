<?php
declare(strict_types=1);

function validar_nombre(string $nombre): ?string
{
    $len = mb_strlen($nombre);
    if ($len < 3 || $len > 120) {
        return 'Escribe tu nombre completo (entre 3 y 120 caracteres).';
    }
    if (!preg_match('/^[\p{L}][\p{L}\p{M}\s\'.-]*$/u', $nombre)) {
        return 'El nombre solo puede contener letras, espacios, apóstrofes, puntos y guiones.';
    }
    return null;
}

function validar_correo(string $correo): ?string
{
    if ($correo === '' || mb_strlen($correo) > 190 || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        return 'Ingresa un correo electrónico válido.';
    }
    return null;
}

/** Reglas visibles en el formulario: 8+ caracteres, mayúscula, número y carácter especial. */
function reglas_password(string $p): array
{
    return [
        'long'  => mb_strlen($p) >= 8,
        'may'   => (bool)preg_match('/\p{Lu}/u', $p),
        'num'   => (bool)preg_match('/\d/', $p),
        'esp'   => (bool)preg_match('/[^\p{L}\d\s]/u', $p),
    ];
}

function validar_password(string $p): ?string
{
    if (strlen($p) > 200) {
        return 'La contraseña es demasiado larga.';
    }
    $r = reglas_password($p);
    if (in_array(false, $r, true)) {
        return 'La contraseña debe tener mínimo 8 caracteres, una mayúscula, un número y un carácter especial.';
    }
    return null;
}
