<?php

namespace Src\Config;

use Src\Traits\PropertiesApplier;

class SheetProcessingConfig
{
    use PropertiesApplier;

    public ?string $studentsGroup = null;
    public bool $forceApplyMendeleeva4ToLessons = false;
    public bool $processGroups = true;
    public bool $detectMendeleeva4 = false;

    public function __construct(array $config)
    {
        $this->applyFromArray($config);
    }
}