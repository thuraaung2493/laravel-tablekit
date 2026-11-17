<?php

namespace TableKitDemo\Models;

class Client extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = ['name', 'phone', 'office', 'status', 'tenant_id'];
    protected $casts = ['tenant_id' => 'integer'];
}
