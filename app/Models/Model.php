<?php

namespace App\Models;

use App\Traits\CanGetTableNameStatically;
use Illuminate\Database\Eloquent\Model as EloquentModel;

class Model extends EloquentModel
{
	use CanGetTableNameStatically;
}
