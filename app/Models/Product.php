<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;


    protected $fillable=[
        'name',
        'price',
        'description',
        'slug',
    ];


    /**
     * Get all of the product_galeries for the Product
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function galeries()
    {
        return $this->hasMany(ProductGallery::class, 'products_id', 'id');
    }

}
