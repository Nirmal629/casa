<?php
declare(strict_types=1);
namespace App\Helpers;

/** Pure path policy shared by the development front controller and its tests. */
final class WebAccess
{
    public static function blocked(string $path): bool
    {
        $path = str_replace('\\', '/', rawurldecode($path));
        if (str_contains($path, "\0") || preg_match('~(?:^|/)\.{1,2}(?:/|$)~', $path)) return true;
        if (preg_match('~(?:^|/)\.[^/]+~', $path)) return true;
        if (preg_match('~^/(?:vendor|brain|scratch|scripts|tests|sql|PHPMailer|storage)(?:/|$)~i', $path)) return true;
        if (preg_match('~^/pos/admin/(?:app|system|writable|vendor)(?:/|$)~i', $path)) return true;
        if (preg_match('~^/app/.*\.php(?:/|$)~i', $path)) return true;
        if (preg_match('~^/(?:dbConnection[^/]*|init|.*migration[^/]*|reset)\.php$~i', $path)) return true;
        if (preg_match('~(?:^|/)(?:composer\.(?:json|lock)|AGENTS\.md|error_log|migration_[^/]+|ci_session[^/]*)$~i', $path)) return true;
        if (preg_match('~\.(?:env|ini|log|sql|sqlite|db|bak|old|orig|save|md|toml|ya?ml)(?:$|/)~i', $path)) return true;
        if (preg_match('~^/(?:uploads|profile_img)/.*\.(?:php\d*|phtml|phar|cgi|pl)(?:[./]|$)~i', $path)) return true;
        if (preg_match('~(?:^|/)(?:\x7e|\[[0-9]|AnuragBackup(?:/|$))~i', $path)) return true;
        if (preg_match('~(?:^|/)assets/.*\.(?:php\d*|phtml|phar)(?:[./]|$)~i', $path)) return true;
        return false;
    }
}
