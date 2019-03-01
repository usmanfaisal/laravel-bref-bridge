<?php declare(strict_types=1);

/**
 * Package: laravel-bref-bridge
 * Create Date: 2019-02-27
 * Created Time: 13:46
 */

namespace STS\Bref\Bridge\Models;

use Threaded;

class LambdaResult extends Threaded
{
    /** @var array */
    protected $result;

    public function getResult(): array
    {
        return $this->result;
    }

    public function setResult(array $result): array
    {
        $this->result = $result;
    }
}