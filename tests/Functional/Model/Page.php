<?php

namespace Psi\Bridge\ObjectAgent\Bolt\Tests\Functional\Model;

use Bolt\Storage\Entity\Entity;

class Page extends Entity
{
    public $id;
    public $title;
    public $path;

    public function __construct(string $title = null)
    {
        $this->title = $title;
    }
}

