<?php

namespace Src\Traits;

trait PropertiesApplier
{
    /**
     * @param array $props
     */
    protected function applyFromArray(array $props)
    {
        foreach ($props as $propName => $propValue) {
            $this->$propName = $propValue;
        }
    }
}