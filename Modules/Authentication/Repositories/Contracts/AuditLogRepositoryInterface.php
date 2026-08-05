<?php

namespace Modules\Authentication\Repositories\Contracts;

use App\Core\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Modules\Authentication\Models\AuditLog;

interface AuditLogRepositoryInterface extends RepositoryInterface
{
    public function record(array $attributes): AuditLog;
}
