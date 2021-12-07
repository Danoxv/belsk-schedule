<?php

namespace Src\Support;

class Session
{
    public function __construct()
    {
        if (!isset($_SESSION)) {
            session_start();
        }
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function set(string $name, $value): void
    {
        $_SESSION[$name] = $value;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed|null
     */
    public function get(string $name, $default = null)
    {
        return $_SESSION[$name] ?? $default;
    }

    /**
     * @param string $name
     */
    public function del(string $name): void
    {
        unset($_SESSION[$name]);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function exists(string $name): bool
    {
        return array_key_exists($name, $_SESSION);
    }

    /**
     * Destroy all session data.
     */
    public function destroy(): void
    {
        $_SESSION = [];
        setcookie(session_name(), '', time() - 2592000, '/'); // subtract 30 days
        session_destroy();
    }
}