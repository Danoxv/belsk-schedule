<?php

namespace Src\Exceptions;

use Throwable;

class TerminateException extends \RuntimeException
{
    public const TYPE_INFO      = 'info';
    public const TYPE_WARNING   = 'warning';
    public const TYPE_DANGER    = 'danger';

    private string $type;

    public function __construct($message = '', $type = self::TYPE_WARNING, $code = 0, Throwable $previous = null)
    {
        $this->setType($type);

        parent::__construct($message, $code, $previous);
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