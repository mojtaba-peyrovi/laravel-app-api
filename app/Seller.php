<?php

namespace App;

use App\Product;

class Seller extends User
{
    protected $table = 'users';
    
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
