<?php

namespace Src\Exceptions;

use Src\Config\AppConfig;
use Throwable;

class TerminateException extends \RuntimeException
{
    public const TYPE_INFO      = 'info';
    public const TYPE_WARNING   = 'warning';
    public const TYPE_DANGER    = 'danger';

    public const ABSTRACT_ERROR_MSG = 'Что-то пошло не так';

    private string $type;

    public function __construct($message = self::ABSTRACT_ERROR_MSG, $type = self::TYPE_WARNING, $code = 0, Throwable $previous = null)
    {
        $this->setType($type);

        parent::__construct($message, $code, $previous);
    }

    /**
     * @param Throwable $throwable
     * @return self
     */
    public static function fromThrowable(\Throwable $throwable): self
    {
        $message = self::ABSTRACT_ERROR_MSG;

        if (AppConfig::getInstance()->debug) {
            $message = (string) $throwable;
        }

        return new self($message);
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }
}