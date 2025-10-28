<?php
namespace App\Sources;

abstract class AbstractSource {
    abstract public function parseRaceCard(string $html): void;
}