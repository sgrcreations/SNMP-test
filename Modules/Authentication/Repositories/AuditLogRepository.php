<?php

namespace Modules\Authentication\Repositories;

use App\Core\Repositories\BaseRepository;
use Modules\Authentication\Models\AuditLog;
use Modules\Authentication\Repositories\Contracts\AuditLogRepositoryInterface;

class AuditLogRepository extends BaseRepository implements AuditLogRepositoryInterface
{
    public function __construct(AuditLog $model)
    {
        parent::__construct($model);
    }

    public function record(array $attributes): AuditLog
    {
        /** @var AuditLog $log */
        $log = $this->create($attributes);

        return $log;
    }
}
