<?php

namespace Src\Config;

class SheetProcessingConfig
{
    public ?string $studentsGroup = null;
    public bool $forceApplyMendeleeva4ToLessons = false;
    public bool $processGroups = true;
    public bool $detectMendeleeva4 = false;

    public function __construct(array $config)
    {
        $this->applyFromArray($config);
    }

    /**
     * @param array $props
     */
    private function applyFromArray(array $props): void
    {
        foreach ($props as $propName => $propValue) {
            $this->$propName = $propValue;
        }
    }
}