<?php

/**
 * Role Model.
 *
 * Extends Spatie's Role model with UUID primary keys,
 * user relationships, and custom table naming.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasUuids;

    /**
     * The data type of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;
}
