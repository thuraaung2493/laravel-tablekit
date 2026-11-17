<?php

namespace TableKitDemo\Models;

class User extends \Illuminate\Foundation\Auth\User
{
    public $timestamps = false;
    protected $guarded = [];
}
